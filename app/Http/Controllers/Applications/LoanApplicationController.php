<?php

namespace App\Http\Controllers\Applications;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessLoanDisbursement;
use App\Models\Borrower;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Services\LoanAppraisalEngine;
use App\Services\Mpesa\MpesaDisbursementService;
use App\Services\Loans\LoanService;
use App\Support\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class LoanApplicationController extends Controller
{
    // ── Listing ──────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $user = $request->user();
        $scope = LoanApplication::query();

        if ($user->hasRole('loan-officer')) {
            $scope->where('created_by', $user->id);
        } elseif ($user->hasRole('supervisor')) {
            $scope->where('branch_id', $user->branch_id ?? 0);
        }
        $query = (clone $scope)->with(['borrower.user', 'loanProduct', 'loan'])->latest('created_at');

        $status = $request->string('status')->toString();
        $search = trim($request->string('search')->toString());

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('id', is_numeric($search) ? (int) $search : -1)
                    ->orWhereHas('borrower.user', function ($user) use ($search) {
                        $user->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('loanProduct', function ($product) use ($search) {
                        $product->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        $applications = $query->paginate(15)->withQueryString();

        $stats = [
            'total'    => (clone $scope)->count(),
            'pending'  => (clone $scope)->whereIn('status', ['pending', 'submitted', 'under_review', 'first_level_approved'])->count(),
            'approved' => (clone $scope)->where('status', 'approved')->count(),
            'rejected' => (clone $scope)->whereIn('status', ['rejected', 'declined'])->count(),
        ];

        return view('applications.index', compact('applications', 'stats', 'status', 'search'));
    }

    public function activeLoans(Request $request)
    {
        $organisationId = (int) auth()->user()->organisation_id;
        $search = trim($request->string('search')->toString());
        $user = $request->user();

        $loanScope = Loan::query()
            ->where('status', 'active')
            ->when($user->hasRole('loan-officer'), fn ($loans) => $loans->whereHas(
                'loanApplication',
                fn ($applications) => $applications->where('created_by', $user->id)
            ))
            ->when($user->hasRole('supervisor'), fn ($loans) => $loans->whereHas(
                'loanApplication',
                fn ($applications) => $applications->where('branch_id', $user->branch_id ?? 0)
            ));
        $query = (clone $loanScope)
            ->with(['borrower.user', 'loanProduct'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('id', is_numeric($search) ? (int) $search : -1)
                        ->orWhereHas('borrower.user', function ($user) use ($search) {
                            $user->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('loanProduct', function ($product) use ($search) {
                            $product->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('disbursed_at');

        $loans = $query->paginate(20)->withQueryString();

        $stats = [
            'count' => (clone $loanScope)->count(),
            'principal' => (clone $loanScope)->sum('principal_cents'),
            'payable' => (clone $loanScope)->sum('total_payable_cents'),
        ];

        return view('applications.active-loans', compact('loans', 'stats', 'search'));
    }

    // ── Create form ──────────────────────────────────────────────────────

    public function create()
    {
        $this->authorizePermission('create-loans');
        abort_if(
            auth()->user()->hasRole('supervisor')
            && !auth()->user()->hasRole(['admin', 'super-admin']),
            403,
            'Supervisors can only update applications returned for correction.'
        );

        $borrowers = Borrower::query()
            ->with('user')
            ->where('account_status', 'active')
            ->when(auth()->user()->hasRole(['loan-officer', 'supervisor']), fn ($query) =>
                $query->where('branch_id', auth()->user()->branch_id ?? 0)
            )
            ->orderByDesc('id')
            ->get();

        $products = LoanProduct::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $application = null;
        $securityData = [];

        return view('applications.create', compact('borrowers', 'products', 'application', 'securityData'));
    }

    // ── AJAX: live eligibility panel ─────────────────────────────────────

    /**
     * GET /applications/eligibility/{borrower}?loan_product_id=X
     *
     * Returns a JSON appraisal result so the create form can display a
     * real-time eligibility card before the officer submits.
     */
    public function eligibility(Request $request, int $borrower)
    {
        $orgId     = (int) auth()->user()->organisation_id;
        $productId = (int) $request->query('loan_product_id', 0);

        // Borrower must belong to this organisation
        abort_unless(
            DB::table('borrowers')
                ->where('organisation_id', $orgId)
                ->where('id', $borrower)
                ->when(auth()->user()->hasRole(['loan-officer', 'supervisor']), fn ($query) =>
                    $query->where('branch_id', auth()->user()->branch_id ?? 0)
                )
                ->exists(),
            404
        );

        $borrowerContact = DB::table('borrowers')
            ->join('users', 'users.id', '=', 'borrowers.user_id')
            ->where('borrowers.organisation_id', $orgId)
            ->where('borrowers.id', $borrower)
            ->select('users.name', 'users.email', 'borrowers.phone_number')
            ->firstOrFail();
        $profilePayload = DB::table('system_events')
            ->where('organisation_id', $orgId)
            ->where('aggregate_type', 'borrower')
            ->where('aggregate_id', $borrower)
            ->where('event_type', 'borrower_profile_created')
            ->where('status', 'processed')
            ->orderByDesc('occurred_at')
            ->value('payload');
        $borrowerProfile = is_array($profilePayload)
            ? $profilePayload
            : (json_decode((string) $profilePayload, true) ?? []);
        $borrowerDetails = [
            'name' => $borrowerContact->name,
            'phone' => $borrowerContact->phone_number,
            'email' => $borrowerContact->email,
            'residence_county' => $borrowerProfile['residence_county'] ?? null,
            'residence_address' => $borrowerProfile['residence_address'] ?? null,
        ];

        if ($productId === 0) {
            // No product selected yet — return borrower context only
            $borrowerRow = DB::table('borrowers')
                ->join('users', 'users.id', '=', 'borrowers.user_id')
                ->leftJoin('borrower_tiers', function ($j) use ($orgId) {
                    $j->on('borrower_tiers.id', '=', 'borrowers.current_tier_id')
                      ->where('borrower_tiers.organisation_id', '=', $orgId);
                })
                ->where('borrowers.organisation_id', $orgId)
                ->where('borrowers.id', $borrower)
                ->select(
                    'borrowers.id', 'borrowers.account_status', 'borrowers.behaviour_score',
                    'borrowers.lifetime_loans_count', 'borrowers.successful_loans_count',
                    'borrowers.on_time_loans_count', 'borrowers.consecutive_on_time_loans',
                    'borrowers.defaulted_loans_count', 'borrowers.current_tier_id',
                    'users.name', 'users.email', 'borrowers.phone_number',
                    'borrower_tiers.name as tier_name', 'borrower_tiers.tier_number',
                    'borrower_tiers.max_loan_amount_cents', 'borrower_tiers.min_loan_amount_cents',
                    'borrower_tiers.max_term_days'
                )
                ->first();

            $kyc = DB::table('kyc_records')
                ->where('organisation_id', $orgId)
                ->where('borrower_id', $borrower)
                ->where('is_current', true)
                ->select('verification_status', 'document_type', 'verified_at', 'full_name')
                ->first();

            $fp = DB::table('financial_profiles')
                ->where('organisation_id', $orgId)
                ->where('borrower_id', $borrower)
                ->where('is_current', true)
                ->select(
                    'financial_score', 'income_stability_score', 'cashflow_score',
                    'debt_service_ratio', 'average_monthly_income_cents',
                    'average_monthly_discretionary_cashflow_cents',
                    'average_monthly_debt_service_cents', 'analysis_months',
                    'model_version', 'calculated_at'
                )
                ->first();

            return response()->json([
                'product_required' => true,
                'borrower'         => $borrowerRow,
                'borrower_details' => $borrowerDetails,
                'kyc'              => $kyc,
                'financial_profile'=> $fp,
            ]);
        }

        // Full appraisal with product context
        $result = LoanAppraisalEngine::appraise($borrower, $productId, $orgId);

        return response()->json([
            'product_required'  => false,
            'borrower_details'  => $borrowerDetails,
            'eligible'          => $result->eligible,
            'max_amount_cents'  => $result->maxAmountCents,
            'max_amount_kes'    => round($result->maxAmountCents / 100, 2),
            'max_term_days'     => $result->maxTermDays,
            'blended_score'     => $result->blendedScore,
            'financial_score'   => $result->financialScore,
            'behaviour_score'   => $result->behaviourScore,
            'kyc_status'        => $result->kycStatus,
            'tier_name'         => $result->tierName,
            'tier_rule_id'      => $result->tierRuleId,
            'tier_rule_version' => $result->tierRuleVersion,
            'reasons'           => $result->reasons,
            'warnings'          => $result->warnings,
        ]);
    }

    // ── Store ────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        return $this->saveApplication($request);
    }

    public function guarantorMatch(Request $request)
    {
        $this->authorizePermission('create-loans');
        $data = $request->validate([
            'national_id' => ['required', 'string', 'max:30'],
            'application_id' => ['nullable', 'integer'],
        ]);

        if (!empty($data['application_id'])) {
            $application = LoanApplication::findOrFail($data['application_id']);
            $this->authorizeApplicationAccess($application);
        }

        $fingerprint = $this->guarantorFingerprint($data['national_id']);
        $query = LoanApplication::query()
            ->where('guarantor_id_fingerprint', $fingerprint);

        if (!empty($data['application_id'])) {
            $query->where('id', '!=', $data['application_id']);
        }

        return response()->json(['possible_match' => $query->exists()]);
    }

    public function securityFile(LoanApplication $application, string $file)
    {
        $this->authorizeApplicationAccess($application);
        $securityData = $application->security_data ?? [];
        $path = match (true) {
            $file === 'guarantor-photo' => $securityData['guarantor']['photo'] ?? null,
            $file === 'guarantor-id-front' => $securityData['guarantor']['id_front'] ?? null,
            $file === 'guarantor-id-back' => $securityData['guarantor']['id_back'] ?? null,
            preg_match('/^collateral-(\d+)-(\d+)$/', $file, $matches) === 1 =>
                $securityData['collateral'][(int) $matches[1]]['photos'][(int) $matches[2]]['path'] ?? null,
            default => null,
        };

        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function saveApplication(Request $request, ?LoanApplication $application = null)
    {
        $this->authorizePermission('create-loans');
        abort_if(
            !$application
            && auth()->user()->hasRole('supervisor')
            && !auth()->user()->hasRole(['admin', 'super-admin']),
            403,
            'Supervisors cannot originate new loan applications.'
        );
        $submit = $request->input('submit_mode', 'submit') !== 'draft';
        if ($submit) {
            $this->authorizePermission('loans.submit');
        }

        if ($application) {
            $this->authorizeApplicationAccess($application);
        }
        $existingGuarantor = $application?->security_data['guarantor'] ?? [];
        $rules = [
            'submit_mode' => ['nullable', 'in:draft,submit'],
            'borrower_id' => ['required', 'integer', 'exists:borrowers,id'],
            'loan_product_id' => ['required', 'integer', 'exists:loan_products,id'],
            'requested_amount' => ['required', 'numeric', 'min:1'],
            'requested_term_days' => ['required', 'integer', 'min:1'],
            'guarantor.name' => [$submit ? 'required' : 'nullable', 'string', 'max:255'],
            'guarantor.national_id' => [$submit ? 'required' : 'nullable', 'string', 'max:30'],
            'guarantor.phone' => [$submit ? 'required' : 'nullable', 'string', 'max:25', 'regex:/^\\+?[0-9][0-9\\s().-]{6,19}$/'],
            'guarantor.email' => ['nullable', 'email', 'max:255'],
            'guarantor.relationship' => [$submit ? 'required' : 'nullable', 'string', 'max:80'],
            'guarantor.occupation' => ['nullable', 'string', 'max:120'],
            'guarantor.address' => [$submit ? 'required' : 'nullable', 'string', 'max:1000'],
            'guarantor.employer' => ['nullable', 'string', 'max:255'],
            'guarantor.alternative_contact' => ['nullable', 'string', 'max:255'],
            'guarantor_photo' => [$submit && empty($existingGuarantor['photo']) ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'guarantor_id_front' => [$submit && empty($existingGuarantor['id_front']) ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'guarantor_id_back' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'collateral' => ['nullable', 'array', 'max:10'],
            'collateral.*.type' => [$submit ? 'required_with:collateral' : 'nullable', 'string', 'max:100'],
            'collateral.*.description' => [$submit ? 'required_with:collateral' : 'nullable', 'string', 'max:500'],
            'collateral.*.owner' => [$submit ? 'required_with:collateral' : 'nullable', 'string', 'max:255'],
            'collateral.*.reference' => ['nullable', 'string', 'max:100'],
            'collateral.*.estimated_value' => [$submit ? 'required_with:collateral' : 'nullable', 'numeric', 'min:0'],
            'collateral.*.location' => [$submit ? 'required_with:collateral' : 'nullable', 'string', 'max:500'],
            'collateral.*.field_visit_date' => [$submit ? 'required_with:collateral' : 'nullable', 'date'],
            'collateral.*.condition' => [$submit ? 'required_with:collateral' : 'nullable', 'string', 'max:500'],
            'collateral.*.ownership_evidence' => [$submit ? 'required_with:collateral' : 'nullable', 'string', 'max:1000'],
            'collateral.*.notes' => ['nullable', 'string', 'max:2000'],
            'collateral.*.photos' => ['nullable', 'array', 'max:8'],
            'collateral.*.photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'collateral.*.captions' => ['nullable', 'array'],
            'collateral.*.captions.*' => ['nullable', 'string', 'max:150'],
            'remove_collateral_photos' => ['nullable', 'array'],
            'remove_collateral_photos.*' => ['array'],
            'remove_collateral_photos.*.*' => ['integer', 'min:0'],
            'guarantor_consent' => [$submit ? 'accepted' : 'nullable'],
        ];
        $data = $request->validate($rules);

        $organisationId = (int) auth()->user()->organisation_id;

        $borrower = Borrower::query()
            ->where('account_status', 'active')
            ->when(auth()->user()->hasRole(['loan-officer', 'supervisor']), fn ($query) =>
                $query->where('branch_id', auth()->user()->branch_id ?? 0)
            )
            ->findOrFail($data['borrower_id']);
        $product = LoanProduct::query()->where('is_active', true)->findOrFail($data['loan_product_id']);

        $amountCents = (int) round(((float) $data['requested_amount']) * 100);
        $termDays    = (int) $data['requested_term_days'];

        // ── Run appraisal engine ──────────────────────────────────────
        $appraisal = LoanAppraisalEngine::appraise(
            $borrower->id,
            $product->id,
            $organisationId
        );

        // Hard block: KYC or account status failures are non-negotiable
        $hardBlockReasons = array_filter($appraisal->reasons, function (string $r) {
            return str_contains($r, 'KYC')
                || str_contains($r, 'Account is')
                || str_contains($r, 'not found');
        });

        if ($submit && !empty($hardBlockReasons)) {
            return back()->withInput()->withErrors([
                'borrower_id' => implode(' ', $hardBlockReasons),
            ]);
        }

        // Product-level amount/term guard
        if ($amountCents < $product->minimum_amount_cents || $amountCents > $product->maximum_amount_cents) {
            return back()->withInput()->withErrors([
                'requested_amount' => 'The requested amount is outside the selected product range.',
            ]);
        }

        if ($termDays < $product->minimum_term_days || $termDays > $product->maximum_term_days) {
            return back()->withInput()->withErrors([
                'requested_term_days' => 'The requested term is outside the selected product range.',
            ]);
        }

        // Enforce engine's max offer as a hard ceiling on requested amount
        if ($submit && $appraisal->eligible && $amountCents > $appraisal->maxAmountCents) {
            return back()->withInput()->withErrors([
                'requested_amount' => sprintf(
                    'Requested amount KSh %s exceeds the maximum eligible offer of KSh %s for this borrower.',
                    number_format($amountCents / 100, 2),
                    number_format($appraisal->maxAmountCents / 100, 2)
                ),
            ]);
        }

        // Enforce term ceiling from engine
        if ($submit && $appraisal->eligible && $termDays > $appraisal->maxTermDays) {
            return back()->withInput()->withErrors([
                'requested_term_days' => sprintf(
                    'Requested term %d days exceeds the maximum eligible term of %d days for this borrower.',
                    $termDays,
                    $appraisal->maxTermDays
                ),
            ]);
        }

        $previousPaths = $this->securityPaths($application?->security_data ?? []);
        $storedPaths = [];
        $isNewApplication = !$application || !$application->exists;
        $mustReverifyCollateral = (bool) $application && $submit;
        $application ??= new LoanApplication();

        try {
            DB::transaction(function () use (
                $request, $application, $data, $submit, $organisationId, $borrower, $product,
                $amountCents, $termDays, $appraisal, $isNewApplication, $mustReverifyCollateral, &$storedPaths
            ) {
                $application->fill([
                    'organisation_id' => $organisationId,
                    'branch_id' => $application->branch_id ?? auth()->user()->branch_id ?? $borrower->branch_id,
                    'created_by' => $application->created_by ?? auth()->id(),
                    'borrower_id' => $borrower->id,
                    'loan_product_id' => $product->id,
                    'loan_tier_rule_id' => $appraisal->tierRuleId,
                    'requested_amount_cents' => $amountCents,
                    'requested_term_days' => $termDays,
                    'financial_score' => $appraisal->financialScore,
                    'behaviour_score' => $appraisal->behaviourScore,
                    'appraisal_model_version' => 'appraisal-engine-v1',
                    'tier_rule_version' => $appraisal->tierRuleVersion,
                    'decision_snapshot' => $appraisal->snapshot,
                    'security_requirements_required' => true,
                    'status' => $submit ? ($appraisal->eligible ? 'submitted' : 'under_review') : 'draft',
                    'submitted_at' => $submit ? now() : null,
                    'first_level_approved_by' => null,
                    'first_level_approved_at' => null,
                    'first_level_reason' => null,
                    'final_approved_by' => null,
                    'final_approved_at' => null,
                    'returned_by' => null,
                    'returned_at' => null,
                    'return_reason' => null,
                    'approved_amount_cents' => null,
                    'approved_term_days' => null,
                    'decision' => null,
                    'decision_reason' => null,
                    'decided_at' => null,
                ]);
                $application->save();

                $securityData = $this->persistSecurityData(
                    $request,
                    $data,
                    $application,
                    $storedPaths,
                    $mustReverifyCollateral
                );
                if ($submit) {
                    $this->assertSecurityPackageComplete($securityData, $request);
                }

                $nationalId = trim((string) ($securityData['guarantor']['national_id'] ?? ''));
                $application->update([
                    'security_data' => $securityData,
                    'guarantor_id_fingerprint' => $nationalId === '' ? null : $this->guarantorFingerprint($nationalId),
                    'guarantor_consent_at' => $request->boolean('guarantor_consent')
                        ? now()
                        : $application->guarantor_consent_at,
                ]);
            });
        } catch (Throwable $exception) {
            $this->deleteSecurityFiles($storedPaths);

            throw $exception;
        }

        $newPaths = $this->securityPaths($application->fresh()->security_data ?? []);
        $this->deleteSecurityFiles(array_diff($previousPaths, $newPaths));

        Auditor::log(
            $isNewApplication ? ($submit ? 'application.submitted' : 'application.draft_saved')
                : ($submit ? 'application.resubmitted' : 'application.draft_saved'),
            $application,
            ['new' => ['status' => $application->status, 'branch_id' => $application->branch_id]]
        );

        return redirect()
            ->route('applications.show', $application)
            ->with('success', $submit ? 'Loan application submitted successfully.' : 'Loan application draft saved.');
    }

    private function persistSecurityData(
        Request $request,
        array $data,
        LoanApplication $application,
        array &$storedPaths,
        bool $mustReverifyCollateral
    ): array
    {
        $security = $application->security_data ?? ['guarantor' => [], 'collateral' => []];
        $guarantor = array_merge($security['guarantor'] ?? [], $data['guarantor'] ?? []);

        foreach ([
            'guarantor_photo' => 'photo',
            'guarantor_id_front' => 'id_front',
            'guarantor_id_back' => 'id_back',
        ] as $input => $key) {
            if ($request->boolean('remove_'.$input)) {
                $guarantor[$key] = null;
            }

            if ($request->hasFile($input)) {
                $path = $request->file($input)->store(
                    "application-security/{$application->organisation_id}/{$application->id}",
                    'local'
                );
                if ($path === false) {
                    throw new \RuntimeException('The guarantor document could not be stored securely.');
                }
                $storedPaths[] = $path;
                $guarantor[$key] = $path;
            }
        }

        $collateral = $security['collateral'] ?? [];
        if ($request->boolean('collateral_present')) {
            $collateral = [];
            foreach ($data['collateral'] ?? [] as $index => $assetData) {
                $previous = $security['collateral'][$index] ?? [];
                $asset = array_merge($previous, $assetData);
                if ($mustReverifyCollateral) {
                    unset($asset['independent_verification']);
                }
                $asset['estimated_value_cents'] = (int) round(((float) ($assetData['estimated_value'] ?? 0)) * 100);
                unset($asset['estimated_value'], $asset['photos'], $asset['captions']);

                $photos = $previous['photos'] ?? [];
                foreach ($request->input("remove_collateral_photos.{$index}", []) as $removeIndex) {
                    if (isset($photos[$removeIndex])) {
                        $photos[$removeIndex] = null;
                    }
                }
                $photos = array_values(array_filter($photos));

                foreach ($request->file("collateral.{$index}.photos", []) as $photoIndex => $file) {
                    $path = $file->store(
                        "application-security/{$application->organisation_id}/{$application->id}",
                        'local'
                    );
                    if ($path === false) {
                        throw new \RuntimeException('A collateral photograph could not be stored securely.');
                    }
                    $storedPaths[] = $path;
                    $photos[] = [
                        'path' => $path,
                        'caption' => $data['collateral'][$index]['captions'][$photoIndex] ?? null,
                    ];
                }

                $asset['photos'] = $photos;
                $collateral[] = $asset;
            }
        }

        return [
            'guarantor' => $guarantor,
            'collateral' => array_values($collateral),
        ];
    }

    private function assertSecurityPackageComplete(array $security, Request $request): void
    {
        $guarantor = $security['guarantor'] ?? [];
        foreach (['name', 'national_id', 'phone', 'relationship', 'address', 'photo', 'id_front'] as $field) {
            if (empty($guarantor[$field])) {
                throw ValidationException::withMessages([
                    $field === 'photo' ? 'guarantor_photo' : ($field === 'id_front' ? 'guarantor_id_front' : "guarantor.{$field}") =>
                        $field === 'photo'
                            ? 'A clear guarantor photograph is required.'
                            : ($field === 'id_front'
                                ? 'A clear image of the ID front is required.'
                                : 'This guarantor detail is required.'),
                ]);
            }
        }

        foreach (['photo', 'id_front'] as $field) {
            if (!Storage::disk('local')->exists($guarantor[$field])) {
                throw ValidationException::withMessages([
                    $field === 'photo' ? 'guarantor_photo' : 'guarantor_id_front' =>
                        'The saved image is missing. Upload a replacement before submission.',
                ]);
            }
        }

        if (!$request->boolean('guarantor_consent')) {
            throw ValidationException::withMessages([
                'guarantor_consent' => 'Confirm that the guarantor was informed and consented before submission.',
            ]);
        }

        foreach ($security['collateral'] ?? [] as $index => $asset) {
            if (empty($asset['photos'])) {
                throw ValidationException::withMessages([
                    "collateral.{$index}.photos" => 'Upload at least one photograph for each collateral item.',
                ]);
            }
            foreach ($asset['photos'] as $photo) {
                if (empty($photo['path']) || !Storage::disk('local')->exists($photo['path'])) {
                    throw ValidationException::withMessages([
                        "collateral.{$index}.photos" => 'A collateral photograph is missing. Upload a replacement before submission.',
                    ]);
                }
            }
        }
    }

    private function deleteSecurityFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (!Storage::disk('local')->delete($path)) {
                Log::warning('Could not remove a private loan security file.', ['path' => $path]);
            }
        }
    }

    private function securityPaths(array $security): array
    {
        $paths = [];
        foreach (['photo', 'id_front', 'id_back'] as $key) {
            if (!empty($security['guarantor'][$key])) {
                $paths[] = $security['guarantor'][$key];
            }
        }
        foreach ($security['collateral'] ?? [] as $asset) {
            foreach ($asset['photos'] ?? [] as $photo) {
                if (!empty($photo['path'])) {
                    $paths[] = $photo['path'];
                }
            }
        }

        return array_values(array_unique($paths));
    }

    private function guarantorFingerprint(string $nationalId): string
    {
        $normalized = strtoupper((string) preg_replace('/\s+/', '', trim($nationalId)));

        return hash_hmac('sha256', $normalized, (string) config('app.key'));
    }

    private function securityPackageIsComplete(LoanApplication $application): bool
    {
        return $application->hasCompleteSecurityPackage();
    }

    private function collateralVerificationIsComplete(LoanApplication $application): bool
    {
        foreach (($application->security_data['collateral'] ?? []) as $item) {
            if (($item['independent_verification']['status'] ?? null) !== 'verified') {
                return false;
            }
        }

        return true;
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->hasPermission($permission), 403);
    }

    private function authorizeApplicationAccess(LoanApplication $application): void
    {
        $user = auth()->user();
        abort_unless($user && (int) $user->organisation_id === (int) $application->organisation_id, 404);

        if ($user->hasRole(['admin', 'super-admin'])) {
            return;
        }
        if ($user->hasRole('loan-officer')) {
            abort_unless((int) $application->created_by === (int) $user->id, 404);

            return;
        }
        if ($user->hasRole('supervisor')) {
            abort_unless(
                $user->branch_id && (int) $application->branch_id === (int) $user->branch_id,
                404
            );

            return;
        }

        abort(404);
    }

    // ── Show ─────────────────────────────────────────────────────────────

    public function show(LoanApplication $application, MpesaDisbursementService $disbursementService)
    {
        $this->authorizeApplicationAccess($application);
        $application->load([
            'borrower.user',
            'borrower.currentTier',
            'loanProduct',
            'loanTierRule',
            'loan.disbursementTransaction',
            'branch',
            'creator',
            'firstLevelApprover',
            'finalApprover',
        ]);

        // Load live borrower context for the decision sidebar
        $orgId = (int) auth()->user()->organisation_id;

        $kyc = DB::table('kyc_records')
            ->where('organisation_id', $orgId)
            ->where('borrower_id', $application->borrower_id)
            ->where('is_current', true)
            ->first();

        $financialProfile = DB::table('financial_profiles')
            ->where('organisation_id', $orgId)
            ->where('borrower_id', $application->borrower_id)
            ->where('is_current', true)
            ->first();

        // Loan history is derived from the canonical loans table. The borrower
        // counters are denormalised and may be stale for older records.
        $borrowerLoanStats = DB::table('loans')
            ->where('organisation_id', $orgId)
            ->where('borrower_id', $application->borrower_id)
            ->selectRaw('COUNT(*) as total_loans_count')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_loans_count")
            ->selectRaw("SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as late_loans_count")
            ->selectRaw("SUM(CASE WHEN status IN ('defaulted', 'written_off') THEN 1 ELSE 0 END) as defaulted_loans_count")
            ->first();

        $canRetryDisbursement = $application->loan
            ? $disbursementService->canSafelyRetry($application->loan)
            : false;

        return view('applications.show', compact(
            'application',
            'kyc',
            'financialProfile',
            'borrowerLoanStats',
            'canRetryDisbursement'
        ));
    }

    // ── Edit ─────────────────────────────────────────────────────────────

    public function edit(LoanApplication $application)
    {
        $this->authorizeApplicationAccess($application);
        $application->load(['borrower.user', 'loanProduct']);

        $borrowers = Borrower::query()
            ->with('user')
            ->where('account_status', 'active')
            ->when(auth()->user()->hasRole(['loan-officer', 'supervisor']), fn ($query) =>
                $query->where('branch_id', auth()->user()->branch_id ?? 0)
            )
            ->orderByDesc('id')
            ->get();

        $products = LoanProduct::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $securityData = $application->security_data ?? [];

        return view('applications.create', compact('application', 'borrowers', 'products', 'securityData'));
    }

    // ── Update ───────────────────────────────────────────────────────────

    public function update(Request $request, LoanApplication $application)
    {
        $this->authorizeApplicationAccess($application);
        if (in_array($application->status, ['approved', 'rejected', 'declined'], true)) {
            return back()->withErrors([
                'application' => 'A decided application cannot be edited.',
            ]);
        }
        if (
            in_array($application->status, ['submitted', 'under_review', 'first_level_approved'], true)
            && !auth()->user()->hasRole(['admin', 'super-admin'])
        ) {
            abort(403, 'Only an administrator can amend an application while it is under review.');
        }

        return $this->saveApplication($request, $application);
    }

    public function verifyCollateral(Request $request, LoanApplication $application, int $index)
    {
        $this->authorizePermission('collateral.verify');
        abort_unless(auth()->user()->hasRole(['supervisor', 'admin', 'super-admin']), 403);
        $this->authorizeApplicationAccess($application);
        abort_unless(in_array($application->status, ['submitted', 'under_review'], true), 422);

        $data = $request->validate([
            'verification_status' => ['required', 'in:verified,issue'],
            'verification_notes' => ['required', 'string', 'max:2000'],
        ]);

        $security = $application->security_data ?? [];
        abort_unless(isset($security['collateral'][$index]), 404);
        $security['collateral'][$index]['independent_verification'] = [
            'status' => $data['verification_status'],
            'notes' => $data['verification_notes'],
            'verified_by' => auth()->id(),
            'verified_at' => now()->toIso8601String(),
        ];
        $application->update(['security_data' => $security]);

        Auditor::log('collateral.independently_verified', $application, [
            'new' => [
                'collateral_index' => $index,
                'verification_status' => $data['verification_status'],
                'verification_notes' => $data['verification_notes'],
            ],
        ]);

        return back()->with('success', 'Collateral verification recorded.');
    }

    public function firstLevelApprove(Request $request, LoanApplication $application)
    {
        $this->authorizePermission('loans.approve.first_level');
        abort_unless(auth()->user()->hasRole(['supervisor', 'admin', 'super-admin']), 403);
        $this->authorizeApplicationAccess($application);

        abort_if((int) $application->created_by === (int) auth()->id(), 403, 'You cannot approve an application you originated.');
        abort_unless(in_array($application->status, ['submitted', 'under_review'], true), 422);

        if (!$this->securityPackageIsComplete($application)) {
            return back()->withErrors([
                'application' => 'First-level approval is blocked until the guarantor details, required images, consent, and collateral photographs are complete.',
            ]);
        }
        if (!$this->collateralVerificationIsComplete($application)) {
            return back()->withErrors([
                'application' => 'Independently verify every collateral item before first-level approval.',
            ]);
        }

        $data = $request->validate([
            'decision_reason' => ['required', 'string', 'max:5000'],
        ]);

        $application->update([
            'status' => 'first_level_approved',
            'first_level_approved_by' => auth()->id(),
            'first_level_approved_at' => now(),
            'first_level_reason' => $data['decision_reason'],
        ]);

        Auditor::log('application.first_level_approved', $application, [
            'new' => [
                'status' => 'first_level_approved',
                'decision_reason' => $data['decision_reason'],
            ],
        ]);

        return back()->with('success', 'First-level approval recorded. The application is ready for final admin review.');
    }

    public function returnForCorrection(Request $request, LoanApplication $application)
    {
        $this->authorizePermission('loans.return');
        $this->authorizeApplicationAccess($application);
        abort_if((int) $application->created_by === (int) auth()->id(), 403, 'You cannot return an application you originated.');
        abort_unless(in_array($application->status, ['submitted', 'under_review', 'first_level_approved'], true), 422);

        $data = $request->validate([
            'return_reason' => ['required', 'string', 'max:5000'],
        ]);
        $application->update([
            'status' => 'returned',
            'returned_by' => auth()->id(),
            'returned_at' => now(),
            'return_reason' => $data['return_reason'],
            'first_level_approved_by' => null,
            'first_level_approved_at' => null,
            'first_level_reason' => null,
        ]);

        Auditor::log('application.returned', $application, [
            'new' => ['status' => 'returned', 'return_reason' => $data['return_reason']],
        ]);

        return back()->with('success', 'Application returned to its originator for correction.');
    }

    // ── Final approval ───────────────────────────────────────────────────

    public function approve(Request $request, LoanApplication $application)
    {
        $this->authorizePermission('loans.approve.final');
        abort_unless(auth()->user()->hasRole(['admin', 'super-admin']), 403);
        $this->authorizeApplicationAccess($application);
        abort_if(
            (int) $application->created_by === (int) auth()->id()
            || (int) $application->first_level_approved_by === (int) auth()->id(),
            403,
            'The final approver must be different from the originator and first-level approver.'
        );
        abort_unless($application->status === 'first_level_approved', 422, 'Supervisor first-level approval is required.');

        if (!$this->securityPackageIsComplete($application)) {
            return back()->withErrors([
                'application' => 'Final approval is blocked until mandatory guarantor details, documents, consent, and collateral evidence are complete.',
            ]);
        }
        if (!$this->collateralVerificationIsComplete($application)) {
            return back()->withErrors(['application' => 'Independent collateral verification is required before final approval.']);
        }

        $data = $request->validate([
            'approved_amount'    => ['nullable', 'numeric', 'min:0'],
            'approved_term_days' => ['nullable', 'integer', 'min:1'],
            'decision_reason'    => ['nullable', 'string', 'max:5000'],
        ]);

        $organisationId = (int) auth()->user()->organisation_id;

        $amountCents = isset($data['approved_amount'])
            ? (int) round(((float) $data['approved_amount']) * 100)
            : $application->requested_amount_cents;

        $termDays = $data['approved_term_days'] ?? $application->requested_term_days;

        // Re-run appraisal to get the current max offer (scores/financials may have been
        // updated since submission — always use the latest picture at decision time)
        $appraisal = LoanAppraisalEngine::appraise(
            $application->borrower_id,
            $application->loan_product_id,
            $organisationId
        );

        // Hard ceiling: approved amount must not exceed the engine's max offer
        if ($appraisal->eligible && $amountCents > $appraisal->maxAmountCents) {
            return back()->withErrors([
                'approved_amount' => sprintf(
                    'Approved amount KSh %s exceeds the engine\'s maximum eligible offer of KSh %s for this borrower.',
                    number_format($amountCents / 100, 2),
                    number_format($appraisal->maxAmountCents / 100, 2)
                ),
            ]);
        }

        // Hard ceiling: term must not exceed engine's max
        if ($appraisal->eligible && $termDays > $appraisal->maxTermDays) {
            return back()->withErrors([
                'approved_term_days' => sprintf(
                    'Approved term %d days exceeds the engine\'s maximum eligible term of %d days.',
                    $termDays,
                    $appraisal->maxTermDays
                ),
            ]);
        }

        // If engine says ineligible (e.g. KYC lapsed, exposure blown), block approval
        if (!$appraisal->eligible) {
            return back()->withErrors([
                'approved_amount' => 'Cannot approve: ' . implode(' ', $appraisal->reasons),
            ]);
        }

        $application->update([
            'approved_amount_cents'  => $amountCents,
            'approved_term_days'     => $termDays,
            'financial_score'        => $appraisal->financialScore,
            'behaviour_score'        => $appraisal->behaviourScore,
            'loan_tier_rule_id'      => $appraisal->tierRuleId ?? $application->loan_tier_rule_id,
            'appraisal_model_version'=> 'appraisal-engine-v1',
            'tier_rule_version'      => $appraisal->tierRuleVersion ?? $application->tier_rule_version,
            'status'                 => 'approved',
            'decision'               => 'approved',
            'decision_reason'        => $data['decision_reason'] ?? null,
            'decision_snapshot'      => $appraisal->snapshot,
            'decided_at'             => now(),
            'final_approved_by'      => auth()->id(),
            'final_approved_at'      => now(),
        ]);

        Auditor::log('application.approved', $application, [
            'new' => ['status' => 'approved', 'approved_amount_cents' => $amountCents, 'approved_term_days' => $termDays],
        ]);

        return back()->with('success', 'Application approved.');
    }

    // ── Disburse ─────────────────────────────────────────────────────────

    /**
     * POST /applications/{application}/disburse
     *
     * Creates a Loan from an approved LoanApplication, generates the
     * repayment schedule, and dispatches the B2C disbursement job.
     *
     * Preconditions:
     *   - application.status === 'approved'
     *   - No loan already exists for this application.
     */
    public function disburse(
        LoanApplication $application,
        LoanService $loanService,
        MpesaDisbursementService $disbursementService
    )
    {
        $this->authorizePermission('loans.disburse');
        abort_unless(auth()->user()->hasRole(['admin', 'super-admin']), 403);
        $this->authorizeApplicationAccess($application);
        abort_unless(
            $application->first_level_approved_by
            && $application->final_approved_by
            && (int) $application->first_level_approved_by !== (int) $application->final_approved_by
            && (int) $application->created_by !== (int) $application->first_level_approved_by
            && (int) $application->created_by !== (int) $application->final_approved_by,
            422,
            'Distinct first-level and final approvals are required before disbursement.'
        );
        if (!$application->security_requirements_required || !$this->securityPackageIsComplete($application)) {
            return back()->withErrors([
                'application' => 'Disbursement is blocked until the required guarantor and security documents are complete.',
            ]);
        }

        $existingLoan = Loan::where('organisation_id', $application->organisation_id)
            ->where('loan_application_id', $application->id)
            ->first();

        if ($existingLoan) {
            if (
                $application->status !== 'loan_created'
                || !$disbursementService->canSafelyRetry($existingLoan)
            ) {
                return back()->withErrors([
                    'application' => 'This loan cannot be retried safely. Check its M-Pesa transaction status before trying again.',
                ]);
            }

            try {
                $disbursementService->assertReady(
                    (int) $application->organisation_id,
                    $application->borrower?->phone_number
                );
            } catch (\RuntimeException $e) {
                return back()->withErrors(['application' => $e->getMessage()]);
            }

            $existingLoan->update(['status' => 'disbursement_pending']);
            ProcessLoanDisbursement::dispatch($existingLoan->id, (int) $existingLoan->organisation_id);

            Auditor::log('loan.disbursement_retried', $existingLoan, [
                'new' => ['status' => 'disbursement_pending', 'application_id' => $application->id],
            ]);

            return back()->with('success', "Loan #{$existingLoan->id} M-Pesa disbursement retry queued.");
        }

        abort_unless($application->status === 'approved', 422, 'Only approved applications can be disbursed.');

        try {
            $disbursementService->assertReady(
                (int) $application->organisation_id,
                $application->borrower?->phone_number
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['application' => $e->getMessage()]);
        }

        try {
            $loan = $loanService->createFromApplication($application);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['application' => $e->getMessage()]);
        }

        // Queue the B2C disbursement — loan activates asynchronously on callback
        ProcessLoanDisbursement::dispatch($loan->id, (int) $loan->organisation_id);

        Auditor::log('loan.disbursed', $loan, [
            'new' => ['status' => 'disbursement_pending', 'application_id' => $application->id],
        ]);

        return back()->with(
            'success',
            "Loan #{$loan->id} created (ref: {$loan->repayment_reference}). Disbursement queued."
        );
    }

    // ── Reject ───────────────────────────────────────────────────────────

    public function reject(Request $request, LoanApplication $application)
    {
        $this->authorizePermission('loans.reject');
        abort_unless(auth()->user()->hasRole(['supervisor', 'admin', 'super-admin']), 403);
        $this->authorizeApplicationAccess($application);
        abort_if((int) $application->created_by === (int) auth()->id(), 403, 'You cannot reject an application you originated.');
        $data = $request->validate([
            'decision_reason' => ['required', 'string', 'max:5000'],
        ]);

        abort_unless(
            in_array($application->status, ['pending', 'submitted', 'under_review', 'first_level_approved'], true),
            422
        );

        $closedAt = now();
        $application->update([
            'status'          => 'rejected',
            'decision'        => 'rejected',
            'decision_reason' => $data['decision_reason'],
            'decided_at'      => $closedAt,
            'final_approved_by' => null,
            'final_approved_at' => null,
            'security_retention_until' => $closedAt->copy()->addYears(LoanApplication::SECURITY_DATA_RETENTION_YEARS),
        ]);

        Auditor::log('application.rejected', $application, [
            'new' => ['status' => 'rejected', 'decision_reason' => $data['decision_reason']],
        ]);

        return back()->with('success', 'Application rejected.');
    }
}
