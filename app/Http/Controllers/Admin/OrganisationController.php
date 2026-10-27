<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Borrower;
use App\Models\BorrowerTier;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\LoanTierRule;
use App\Models\MpesaSetting;
use App\Models\Organisation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OrganisationController extends Controller
{
    /**
     * Display the organisation overview page
     */
    public function index()
    {
        $orgId = Auth::user()->organisation_id;
        $organisation = Organisation::findOrFail($orgId);
        $now = Carbon::now();
        $mStart = $now->copy()->startOfMonth();
        $mEnd = $now->copy()->endOfMonth();
        $pmStart = $now->copy()->subMonth()->startOfMonth();
        $pmEnd = $now->copy()->subMonth()->endOfMonth();

        // ── Stat: Total Borrowers ─────────────────────────────────────────────
        $totalBorrowers = Borrower::where('organisation_id', $orgId)->count();
        $prevBorrowers = Borrower::where('organisation_id', $orgId)
            ->where('registered_at', '<', $mStart)->count();
        $borrowersDelta = $this->calculateDelta($totalBorrowers, $prevBorrowers);

        // ── Stat: Active Loans ─────────────────────────────────────────────────
        $activeLoans = Loan::where('organisation_id', $orgId)
            ->whereIn('status', ['active', 'overdue'])
            ->count();

        $prevActiveLoans = Loan::where('organisation_id', $orgId)
            ->whereIn('status', ['active', 'overdue'])
            ->where('disbursed_at', '<', $mStart)
            ->count();

        $activeLoansDelta = $this->calculateDelta($activeLoans, $prevActiveLoans);

        // ── Stat: Portfolio (Outstanding Principal) ───────────────────────────
        $portfolioCents = Loan::where('organisation_id', $orgId)
            ->whereIn('status', ['active', 'overdue'])
            ->sum('principal_cents');

        $thisMonthDisbursed = Loan::where('organisation_id', $orgId)
            ->whereBetween('disbursed_at', [$mStart, $mEnd])
            ->sum('principal_cents');

        $lastMonthDisbursed = Loan::where('organisation_id', $orgId)
            ->whereBetween('disbursed_at', [$pmStart, $pmEnd])
            ->sum('principal_cents');

        $portfolioDelta = $this->calculateDelta($thisMonthDisbursed, $lastMonthDisbursed);

        // ── Stat: Collection Rate ─────────────────────────────────────────────
        $totalPayableCents = Loan::where('organisation_id', $orgId)
            ->whereIn('status', ['active', 'overdue', 'completed'])
            ->sum('total_payable_cents');

        $collectedCents = LoanPayment::where('organisation_id', $orgId)
            ->where('status', 'posted')
            ->sum('amount_cents');

        $collectionRate = $totalPayableCents > 0
            ? round(($collectedCents / $totalPayableCents) * 100, 1)
            : 0;

        $prevCollected = LoanPayment::where('organisation_id', $orgId)
            ->where('status', 'posted')
            ->where('posted_at', '<', $mStart)
            ->sum('amount_cents');

        $prevPayable = Loan::where('organisation_id', $orgId)
            ->whereIn('status', ['active', 'overdue', 'completed'])
            ->where('disbursed_at', '<', $mStart)
            ->sum('total_payable_cents');

        $prevCollectionRate = $prevPayable > 0
            ? round(($prevCollected / $prevPayable) * 100, 1)
            : 0;

        $collectionDelta = $this->calculateDelta($collectionRate, $prevCollectionRate);

        // ── Secondary Stats ────────────────────────────────────────────────────
        $overdueCents = Loan::where('organisation_id', $orgId)
            ->where('status', 'overdue')
            ->sum('principal_cents');

        $totalLoans = Loan::where('organisation_id', $orgId)->count();
        $overdueLoans = Loan::where('organisation_id', $orgId)
            ->where('status', 'overdue')
            ->count();

        $par30 = $totalLoans > 0 ? round(($overdueLoans / $totalLoans) * 100, 1) : 0;

        $disbursedThisMonth = $thisMonthDisbursed;
        $repaymentsThisMonth = LoanPayment::where('organisation_id', $orgId)
            ->where('status', 'posted')
            ->whereBetween('posted_at', [$mStart, $mEnd])
            ->sum('amount_cents');

        return view('organisation.index', compact(
            'organisation',
            'totalBorrowers',
            'borrowersDelta',
            'activeLoans',
            'activeLoansDelta',
            'portfolioCents',
            'portfolioDelta',
            'collectionRate',
            'collectionDelta',
            'overdueCents',
            'par30',
            'disbursedThisMonth',
            'repaymentsThisMonth'
        ));
    }

    /**
     * Display the general configuration page
     */
    public function general()
    {
        $orgId = Auth::user()->organisation_id;
        $organisation = Organisation::findOrFail($orgId);

        return view('organisation.config.general', compact('organisation'));
    }

    /**
     * Display the lending configuration page
     */
    public function lending()
    {
        $orgId = Auth::user()->organisation_id;
        $organisation = Organisation::findOrFail($orgId);
        $tiers = BorrowerTier::where('organisation_id', $orgId)
            ->orderBy('tier_number')
            ->get();

        return view('organisation.config.lending', compact('organisation', 'tiers'));
    }

    /**
     * Display the payments configuration page
     */
    public function payments()
    {
        $orgId = Auth::user()->organisation_id;
        $organisation = Organisation::findOrFail($orgId);
        $paymentSettings = MpesaSetting::where('organisation_id', $orgId)
            ->orderByRaw("CASE WHEN environment = 'sandbox' THEN 0 ELSE 1 END")
            ->get();

        return view('organisation.config.payments', compact('organisation', 'paymentSettings'));
    }

    /**
     * Display the notifications configuration page
     */
    public function notifications()
    {
        $orgId = Auth::user()->organisation_id;
        $organisation = Organisation::findOrFail($orgId);

        return view('organisation.config.notifications', compact('organisation'));
    }

    /**
     * Display the branding configuration page
     */
    public function branding()
    {
        $orgId = Auth::user()->organisation_id;
        $organisation = Organisation::findOrFail($orgId);

        return view('organisation.config.branding', compact('organisation'));
    }

    /**
     * Show the form for editing the organisation
     */
    public function edit()
    {
        $orgId = Auth::user()->organisation_id;
        $organisation = Organisation::findOrFail($orgId);

        return view('organisation.edit', compact('organisation'));
    }

    /**
     * Update the organisation
     */
    public function update(Request $request)
    {
        $orgId = Auth::user()->organisation_id;
        $organisation = Organisation::findOrFail($orgId);

        $request->merge([
            'use_org_branding' => $request->boolean('use_org_branding'),
        ]);

        $validated = $request->validate([
            // Identity fields
            'name' => 'required|string|max:255',
            'trading_name' => 'nullable|string|max:255',
            'organisation_type' => 'nullable|string|max:255',
            'company_reg_number' => 'nullable|string|max:255|unique:organisations,company_reg_number,'.$orgId,
            'tax_pin' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',

            // Address fields
            'county' => 'nullable|string|max:255',
            'town' => 'nullable|string|max:255',
            'physical_address' => 'nullable|string|max:500',

            // Branding fields
            'primary_brand_color' => 'nullable|string|max:7',
            'secondary_brand_color' => 'nullable|string|max:7',
            'use_org_branding' => 'nullable|boolean',
        ]);

        // Handle checkbox for boolean field
        $validated['use_org_branding'] = $request->boolean('use_org_branding');

        $organisation->update($validated);

        return redirect()->route('organisation.index')
            ->with('success', 'Organisation updated successfully.');
    }

    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $organisation = Organisation::findOrFail(Auth::user()->organisation_id);
        $organisation->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        $status = $organisation->is_active ? 'activated' : 'deactivated';

        return redirect()->route('organisation.index')
            ->with('success', "Organisation {$status} successfully.");
    }

    /**
     * Update organisation configuration
     */
    public function updateGeneral(Request $request)
    {
        return $this->updateConfig($request, 'general');
    }

    public function updateLending(Request $request)
    {
        return $this->updateConfig($request, 'lending');
    }

    public function storeTier(Request $request)
    {
        $organisationId = (int) Auth::user()->organisation_id;
        Organisation::findOrFail($organisationId);
        $validated = $this->validateTierInput($request);

        $tier = DB::transaction(function () use ($organisationId, $validated) {
            $tierNumber = (int) BorrowerTier::where('organisation_id', $organisationId)
                ->lockForUpdate()
                ->max('tier_number') + 1;

            return BorrowerTier::create([
                'organisation_id' => $organisationId,
                'tier_number' => $tierNumber,
                'name' => $validated['name'],
                'min_loan_amount_cents' => (int) round((float) $validated['min_loan_amount'] * 100),
                'max_loan_amount_cents' => (int) round((float) $validated['max_loan_amount'] * 100),
                'max_term_days' => $validated['max_term_days'],
                'minimum_financial_score' => round((float) $validated['acceptance_percentage'] / 100, 4),
                'is_entry_tier' => ! BorrowerTier::where('organisation_id', $organisationId)
                    ->where('is_entry_tier', true)
                    ->exists(),
                'is_active' => true,
                'version' => 1,
            ]);
        });

        return redirect()->route('organisation.lending')
            ->with('success', "{$tier->name} tier added successfully.");
    }

    public function updateTier(Request $request, int $tier)
    {
        $organisationId = (int) Auth::user()->organisation_id;
        $borrowerTier = BorrowerTier::where('organisation_id', $organisationId)
            ->findOrFail($tier);
        $validated = $this->validateTierInput($request);

        DB::transaction(function () use ($borrowerTier, $validated) {
            $previousMaximum = (int) $borrowerTier->max_loan_amount_cents;
            $previousMaximumTerm = (int) $borrowerTier->max_term_days;
            $updates = [
                'name' => $validated['name'],
                'min_loan_amount_cents' => (int) round((float) $validated['min_loan_amount'] * 100),
                'max_loan_amount_cents' => (int) round((float) $validated['max_loan_amount'] * 100),
                'max_term_days' => $validated['max_term_days'],
                'minimum_financial_score' => round((float) $validated['acceptance_percentage'] / 100, 4),
            ];
            $hasChanges = $borrowerTier->name !== $updates['name']
                || $borrowerTier->min_loan_amount_cents !== $updates['min_loan_amount_cents']
                || $borrowerTier->max_loan_amount_cents !== $updates['max_loan_amount_cents']
                || $borrowerTier->max_term_days !== $updates['max_term_days']
                || $borrowerTier->minimum_financial_score === null
                || (float) $borrowerTier->minimum_financial_score !== $updates['minimum_financial_score'];

            if ($hasChanges) {
                $updates['version'] = (int) $borrowerTier->version + 1;
            }

            $borrowerTier->update($updates);
            $this->syncDefaultTierRuleMaximums($borrowerTier, $previousMaximum, $previousMaximumTerm);
        });

        return redirect()->route('organisation.lending')
            ->with('success', "{$borrowerTier->name} tier updated successfully.");
    }

    public function destroyTier(int $tier)
    {
        $organisationId = (int) Auth::user()->organisation_id;
        $tierName = DB::transaction(function () use ($organisationId, $tier) {
            $borrowerTier = BorrowerTier::where('organisation_id', $organisationId)
                ->lockForUpdate()
                ->findOrFail($tier);

            if ($borrowerTier->borrowers()->exists()
                || $borrowerTier->incomingTierHistories()->exists()
                || $borrowerTier->outgoingTierHistories()->exists()
                || $borrowerTier->incomingGraduationEvents()->exists()
                || $borrowerTier->outgoingGraduationEvents()->exists()) {
                throw ValidationException::withMessages([
                    'tiers' => "The {$borrowerTier->name} tier is in use by borrowers or lending history and cannot be deleted.",
                ]);
            }

            $rules = $borrowerTier->loanTierRules()->get();
            foreach ($rules as $rule) {
                if ($rule->loanApplications()->exists()
                    || $rule->loans()->exists()
                    || $rule->graduationEvents()->exists()) {
                    throw ValidationException::withMessages([
                        'tiers' => "The {$borrowerTier->name} tier has lending rules used by applications, loans, or history and cannot be deleted.",
                    ]);
                }
            }

            $wasEntryTier = $borrowerTier->is_entry_tier;
            foreach ($rules as $rule) {
                $rule->delete();
            }
            $borrowerTier->delete();

            if ($wasEntryTier) {
                BorrowerTier::where('organisation_id', $organisationId)
                    ->orderBy('tier_number')
                    ->first()
                    ?->update(['is_entry_tier' => true]);
            }

            return $borrowerTier->name;
        });

        return redirect()->route('organisation.lending')
            ->with('success', "{$tierName} tier deleted successfully.");
    }

    private function validateTierInput(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'min_loan_amount' => 'required|numeric|min:0|max:100000000000',
            'max_loan_amount' => 'required|numeric|min:0|max:100000000000',
            'acceptance_percentage' => 'required|numeric|min:0|max:100',
            'max_term_days' => 'required|integer|min:1|max:65535',
        ]);

        if ((float) $validated['min_loan_amount'] > (float) $validated['max_loan_amount']) {
            throw ValidationException::withMessages([
                'min_loan_amount' => 'The minimum loan amount cannot exceed the maximum.',
            ]);
        }

        return $validated;
    }

    public function updatePayments(Request $request)
    {
        $orgId = Auth::user()->organisation_id;
        Organisation::findOrFail($orgId);

        $validated = $request->validate([
            'environment' => 'required|string|in:sandbox,production',
            'consumer_key' => 'nullable|string|max:500',
            'consumer_secret' => 'nullable|string|max:500',
            'passkey' => 'nullable|string|max:500',
            'business_shortcode' => 'nullable|string|max:30',
            'head_office' => 'nullable|string|max:30',
            'store_number' => 'nullable|string|max:30',
            'till_number' => 'nullable|string|max:30',
            'g2_password' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $setting = MpesaSetting::where('organisation_id', $orgId)
            ->where('environment', $validated['environment'])
            ->first();

        $credentials = collect($validated)
            ->only(['consumer_key', 'consumer_secret', 'passkey', 'g2_password'])
            ->reject(fn ($value) => blank($value))
            ->all();

        $settingData = array_merge(
            collect($validated)->only([
                'business_shortcode',
                'head_office',
                'store_number',
                'till_number',
            ])->all(),
            $credentials,
            ['is_active' => $request->boolean('is_active')]
        );

        if (! $setting && empty($credentials['consumer_key'])) {
            return back()->withErrors(['consumer_key' => 'Consumer key is required when creating a payment configuration.'])->withInput();
        }

        if (! $setting && empty($credentials['consumer_secret'])) {
            return back()->withErrors(['consumer_secret' => 'Consumer secret is required when creating a payment configuration.'])->withInput();
        }

        MpesaSetting::updateOrCreate(
            [
                'organisation_id' => $orgId,
                'environment' => $validated['environment'],
            ],
            $settingData
        );

        return redirect()->route('organisation.payments')
            ->with('success', 'Payment configuration updated successfully.');
    }

    public function updateNotifications(Request $request)
    {
        $orgId = Auth::user()->organisation_id;
        $organisation = Organisation::findOrFail($orgId);

        foreach ([
            'notifications_enabled',
            'sms_notifications_enabled',
            'email_notifications_enabled',
            'in_app_notifications_enabled',
        ] as $field) {
            $request->merge([$field => $request->boolean($field)]);
        }

        $validated = $request->validate([
            'notifications_enabled' => 'required|boolean',
            'sms_notifications_enabled' => 'required|boolean',
            'email_notifications_enabled' => 'required|boolean',
            'in_app_notifications_enabled' => 'required|boolean',
            'notification_sender_name' => 'nullable|string|max:100',
            'notification_reply_to_email' => 'nullable|email|max:255',
        ]);

        $organisation->update($validated);

        return redirect()->route('organisation.notifications')
            ->with('success', 'Notification settings updated successfully.');
    }

    public function updateBranding(Request $request)
    {
        return $this->updateConfig($request, 'branding');
    }

    private function updateConfig(Request $request, string $section)
    {
        $orgId = Auth::user()->organisation_id;
        $organisation = Organisation::findOrFail($orgId);

        if ($section === 'lending') {
            $request->merge([
                'late_payment_handling' => $request->boolean('late_payment_handling'),
            ]);
        }

        if ($section === 'branding') {
            $request->merge([
                'use_org_branding' => $request->boolean('use_org_branding'),
                'remove_logo' => $request->boolean('remove_logo'),
            ]);
        }

        $validationRules = $this->getConfigValidationRules($section);
        $validated = $request->validate($validationRules);

        // Handle boolean fields
        if ($section === 'lending') {
            $validated['late_payment_handling'] = $request->boolean('late_payment_handling');
        }
        if ($section === 'branding') {
            $validated['use_org_branding'] = $request->boolean('use_org_branding');

            if ($request->boolean('remove_logo') && $organisation->logo_path) {
                Storage::disk('public')->delete(str_replace('storage/', '', $organisation->logo_path));
                $validated['logo_path'] = null;
            } elseif ($request->hasFile('logo')) {
                if ($organisation->logo_path) {
                    Storage::disk('public')->delete(str_replace('storage/', '', $organisation->logo_path));
                }

                $validated['logo_path'] = 'storage/'.$request->file('logo')->store('organisations/logos', 'public');
            }

            unset($validated['logo'], $validated['remove_logo']);
        }

        $organisation->update($validated);

        return redirect()->route("organisation.{$section}")
            ->with('success', ucfirst($section).' configuration updated successfully.');
    }

    private function syncDefaultTierRuleMaximums(
        BorrowerTier $tier,
        int $previousTierMaximum,
        int $previousTierMaximumTerm
    ): void {
        $rules = LoanTierRule::where('organisation_id', $tier->organisation_id)
            ->where('borrower_tier_id', $tier->id)
            ->where('is_active', true)
            ->with('loanProduct')
            ->get();

        foreach ($rules as $rule) {
            $product = $rule->loanProduct;
            if (! $product) {
                continue;
            }

            $previousDefaultMaximum = min(
                (int) $product->maximum_amount_cents,
                $previousTierMaximum
            );
            $newMaximum = min(
                (int) $product->maximum_amount_cents,
                (int) $tier->max_loan_amount_cents
            );
            $previousDefaultTerm = min(
                (int) $product->maximum_term_days,
                $previousTierMaximumTerm
            );
            $newMaximumTerm = min(
                (int) $product->maximum_term_days,
                (int) $tier->max_term_days
            );
            $updateAmount = (int) $rule->maximum_amount_cents === $previousDefaultMaximum
                && (int) $rule->maximum_amount_cents !== $newMaximum;
            $updateTerm = (int) $rule->maximum_term_days === $previousDefaultTerm
                && (int) $rule->maximum_term_days !== $newMaximumTerm;

            if (! $updateAmount && ! $updateTerm) {
                continue;
            }

            $nextVersion = (int) $rule->version + 1;
            $rule->is_active = false;
            $rule->save();

            $nextRule = $rule->replicate();
            $nextRule->version = $nextVersion;
            if ($updateAmount) {
                $nextRule->maximum_amount_cents = $newMaximum;
            }
            if ($updateTerm) {
                $nextRule->maximum_term_days = $newMaximumTerm;
            }
            $nextRule->is_active = true;
            $nextRule->save();
        }
    }

    /**
     * Get validation rules for different configuration sections
     */
    private function getConfigValidationRules(string $section): array
    {
        return match ($section) {
            'general' => [
                'country_code' => 'required|string|max:2',
                'currency' => 'required|string|max:3',
                'timezone' => 'required|string|max:255',
                'date_format' => 'required|string|max:20',
                'financial_year_start' => 'required|string|max:20',
                'financial_year_end' => 'required|string|max:20',
            ],
            'lending' => [
                'default_loan_term' => 'required|integer|min:1',
                'default_repayment_frequency' => 'required|string|in:daily,weekly,monthly',
                'interest_calculation_method' => 'required|string|in:reducing_balance,flat_rate',
                'late_payment_handling' => 'nullable|boolean',
                'grace_period' => 'required|integer|min:0',
                'maximum_active_loans' => 'required|integer|min:1',
            ],
            'payments' => [
                // Payment-specific configuration rules will be added later
            ],
            'notifications' => [
                // Notification-specific configuration rules will be added later
            ],
            'branding' => [
                'primary_brand_color' => 'nullable|string|max:7',
                'secondary_brand_color' => 'nullable|string|max:7',
                'use_org_branding' => 'nullable|boolean',
                'remove_logo' => 'nullable|boolean',
                'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            ],
            default => [],
        };
    }

    /**
     * Calculate percentage delta between current and previous values
     */
    private function calculateDelta(float $current, float $previous): array
    {
        if ($previous == 0) {
            return [
                'value' => $current > 0 ? 100 : 0,
                'direction' => $current > 0 ? 'up' : 'neutral',
            ];
        }

        $delta = (($current - $previous) / $previous) * 100;

        return [
            'value' => abs(round($delta, 1)),
            'direction' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'neutral'),
        ];
    }
}
