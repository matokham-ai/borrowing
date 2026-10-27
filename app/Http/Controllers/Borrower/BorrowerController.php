<?php

namespace App\Http\Controllers\Borrower;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Borrower;
use App\Support\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BorrowerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePermission('view-borrowers');
        $organisationId = (int) auth()->user()->organisation_id;
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');
        $tier   = $request->input('tier');

        $loanCounts = DB::table('loans')
            ->select('borrower_id')
            ->selectRaw('COUNT(*) as loans_count')
            ->selectRaw("SUM(CASE WHEN status IN ('active','overdue','defaulted') THEN 1 ELSE 0 END) as active_loans_count")
            ->where('organisation_id', $organisationId)
            ->groupBy('borrower_id');

        $outstanding = DB::table('loan_schedules')
            ->join('loans', function ($join) use ($organisationId) {
                $join->on('loans.id', '=', 'loan_schedules.loan_id')
                    ->where('loans.organisation_id', '=', $organisationId);
            })
            ->select('loans.borrower_id')
            ->selectRaw('SUM(CASE WHEN loan_schedules.total_due_cents > loan_schedules.total_paid_cents THEN loan_schedules.total_due_cents - loan_schedules.total_paid_cents ELSE 0 END) as outstanding_cents')
            ->groupBy('loans.borrower_id');

        $borrowers = DB::table('borrowers')
            ->join('users', function ($join) use ($organisationId) {
                $join->on('users.id', '=', 'borrowers.user_id')
                    ->where('users.organisation_id', '=', $organisationId);
            })
            ->leftJoin('borrower_tiers', function ($join) use ($organisationId) {
                $join->on('borrower_tiers.id', '=', 'borrowers.current_tier_id')
                    ->where('borrower_tiers.organisation_id', '=', $organisationId);
            })
            ->leftJoinSub($loanCounts, 'loan_counts', fn ($j) => $j->on('loan_counts.borrower_id', '=', 'borrowers.id'))
            ->leftJoinSub($outstanding, 'borrower_outstanding', fn ($j) => $j->on('borrower_outstanding.borrower_id', '=', 'borrowers.id'))
            ->where('borrowers.organisation_id', $organisationId)
            ->when(auth()->user()->hasRole(['loan-officer', 'supervisor']), fn ($q) =>
                $q->where('borrowers.branch_id', auth()->user()->branch_id ?? 0)
            )
            ->when($search !== '', fn ($q) => $q->where(fn ($q2) =>
                $q2->where('users.name', 'like', "%{$search}%")
                   ->orWhere('users.email', 'like', "%{$search}%")
                   ->orWhere('borrowers.phone_number', 'like', "%{$search}%")
                   ->orWhere('borrowers.id', 'like', "%{$search}%")
            ))
            ->when(in_array($status, ['active', 'frozen', 'blacklisted'], true), fn ($q) => $q->where('borrowers.account_status', $status))
            ->when(is_numeric($tier), fn ($q) => $q->where('borrowers.current_tier_id', (int) $tier))
            ->select([
                'borrowers.id', 'borrowers.phone_number', 'borrowers.account_status',
                'borrowers.current_tier_id', 'borrowers.lifetime_loans_count',
                'borrowers.consecutive_on_time_loans', 'borrowers.created_at',
                'users.name', 'users.email',
                'borrower_tiers.name as tier_name',
                'loan_counts.loans_count', 'loan_counts.active_loans_count',
                'borrower_outstanding.outstanding_cents',
            ])
            ->orderByDesc('borrowers.id')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total'       => DB::table('borrowers')->where('organisation_id', $organisationId)
                ->when(auth()->user()->hasRole(['loan-officer', 'supervisor']), fn ($q) => $q->where('branch_id', auth()->user()->branch_id ?? 0))->count(),
            'active'      => DB::table('borrowers')->where('organisation_id', $organisationId)->where('account_status', 'active')
                ->when(auth()->user()->hasRole(['loan-officer', 'supervisor']), fn ($q) => $q->where('branch_id', auth()->user()->branch_id ?? 0))->count(),
            'frozen'      => DB::table('borrowers')->where('organisation_id', $organisationId)->where('account_status', 'frozen')
                ->when(auth()->user()->hasRole(['loan-officer', 'supervisor']), fn ($q) => $q->where('branch_id', auth()->user()->branch_id ?? 0))->count(),
            'blacklisted' => DB::table('borrowers')->where('organisation_id', $organisationId)->where('account_status', 'blacklisted')
                ->when(auth()->user()->hasRole(['loan-officer', 'supervisor']), fn ($q) => $q->where('branch_id', auth()->user()->branch_id ?? 0))->count(),
        ];

        $tiers = DB::table('borrower_tiers')
            ->where('organisation_id', $organisationId)
            ->where('is_active', true)
            ->orderBy('tier_number')
            ->get(['id', 'tier_number', 'name']);

        return view('borrowers.index', compact('borrowers', 'stats', 'tiers', 'search', 'status', 'tier'));
    }

    public function create()
    {
        $this->authorizePermission('create-borrowers');
        $organisationId = (int) auth()->user()->organisation_id;
        $tiers = $this->activeTiers($organisationId);
        $branches = auth()->user()->hasRole(['admin', 'super-admin'])
            ? Branch::query()->where('is_active', true)->orderBy('name')->get()
            : collect();
        $financialScore = $this->initialFinancialScore(
            $organisationId,
            old('stmt_job_id'),
            old('stmt_file_key')
        );
        $recommendedTierId = $this->recommendedInitialTierId($tiers, $financialScore);

        return view('borrowers.create', compact('tiers', 'branches', 'financialScore', 'recommendedTierId'));
    }

    public function store(Request $request)
    {
        $this->authorizePermission('create-borrowers');
        $organisationId = (int) auth()->user()->organisation_id;
        $tiers = $this->activeTiers($organisationId);
        $financialScore = $this->initialFinancialScore(
            $organisationId,
            $request->input('stmt_job_id'),
            $request->input('stmt_file_key')
        );
        $request->merge([
            'current_tier_id' => $this->recommendedInitialTierId($tiers, $financialScore),
        ]);

        $data = $request->validate([
            // ── Step 1: Personal ──────────────────────────────────
            'name'                    => ['required', 'string', 'max:255'],
            'email'                   => ['nullable', 'email', 'max:255'],
            'phone_number'            => ['required', 'string', 'max:30'],
            'date_of_birth'           => ['required', 'date', 'before:-18 years'],
            'gender'                  => ['required', 'in:male,female,other'],
            'nationality'             => ['required', 'string', 'max:10'],
            'preferred_language'      => ['nullable', 'string', 'max:10'],

            // ── Step 2: KYC ───────────────────────────────────────
            'doc_type'                => ['required', 'in:national_id,passport,alien_id,driving_licence'],
            'doc_number'              => ['required', 'string', 'max:60'],
            'doc_country'             => ['required', 'string', 'max:10'],
            'verification_status'     => ['prohibited'],
            'verification_notes'      => ['nullable', 'string', 'max:1000'],
            // OCR-sourced / image fields (all optional — OCR may not run)
            'doc_file_key'            => ['nullable', 'string', 'max:500'],
            'kyc_full_name'           => ['nullable', 'string', 'max:255'],
            'kyc_dob'                 => ['nullable', 'date'],
            'kyc_expiry'              => ['nullable', 'date'],
            'ocr_raw_text'            => ['nullable', 'string', 'max:5000'],

            // ── Step 3: Financial / Employment ────────────────────
            'employment_type'         => ['required', 'in:salaried,self_employed,business,casual,unemployed,student'],
            'employer_name'           => ['nullable', 'string', 'max:255'],
            'job_title'               => ['nullable', 'string', 'max:255'],
            'monthly_income'          => ['required', 'numeric', 'min:0'],
            'monthly_expenses'        => ['required', 'numeric', 'min:0'],
            'existing_debt'           => ['nullable', 'numeric', 'min:0'],
            'other_income'            => ['nullable', 'numeric', 'min:0'],
            'residence_county'        => ['required', 'string', 'max:100'],
            'residence_address'       => ['required', 'string', 'max:500'],
            'housing_status'          => ['nullable', 'in:own,renting,family,other'],
            'years_at_address'        => ['nullable', 'integer', 'min:0', 'max:60'],
            // M-Pesa statement — optional, populated by StatementController::upload()
            'stmt_job_id'             => ['nullable', 'string', 'max:36'],
            'stmt_file_key'           => ['nullable', 'string', 'max:500'],

            // ── Step 4: Next of Kin ───────────────────────────────
            'next_of_kin_name'        => ['required', 'string', 'max:255'],
            'next_of_kin_relationship'=> ['required', 'string', 'max:50'],
            'next_of_kin_phone'       => ['required', 'string', 'max:30'],
            'next_of_kin_id_number'   => ['nullable', 'string', 'max:60'],
            'next_of_kin_email'       => ['nullable', 'email', 'max:255'],
            'next_of_kin_address'     => ['nullable', 'string', 'max:500'],
            'next_of_kin_notes'       => ['nullable', 'string', 'max:1000'],

            // ── Step 5: Lending ───────────────────────────────────
            'current_tier_id'         => ['required', 'integer'],
            'account_status'          => ['required', 'in:active,frozen,blacklisted'],
            'branch_id'               => auth()->user()->hasRole(['admin', 'super-admin'])
                ? ['nullable', 'integer', \Illuminate\Validation\Rule::exists('branches', 'id')
                    ->where('organisation_id', $organisationId)->where('is_active', true)]
                : ['prohibited'],
        ], [
            'date_of_birth.before'    => 'The borrower must be at least 18 years old.',
            'monthly_income.required' => 'Please enter the borrower\'s monthly income.',
            'monthly_expenses.required'=> 'Please enter the borrower\'s monthly expenses.',
            'years_at_address.max'    => 'Enter 60 years or fewer at the current address, or leave this optional field blank.',
            'years_at_address.min'    => 'Years at the current address cannot be negative.',
            'years_at_address.integer'=> 'Enter a whole number of years at the current address.',
        ]);

        $phone    = $this->normalisePhone($data['phone_number']);
        $nokPhone = $this->normalisePhone($data['next_of_kin_phone']);

        // ── Uniqueness checks ─────────────────────────────────────
        if (DB::table('borrowers')->where('organisation_id', $organisationId)->where('phone_number', $phone)->exists()) {
            return back()->withInput()->withErrors(['phone_number' => 'This phone number is already registered.']);
        }
        if ($data['email'] && DB::table('users')->where('organisation_id', $organisationId)->where('email', $data['email'])->exists()) {
            return back()->withInput()->withErrors(['email' => 'This email address is already registered.']);
        }
        if (!DB::table('borrower_tiers')->where('organisation_id', $organisationId)->where('id', $data['current_tier_id'])->where('is_active', true)->exists()) {
            return back()->withInput()->withErrors(['current_tier_id' => 'Select a valid borrower tier.']);
        }

        DB::transaction(function () use ($organisationId, $data, $phone, $nokPhone) {
            // 1. Create the linked user account
            $userId = DB::table('users')->insertGetId([
                'organisation_id' => $organisationId,
                'name'            => $data['name'],
                'email'           => $data['email'] ?: null,
                'password'        => Hash::make(Str::random(48)),
                'is_active'       => $data['account_status'] === 'active',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            // 2. Create the borrower profile
            $borrowerId = DB::table('borrowers')->insertGetId([
                'organisation_id'           => $organisationId,
                'branch_id'                 => array_key_exists('branch_id', $data)
                    ? ($data['branch_id'] ?: null)
                    : auth()->user()->branch_id,
                'registered_by'             => auth()->id(),
                'user_id'                   => $userId,
                'phone_number'              => $phone,
                'account_status'            => $data['account_status'],
                'current_tier_id'           => $data['current_tier_id'],
                'lifetime_loans_count'      => 0,
                'successful_loans_count'    => 0,
                'on_time_loans_count'       => 0,
                'consecutive_on_time_loans' => 0,
                'late_loans_count'          => 0,
                'defaulted_loans_count'     => 0,
                'behaviour_score'           => 0,
                'registered_at'             => now(),
                'created_at'                => now(),
                'updated_at'                => now(),
            ]);

            // 3. Create the initial KYC record
            // Prefer the OCR-extracted name/DOB when available; fall back to Step-1 values.
            $kycFullName = !empty($data['kyc_full_name'])
                ? trim($data['kyc_full_name'])
                : $data['name'];

            $kycDob = !empty($data['kyc_dob'])
                ? $data['kyc_dob']
                : ($data['date_of_birth'] ?? null);

            // Build a verification_snapshot from OCR data for audit purposes
            $ocrSnapshot = null;
            if (!empty($data['doc_file_key']) || !empty($data['ocr_raw_text'])) {
                $ocrSnapshot = [
                    'provider'          => 'openai_gpt4o',
                    'file_key'          => $data['doc_file_key'] ?? null,
                    'raw_text'          => $data['ocr_raw_text'] ?? null,
                    'extracted_at'      => now()->toIso8601String(),
                    'kyc_expiry_date'   => $data['kyc_expiry'] ?? null,
                ];
            }

            DB::table('kyc_records')->insert([
                'organisation_id'       => $organisationId,
                'borrower_id'           => $borrowerId,
                'document_type'         => $data['doc_type'],
                'document_number'       => strtoupper(trim($data['doc_number'])),
                'document_country_code' => strtoupper(substr($data['doc_country'], 0, 2)),
                'full_name'             => $kycFullName,
                'date_of_birth'         => $kycDob,
                'verification_status'   => 'pending',
                'verification_provider' => !empty($data['doc_file_key']) ? 'openai_gpt4o' : null,
                'verification_notes'    => $data['verification_notes'] ?: null,
                'document_file_key'     => $data['doc_file_key'] ?: null,
                'verification_snapshot' => $ocrSnapshot ? json_encode($ocrSnapshot) : null,
                'is_current'            => true,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);

            // 4. Persist supplementary profile + financial pipeline data
            $monthlyIncomeCents   = (int) round(($data['monthly_income']   ?? 0) * 100);
            $monthlyExpenseCents  = (int) round(($data['monthly_expenses'] ?? 0) * 100);
            $existingDebtCents    = (int) round(($data['existing_debt']    ?? 0) * 100);
            $otherIncomeCents     = (int) round(($data['other_income']     ?? 0) * 100);

            // Attempt to pull richer profile from the M-Pesa statement cache
            $stmtJobId  = $data['stmt_job_id']  ?? null;
            $stmtFileKey= $data['stmt_file_key'] ?? null;
            $stmtCache  = $stmtJobId ? \Illuminate\Support\Facades\Cache::get('mpesa_stmt_' . $stmtJobId) : null;

            // 4a. Create statement_documents row if a statement was uploaded
            $statementDocId = null;
            if ($stmtFileKey && $stmtCache && ($stmtCache['success'] ?? false)) {
                $statementDocId = DB::table('statement_documents')->insertGetId([
                    'organisation_id'        => $organisationId,
                    'borrower_id'            => $borrowerId,
                    'document_type'          => 'mpesa_statement',
                    'provider'               => 'mpesa',
                    'file_key'               => $stmtFileKey,
                    'original_filename'      => $stmtCache['original_filename'] ?? null,
                    'mime_type'              => $stmtCache['mime_type']         ?? 'application/pdf',
                    'file_size_bytes'        => $stmtCache['file_size_bytes']   ?? null,
                    'file_hash'              => $stmtCache['file_hash']         ?? null,
                    'statement_period_start' => $stmtCache['period_start']      ?? null,
                    'statement_period_end'   => $stmtCache['period_end']        ?? null,
                    'processing_status'      => 'completed',
                    'uploaded_at'            => now(),
                    'processed_at'           => now(),
                    'transaction_count'      => $stmtCache['transaction_count'] ?? 0,
                    'document_metadata'      => json_encode(['source' => 'wizard_upload']),
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);

                // 4b. Bulk-insert classified transactions
                $transactions = $stmtCache['transactions'] ?? [];
                foreach (array_chunk($transactions, 200) as $chunk) {
                    $rows = [];
                    foreach ($chunk as $tx) {
                        $rows[] = [
                            'organisation_id'          => $organisationId,
                            'statement_document_id'    => $statementDocId,
                            'borrower_id'              => $borrowerId,
                            'source_transaction_id'    => $tx['receipt_no']    ?? null,
                            'transaction_at'           => ($tx['date'] ?? date('Y-m-d')) . ' ' . ($tx['time'] ?? '00:00:00'),
                            'transaction_type'         => ($tx['amount_cents'] ?? 0) > 0 ? 'credit' : 'debit',
                            'description'              => substr($tx['description'] ?? '', 0, 500),
                            'amount_cents'             => $tx['amount_cents']   ?? 0,
                            'currency'                 => 'KES',
                            'balance_after_cents'      => $tx['balance_cents']  ?? null,
                            'classification'           => $tx['classification'] ?? 'other',
                            'classification_confidence'=> 0.85,
                            'classifier_version'       => 'rule-based-v1',
                            'is_income'                => ($tx['is_income']       ?? false) ? 1 : 0,
                            'is_expense'               => ($tx['is_expense']      ?? false) ? 1 : 0,
                            'is_transfer'              => ($tx['is_transfer']     ?? false) ? 1 : 0,
                            'is_loan_related'          => ($tx['is_loan_related'] ?? false) ? 1 : 0,
                            'is_repayment'             => ($tx['is_repayment']    ?? false) ? 1 : 0,
                            'classification_snapshot'  => null,
                            'created_at'               => now(),
                            'updated_at'               => now(),
                        ];
                    }
                    if (!empty($rows)) {
                        DB::table('transaction_classifications')->insert($rows);
                    }
                }

                // 4c. Create the financial_analysis_run
                $profile = $stmtCache['profile'] ?? [];
                $runId   = DB::table('financial_analysis_runs')->insertGetId([
                    'organisation_id'               => $organisationId,
                    'borrower_id'                   => $borrowerId,
                    'statement_document_id'         => $statementDocId,
                    'run_type'                      => 'initial_appraisal',
                    'status'                        => 'completed',
                    'model_version'                 => 'mpesa-parser-v1',
                    'parser_version'                => 'mpesa-parser-v1',
                    'classifier_version'            => 'rule-based-v1',
                    'started_at'                    => now(),
                    'completed_at'                  => now(),
                    'transaction_count'             => $stmtCache['transaction_count'] ?? 0,
                    'classified_transaction_count'  => $stmtCache['transaction_count'] ?? 0,
                    'financial_score'               => $profile['financial_score']     ?? null,
                    'input_snapshot'                => json_encode([
                        'period_start' => $stmtCache['period_start'] ?? null,
                        'period_end'   => $stmtCache['period_end']   ?? null,
                    ]),
                    'result_snapshot'               => json_encode($profile),
                    'request_reference'             => $stmtJobId,
                    'created_at'                    => now(),
                    'updated_at'                    => now(),
                ]);

                // 4d. Create financial_profile using statement-derived values
                $netCashflow       = ($profile['average_monthly_income_cents']  ?? 0)
                                   - ($profile['average_monthly_expense_cents'] ?? 0);
                $discretionary     = max(0, $netCashflow - ($profile['average_monthly_debt_service_cents'] ?? 0));

                DB::table('financial_profiles')->insert([
                    'organisation_id'                              => $organisationId,
                    'borrower_id'                                  => $borrowerId,
                    'financial_analysis_run_id'                    => $runId,
                    'currency'                                     => 'KES',
                    'average_monthly_income_cents'                 => $profile['average_monthly_income_cents']          ?? $monthlyIncomeCents,
                    'average_monthly_expense_cents'                => $profile['average_monthly_expense_cents']         ?? $monthlyExpenseCents,
                    'average_monthly_net_cashflow_cents'           => $netCashflow,
                    'average_monthly_salary_income_cents'          => $profile['average_monthly_salary_income_cents']   ?? 0,
                    'average_monthly_business_income_cents'        => $profile['average_monthly_business_income_cents'] ?? 0,
                    'average_monthly_other_income_cents'           => $profile['average_monthly_other_income_cents']    ?? 0,
                    'average_monthly_debt_service_cents'           => $profile['average_monthly_debt_service_cents']    ?? $existingDebtCents,
                    'average_monthly_discretionary_cashflow_cents' => $discretionary,
                    'income_stability_score'                       => $profile['income_stability_score']  ?? null,
                    'cashflow_score'                               => $profile['cashflow_score']          ?? null,
                    'financial_score'                              => $profile['financial_score']         ?? null,
                    'income_volatility'                            => $profile['income_volatility']       ?? null,
                    'expense_volatility'                           => $profile['expense_volatility']      ?? null,
                    'debt_service_ratio'                           => $profile['debt_service_ratio']      ?? null,
                    'analysis_months'                              => $profile['analysis_months']         ?? 0,
                    'transaction_count'                            => $stmtCache['transaction_count']     ?? 0,
                    'model_version'                                => 'mpesa-parser-v1',
                    'profile_snapshot'                             => json_encode([
                        'monthly'      => $stmtCache['monthly']      ?? [],
                        'period_start' => $stmtCache['period_start'] ?? null,
                        'period_end'   => $stmtCache['period_end']   ?? null,
                    ]),
                    'is_current'                                   => true,
                    'calculated_at'                                => now(),
                    'created_at'                                   => now(),
                    'updated_at'                                   => now(),
                ]);

                // Consume the cache entry so it can't be reused
                \Illuminate\Support\Facades\Cache::forget('mpesa_stmt_' . $stmtJobId);

            } else {
                // No statement — create a declared-only financial profile
                $netCashflow = $monthlyIncomeCents - $monthlyExpenseCents;
                DB::table('financial_profiles')->insert([
                    'organisation_id'                              => $organisationId,
                    'borrower_id'                                  => $borrowerId,
                    'financial_analysis_run_id'                    => null,
                    'currency'                                     => 'KES',
                    'average_monthly_income_cents'                 => $monthlyIncomeCents,
                    'average_monthly_expense_cents'                => $monthlyExpenseCents,
                    'average_monthly_net_cashflow_cents'           => $netCashflow,
                    'average_monthly_salary_income_cents'          => 0,
                    'average_monthly_business_income_cents'        => 0,
                    'average_monthly_other_income_cents'           => $otherIncomeCents,
                    'average_monthly_debt_service_cents'           => $existingDebtCents,
                    'average_monthly_discretionary_cashflow_cents' => max(0, $netCashflow - $existingDebtCents),
                    'income_stability_score'                       => null,
                    'cashflow_score'                               => null,
                    'financial_score'                              => null,
                    'income_volatility'                            => null,
                    'expense_volatility'                           => null,
                    'debt_service_ratio'                           => $monthlyIncomeCents > 0
                        ? round($existingDebtCents / $monthlyIncomeCents, 6) : null,
                    'analysis_months'                              => 0,
                    'transaction_count'                            => 0,
                    'model_version'                                => 'declared-v1',
                    'profile_snapshot'                             => json_encode(['source' => 'self_declared']),
                    'is_current'                                   => true,
                    'calculated_at'                                => now(),
                    'created_at'                                   => now(),
                    'updated_at'                                   => now(),
                ]);
            }

            // 5. Persist supplementary meta as a system_event for audit trail
            $meta = [
                'branch_id'            => array_key_exists('branch_id', $data)
                    ? ($data['branch_id'] ?: null)
                    : auth()->user()->branch_id,
                'registered_by'        => auth()->id(),
                'gender'               => $data['gender'],
                'nationality'          => $data['nationality'],
                'preferred_language'   => $data['preferred_language'] ?? null,
                'employment_type'      => $data['employment_type'],
                'employer_name'        => $data['employer_name']      ?? null,
                'job_title'            => $data['job_title']          ?? null,
                'monthly_income_cents' => $monthlyIncomeCents,
                'monthly_expenses_cents'=> $monthlyExpenseCents,
                'existing_debt_cents'  => $existingDebtCents,
                'other_income_cents'   => $otherIncomeCents,
                'residence_county'     => $data['residence_county'],
                'residence_address'    => $data['residence_address'],
                'housing_status'       => $data['housing_status']     ?? null,
                'years_at_address'     => $data['years_at_address']   ?? null,
                'statement_doc_id'     => $statementDocId,
                'next_of_kin' => [
                    'name'         => $data['next_of_kin_name'],
                    'relationship' => $data['next_of_kin_relationship'],
                    'phone'        => $nokPhone,
                    'id_number'    => $data['next_of_kin_id_number'] ?? null,
                    'email'        => $data['next_of_kin_email']     ?? null,
                    'address'      => $data['next_of_kin_address']   ?? null,
                    'notes'        => $data['next_of_kin_notes']     ?? null,
                ],
            ];

            // Persist as a system event (audit trail)
            DB::table('system_events')->insert([
                'organisation_id' => $organisationId,
                'event_type'      => 'borrower_profile_created',
                'event_id'        => (string) Str::uuid(),
                'idempotency_key' => 'bpc-' . $borrowerId,
                'aggregate_type'  => 'borrower',
                'aggregate_id'    => $borrowerId,
                'payload'         => json_encode($meta),
                'status'          => 'processed',
                'processed_at'    => now(),
                'occurred_at'     => now(),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        });

        return redirect()->route('borrowers.index')
            ->with('success', 'Borrower profile created successfully.');
    }

    private function activeTiers(int $organisationId)
    {
        return DB::table('borrower_tiers')
            ->where('organisation_id', $organisationId)
            ->where('is_active', true)
            ->orderBy('tier_number')
            ->get([
                'id',
                'tier_number',
                'name',
                'minimum_financial_score',
                'minimum_behaviour_score',
                'minimum_successful_loans',
                'minimum_on_time_loans',
                'minimum_consecutive_on_time_loans',
                'is_entry_tier',
            ]);
    }

    private function initialFinancialScore(int $organisationId, mixed $jobId, mixed $fileKey): ?float
    {
        if (!is_string($jobId)
            || !is_string($fileKey)
            || $jobId === ''
            || !str_starts_with($fileKey, "statements/{$organisationId}/")) {
            return null;
        }

        $analysis = \Illuminate\Support\Facades\Cache::get('mpesa_stmt_' . $jobId);
        if (!is_array($analysis)
            || empty($analysis['success'])
            || ($analysis['file_key'] ?? null) !== $fileKey) {
            return null;
        }

        $score = $analysis['profile']['financial_score'] ?? null;
        if (!is_numeric($score) || !is_finite((float) $score) || $score < 0 || $score > 1) {
            return null;
        }

        return (float) $score;
    }

    private function recommendedInitialTierId($tiers, ?float $financialScore): ?int
    {
        if ($financialScore !== null) {
            $eligibleTiers = $tiers->filter(function ($tier) use ($financialScore) {
                $minimumFinancial = $tier->minimum_financial_score;

                return ($minimumFinancial !== null && $financialScore >= (float) $minimumFinancial)
                    && (float) $tier->minimum_behaviour_score <= 0
                    && (int) $tier->minimum_successful_loans === 0
                    && (int) $tier->minimum_on_time_loans === 0
                    && (int) $tier->minimum_consecutive_on_time_loans === 0;
            });

            if ($eligibleTiers->isNotEmpty()) {
                return (int) $eligibleTiers->last()->id;
            }
        }

        $entryTier = $tiers->first(fn ($tier) => (bool) $tier->is_entry_tier);

        return $entryTier ? (int) $entryTier->id : ($tiers->first() ? (int) $tiers->first()->id : null);
    }

    public function show(int $borrower)
    {
        $this->authorizePermission('view-borrowers');
        $organisationId = (int) auth()->user()->organisation_id;

        $borrowerRecord = DB::table('borrowers')
            ->join('users', 'users.id', '=', 'borrowers.user_id')
            ->leftJoin('borrower_tiers', function ($join) use ($organisationId) {
                $join->on('borrower_tiers.id', '=', 'borrowers.current_tier_id')
                    ->where('borrower_tiers.organisation_id', '=', $organisationId);
            })
            ->where('borrowers.organisation_id', $organisationId)
            ->where('borrowers.id', $borrower)
            ->when(auth()->user()->hasRole(['loan-officer', 'supervisor']), fn ($q) =>
                $q->where('borrowers.branch_id', auth()->user()->branch_id ?? 0)
            )
            ->select('borrowers.*', 'users.name', 'users.email', 'borrower_tiers.name as tier_name', 'borrower_tiers.max_loan_amount_cents', 'borrower_tiers.min_loan_amount_cents', 'borrower_tiers.max_term_days', 'borrower_tiers.tier_number')
            ->firstOrFail();

        // Fetch KYC record (may not exist for older borrowers)
        try {
            $kycRecord = DB::table('kyc_records')
                ->where('organisation_id', $organisationId)
                ->where('borrower_id', $borrower)
                ->where('is_current', true)
                ->first();
        } catch (\Exception $e) {
            $kycRecord = null;
        }

        // Fetch financial profile (may not exist for older borrowers)
        try {
            $financialProfile = DB::table('financial_profiles')
                ->where('organisation_id', $organisationId)
                ->where('borrower_id', $borrower)
                ->where('is_current', true)
                ->first();
        } catch (\Exception $e) {
            $financialProfile = null;
        }

        // Fetch additional profile data from system_events (may not exist for older borrowers)
        try {
            $profileData = DB::table('system_events')
                ->where('organisation_id', $organisationId)
                ->where('aggregate_type', 'borrower')
                ->where('aggregate_id', $borrower)
                ->where('event_type', 'borrower_profile_created')
                ->where('status', 'processed')
                ->orderByDesc('occurred_at')
                ->first();
        } catch (\Exception $e) {
            $profileData = null;
        }

        $loans = DB::table('loans')
            ->leftJoin('loan_products', 'loan_products.id', '=', 'loans.loan_product_id')
            ->where('loans.organisation_id', $organisationId)
            ->where('loans.borrower_id', $borrower)
            ->orderByDesc('loans.id')
            ->get(['loans.id', 'loans.principal_cents', 'loans.total_payable_cents', 'loans.status', 'loans.disbursed_at', 'loans.matures_at', 'loan_products.name as product_name']);

        $applications = DB::table('loan_applications')
            ->leftJoin('loan_products', 'loan_products.id', '=', 'loan_applications.loan_product_id')
            ->where('loan_applications.organisation_id', $organisationId)
            ->where('loan_applications.borrower_id', $borrower)
            ->orderByDesc('loan_applications.id')
            ->limit(10)
            ->get(['loan_applications.id', 'loan_applications.requested_amount_cents', 'loan_applications.approved_amount_cents', 'loan_applications.status', 'loan_applications.decision', 'loan_applications.submitted_at', 'loan_products.name as product_name']);

        $repayments = DB::table('loan_payments')
            ->join('loans', 'loans.id', '=', 'loan_payments.loan_id')
            ->where('loan_payments.organisation_id', $organisationId)
            ->where('loans.borrower_id', $borrower)
            ->where('loan_payments.status', 'posted')
            ->orderByDesc('loan_payments.received_at')
            ->limit(10)
            ->get(['loan_payments.id', 'loan_payments.amount_cents', 'loan_payments.payment_method', 'loan_payments.mpesa_receipt_number', 'loan_payments.received_at', 'loans.id as loan_id']);

        if (request()->wantsJson()) {
            return response()->json([
                'id' => $borrowerRecord->id,
                'name' => $borrowerRecord->name,
                'email' => $borrowerRecord->email,
                'phone_number' => $borrowerRecord->phone_number,
                'account_status' => $borrowerRecord->account_status,
                'current_tier_id' => $borrowerRecord->current_tier_id,
                'tier_name' => $borrowerRecord->tier_name,
                'tier_number' => $borrowerRecord->tier_number,
                'max_loan_amount_cents' => $borrowerRecord->max_loan_amount_cents,
                'min_loan_amount_cents' => $borrowerRecord->min_loan_amount_cents,
                'max_term_days' => $borrowerRecord->max_term_days,
                'behaviour_score' => $borrowerRecord->behaviour_score,
                'lifetime_loans_count' => $borrowerRecord->lifetime_loans_count,
                'successful_loans_count' => $borrowerRecord->successful_loans_count,
                'on_time_loans_count' => $borrowerRecord->on_time_loans_count,
                'consecutive_on_time_loans' => $borrowerRecord->consecutive_on_time_loans,
                'late_loans_count' => $borrowerRecord->late_loans_count,
                'defaulted_loans_count' => $borrowerRecord->defaulted_loans_count,
                'registered_at' => $borrowerRecord->registered_at,
                'created_at' => $borrowerRecord->created_at,
                'kyc_record' => $kycRecord,
                'financial_profile' => $financialProfile,
                'profile_data' => $profileData ? json_decode($profileData->payload, true) : null,
                'loans' => $loans,
                'applications' => $applications,
                'repayments' => $repayments
            ]);
        }

        return view('borrowers.show', compact('borrowerRecord', 'kycRecord', 'financialProfile', 'profileData', 'loans', 'applications', 'repayments'));
    }

    public function edit(int $borrower)
    {
        $this->authorizePermission('edit-borrowers');
        $organisationId = (int) auth()->user()->organisation_id;

        $borrowerRecord = DB::table('borrowers')
            ->join('users', 'users.id', '=', 'borrowers.user_id')
            ->leftJoin('borrower_tiers', function ($join) use ($organisationId) {
                $join->on('borrower_tiers.id', '=', 'borrowers.current_tier_id')
                    ->where('borrower_tiers.organisation_id', '=', $organisationId);
            })
            ->where('borrowers.organisation_id', $organisationId)
            ->where('borrowers.id', $borrower)
            ->when(auth()->user()->hasRole(['loan-officer', 'supervisor']), fn ($q) =>
                $q->where('borrowers.branch_id', auth()->user()->branch_id ?? 0)
            )
            ->select('borrowers.*', 'users.name', 'users.email', 'borrower_tiers.name as tier_name', 'borrower_tiers.max_loan_amount_cents', 'borrower_tiers.min_loan_amount_cents', 'borrower_tiers.max_term_days', 'borrower_tiers.tier_number')
            ->firstOrFail();

        // Fetch KYC record (may not exist for older borrowers)
        try {
            $kycRecord = DB::table('kyc_records')
                ->where('organisation_id', $organisationId)
                ->where('borrower_id', $borrower)
                ->where('is_current', true)
                ->first();
        } catch (\Exception $e) {
            $kycRecord = null;
        }

        // Fetch financial profile (may not exist for older borrowers)
        try {
            $financialProfile = DB::table('financial_profiles')
                ->where('organisation_id', $organisationId)
                ->where('borrower_id', $borrower)
                ->where('is_current', true)
                ->first();
        } catch (\Exception $e) {
            $financialProfile = null;
        }

        // Fetch additional profile data from system_events (may not exist for older borrowers)
        try {
            $profileData = DB::table('system_events')
                ->where('organisation_id', $organisationId)
                ->where('aggregate_type', 'borrower')
                ->where('aggregate_id', $borrower)
                ->where('event_type', 'borrower_profile_created')
                ->where('status', 'processed')
                ->orderByDesc('occurred_at')
                ->first();
        } catch (\Exception $e) {
            $profileData = null;
        }

        $tiers = DB::table('borrower_tiers')
            ->where('organisation_id', $organisationId)
            ->where('is_active', true)
            ->orderBy('tier_number')
            ->get(['id', 'tier_number', 'name']);

        if (request()->wantsJson()) {
            return response()->json([
                'id' => $borrowerRecord->id,
                'name' => $borrowerRecord->name,
                'email' => $borrowerRecord->email,
                'phone_number' => $borrowerRecord->phone_number,
                'account_status' => $borrowerRecord->account_status,
                'current_tier_id' => $borrowerRecord->current_tier_id,
                'tier_name' => $borrowerRecord->tier_name,
                'tier_number' => $borrowerRecord->tier_number,
                'max_loan_amount_cents' => $borrowerRecord->max_loan_amount_cents,
                'min_loan_amount_cents' => $borrowerRecord->min_loan_amount_cents,
                'max_term_days' => $borrowerRecord->max_term_days,
                'behaviour_score' => $borrowerRecord->behaviour_score,
                'lifetime_loans_count' => $borrowerRecord->lifetime_loans_count,
                'successful_loans_count' => $borrowerRecord->successful_loans_count,
                'on_time_loans_count' => $borrowerRecord->on_time_loans_count,
                'consecutive_on_time_loans' => $borrowerRecord->consecutive_on_time_loans,
                'late_loans_count' => $borrowerRecord->late_loans_count,
                'defaulted_loans_count' => $borrowerRecord->defaulted_loans_count,
                'registered_at' => $borrowerRecord->registered_at,
                'created_at' => $borrowerRecord->created_at,
                'kyc_record' => $kycRecord,
                'financial_profile' => $financialProfile,
                'profile_data' => $profileData ? json_decode($profileData->payload, true) : null,
            ]);
        }

        $branches = auth()->user()->hasRole(['admin', 'super-admin'])
            ? Branch::query()->orderBy('name')->get()
            : collect();

        return view('borrowers.edit', compact('borrowerRecord', 'kycRecord', 'financialProfile', 'profileData', 'tiers', 'branches'));
    }

    public function update(Request $request, int $borrower)
    {
        $this->authorizePermission('edit-borrowers');
        $organisationId = (int) auth()->user()->organisation_id;

        $borrowerRecord = DB::table('borrowers')
            ->join('users', 'users.id', '=', 'borrowers.user_id')
            ->where('borrowers.organisation_id', $organisationId)
            ->where('borrowers.id', $borrower)
            ->when(auth()->user()->hasRole(['loan-officer', 'supervisor']), fn ($q) =>
                $q->where('borrowers.branch_id', auth()->user()->branch_id ?? 0)
            )
            ->select('borrowers.*', 'users.id as user_id')
            ->firstOrFail();
        $previousBranchId = $borrowerRecord->branch_id;

        $data = $request->validate([
            'name'                    => ['required', 'string', 'max:255'],
            'email'                   => ['nullable', 'email', 'max:255'],
            'phone_number'            => ['required', 'string', 'max:30'],
            'gender'                  => ['nullable', 'in:male,female,other'],
            'nationality'             => ['nullable', 'string', 'max:10'],
            'preferred_language'      => ['nullable', 'string', 'max:10'],
            'employment_type'         => ['nullable', 'in:salaried,self_employed,business,casual,unemployed,student'],
            'employer_name'           => ['nullable', 'string', 'max:255'],
            'job_title'               => ['nullable', 'string', 'max:255'],
            'monthly_income'          => ['nullable', 'numeric', 'min:0'],
            'monthly_expenses'        => ['nullable', 'numeric', 'min:0'],
            'existing_debt'           => ['nullable', 'numeric', 'min:0'],
            'other_income'            => ['nullable', 'numeric', 'min:0'],
            'residence_county'        => ['required', 'string', 'max:100'],
            'residence_address'       => ['required', 'string', 'max:500'],
            'housing_status'          => ['nullable', 'in:own,renting,family,other'],
            'years_at_address'        => ['nullable', 'integer', 'min:0', 'max:60'],
            'next_of_kin_name'        => ['required', 'string', 'max:255'],
            'next_of_kin_relationship'=> ['required', 'string', 'max:50'],
            'next_of_kin_phone'       => ['required', 'string', 'max:30'],
            'next_of_kin_id_number'   => ['nullable', 'string', 'max:60'],
            'next_of_kin_email'       => ['nullable', 'email', 'max:255'],
            'next_of_kin_address'     => ['nullable', 'string', 'max:500'],
            'next_of_kin_notes'       => ['nullable', 'string', 'max:1000'],
            'current_tier_id'         => ['required', 'integer'],
            'account_status'          => ['required', 'in:active,frozen,blacklisted'],
            'branch_id'               => auth()->user()->hasRole(['admin', 'super-admin'])
                ? ['nullable', 'integer', \Illuminate\Validation\Rule::exists('branches', 'id')->where('organisation_id', $organisationId)]
                : ['prohibited'],
            'doc_type'                => ['nullable', 'in:national_id,passport,alien_id,driving_licence'],
            'doc_number'              => ['nullable', 'string', 'max:100'],
            'doc_country'             => ['nullable', 'string', 'max:10'],
            'kyc_full_name'           => ['nullable', 'string', 'max:255'],
            'kyc_dob'                 => ['nullable', 'date'],
            'kyc_expiry'              => ['nullable', 'date'],
            'verification_status'     => ['prohibited'],
            'verification_notes'      => ['nullable', 'string', 'max:1000'],
            'doc_file_key'            => ['nullable', 'string', 'max:500'],
            'ocr_raw_text'            => ['nullable', 'string', 'max:5000'],
        ]);

        $phone    = $this->normalisePhone($data['phone_number']);
        $nokPhone = $this->normalisePhone($data['next_of_kin_phone']);

        if (DB::table('borrowers')->where('organisation_id', $organisationId)->where('phone_number', $phone)->where('id', '!=', $borrower)->exists()) {
            if (request()->wantsJson()) return response()->json(['success'=>false,'errors'=>['phone_number'=>['This phone number is already registered for another borrower.']]], 422);
            return back()->withInput()->withErrors(['phone_number' => 'This phone number is already registered for another borrower.']);
        }
        if ($data['email'] && DB::table('users')->where('organisation_id', $organisationId)->where('email', $data['email'])->where('id', '!=', $borrowerRecord->user_id)->exists()) {
            if (request()->wantsJson()) return response()->json(['success'=>false,'errors'=>['email'=>['This email address is already registered.']]], 422);
            return back()->withInput()->withErrors(['email' => 'This email address is already registered.']);
        }

        DB::transaction(function () use ($organisationId, $borrower, $borrowerRecord, $data, $phone, $nokPhone) {
            // Update user
            DB::table('users')->where('organisation_id', $organisationId)->where('id', $borrowerRecord->user_id)->update([
                'name' => $data['name'], 'email' => $data['email'] ?: null,
                'is_active' => $data['account_status'] === 'active', 'updated_at' => now(),
            ]);

            // Update borrower
            DB::table('borrowers')->where('organisation_id', $organisationId)->where('id', $borrower)->update([
                'phone_number'   => $phone,
                'account_status' => $data['account_status'],
                'current_tier_id'=> $data['current_tier_id'],
                ...(array_key_exists('branch_id', $data) ? ['branch_id' => $data['branch_id'] ?: null] : []),
                'updated_at'     => now(),
            ]);

            // Update KYC record if exists. A missing file key means the existing
            // evidence should remain unchanged.
            $kycRecord = DB::table('kyc_records')
                ->where('organisation_id', $organisationId)
                ->where('borrower_id', $borrower)
                ->where('is_current', true)
                ->first();

            if ($kycRecord) {
                $kycUpdate = [
                    'document_type'         => $data['doc_type'] ?? $kycRecord->document_type,
                    'document_number'       => array_key_exists('doc_number', $data) ? ($data['doc_number'] ?: null) : $kycRecord->document_number,
                    'document_country_code' => !empty($data['doc_country']) ? strtoupper(substr($data['doc_country'], 0, 2)) : $kycRecord->document_country_code,
                    'full_name'             => $data['kyc_full_name'] ?? $data['name'],
                    'date_of_birth'         => $data['kyc_dob'] ?? $kycRecord->date_of_birth,
                    'verification_status'   => $data['verification_status'] ?? $kycRecord->verification_status,
                    'verification_notes'    => array_key_exists('verification_notes', $data) ? ($data['verification_notes'] ?: null) : $kycRecord->verification_notes,
                    'updated_at'            => now(),
                ];
                $identityEvidenceChanged = !empty($data['doc_file_key'])
                    || (isset($data['doc_type']) && $data['doc_type'] !== $kycRecord->document_type)
                    || (array_key_exists('doc_number', $data) && ($data['doc_number'] ?: null) !== $kycRecord->document_number)
                    || (!empty($data['doc_country']) && strtoupper(substr($data['doc_country'], 0, 2)) !== $kycRecord->document_country_code)
                    || (isset($data['kyc_full_name']) && $data['kyc_full_name'] !== $kycRecord->full_name)
                    || (isset($data['kyc_dob']) && $data['kyc_dob'] !== $kycRecord->date_of_birth);
                if ($identityEvidenceChanged) {
                    $kycUpdate['verification_status'] = 'pending';
                    $kycUpdate['verified_at'] = null;
                    $kycUpdate['verification_provider'] = null;
                    $kycUpdate['document_file_key'] = null;
                    $kycUpdate['verification_snapshot'] = null;
                }

                if (!empty($data['doc_file_key'])) {
                    $kycUpdate['document_file_key'] = $data['doc_file_key'];
                    $kycUpdate['verification_provider'] = 'openai_gpt4o';
                    $kycUpdate['verification_snapshot'] = json_encode([
                        'provider'        => 'openai_gpt4o',
                        'file_key'        => $data['doc_file_key'],
                        'raw_text'        => $data['ocr_raw_text'] ?? null,
                        'extracted_at'    => now()->toIso8601String(),
                        'kyc_expiry_date' => $data['kyc_expiry'] ?? null,
                    ]);
                }

                DB::table('kyc_records')
                    ->where('organisation_id', $organisationId)
                    ->where('borrower_id', $borrower)
                    ->where('is_current', true)
                    ->update($kycUpdate);
            } else {
                // Create KYC record if doesn't exist
                DB::table('kyc_records')->insert([
                    'organisation_id'       => $organisationId,
                    'borrower_id'           => $borrower,
                    'document_type'         => $data['doc_type'] ?? 'national_id',
                    'document_number'       => $data['doc_number'] ?? null,
                    'document_country_code' => strtoupper(substr($data['doc_country'] ?? 'KE', 0, 2)),
                    'full_name'             => $data['kyc_full_name'] ?? $data['name'],
                    'date_of_birth'        => $data['kyc_dob'] ?? null,
                    'verification_status'   => 'pending',
                    'verification_notes'    => $data['verification_notes'] ?? null,
                    'document_file_key'     => $data['doc_file_key'] ?? null,
                    'verification_provider' => !empty($data['doc_file_key']) ? 'openai_gpt4o' : null,
                    'verification_snapshot' => !empty($data['doc_file_key']) ? json_encode([
                        'provider'        => 'openai_gpt4o',
                        'file_key'        => $data['doc_file_key'],
                        'raw_text'        => $data['ocr_raw_text'] ?? null,
                        'extracted_at'    => now()->toIso8601String(),
                        'kyc_expiry_date' => $data['kyc_expiry'] ?? null,
                    ]) : null,
                    'is_current'            => true,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            }

            // Update financial profile if exists
            $financialProfile = DB::table('financial_profiles')
                ->where('organisation_id', $organisationId)
                ->where('borrower_id', $borrower)
                ->where('is_current', true)
                ->first();

            if ($financialProfile) {
                $monthlyIncomeCents   = (int) round(($data['monthly_income']   ?? 0) * 100);
                $monthlyExpenseCents  = (int) round(($data['monthly_expenses'] ?? 0) * 100);
                $existingDebtCents    = (int) round(($data['existing_debt']    ?? 0) * 100);
                $otherIncomeCents     = (int) round(($data['other_income']     ?? 0) * 100);

                DB::table('financial_profiles')
                    ->where('organisation_id', $organisationId)
                    ->where('borrower_id', $borrower)
                    ->where('is_current', true)
                    ->update([
                        'average_monthly_income_cents'                 => $monthlyIncomeCents,
                        'average_monthly_expense_cents'                => $monthlyExpenseCents,
                        'average_monthly_net_cashflow_cents'           => $monthlyIncomeCents - $monthlyExpenseCents,
                        'average_monthly_other_income_cents'           => $otherIncomeCents,
                        'average_monthly_debt_service_cents'           => $existingDebtCents,
                        'average_monthly_discretionary_cashflow_cents' => max(0, ($monthlyIncomeCents - $monthlyExpenseCents) - $existingDebtCents),
                        'updated_at'                                   => now(),
                    ]);
            }

            // Update system events record
            $meta = [
                'branch_id'            => array_key_exists('branch_id', $data)
                    ? ($data['branch_id'] ?: null)
                    : $borrowerRecord->branch_id,
                'registered_by'        => $borrowerRecord->registered_by,
                'gender'               => $data['gender'] ?? null,
                'nationality'          => $data['nationality'] ?? null,
                'preferred_language'   => $data['preferred_language'] ?? null,
                'employment_type'      => $data['employment_type'] ?? null,
                'employer_name'        => $data['employer_name'] ?? null,
                'job_title'            => $data['job_title'] ?? null,
                'monthly_income_cents' => (int) round(($data['monthly_income'] ?? 0) * 100),
                'monthly_expenses_cents'=> (int) round(($data['monthly_expenses'] ?? 0) * 100),
                'existing_debt_cents'  => (int) round(($data['existing_debt'] ?? 0) * 100),
                'other_income_cents'   => (int) round(($data['other_income'] ?? 0) * 100),
                'residence_county'     => $data['residence_county'],
                'residence_address'    => $data['residence_address'],
                'housing_status'       => $data['housing_status'] ?? null,
                'years_at_address'     => $data['years_at_address'] ?? null,
                'next_of_kin' => [
                    'name'         => $data['next_of_kin_name'],
                    'relationship' => $data['next_of_kin_relationship'],
                    'phone'        => $nokPhone,
                    'id_number'    => $data['next_of_kin_id_number'] ?? null,
                    'email'        => $data['next_of_kin_email'] ?? null,
                    'address'      => $data['next_of_kin_address'] ?? null,
                    'notes'        => $data['next_of_kin_notes'] ?? null,
                ],
            ];

            // Update or create system event
            $existingEvent = DB::table('system_events')
                ->where('organisation_id', $organisationId)
                ->where('aggregate_type', 'borrower')
                ->where('aggregate_id', $borrower)
                ->where('event_type', 'borrower_profile_created')
                ->where('status', 'processed')
                ->first();

            if ($existingEvent) {
                DB::table('system_events')
                    ->where('id', $existingEvent->id)
                    ->update([
                        'payload' => json_encode($meta),
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('system_events')->insert([
                    'organisation_id' => $organisationId,
                    'event_type'      => 'borrower_profile_created',
                    'event_id'        => (string) Str::uuid(),
                    'idempotency_key' => 'bpc-' . $borrower,
                    'aggregate_type'  => 'borrower',
                    'aggregate_id'    => $borrower,
                    'payload'         => json_encode($meta),
                    'status'          => 'processed',
                    'processed_at'    => now(),
                    'occurred_at'     => now(),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        });

        if (
            array_key_exists('branch_id', $data)
            && (int) $previousBranchId !== (int) ($data['branch_id'] ?? 0)
        ) {
            Auditor::log('borrower.branch_assigned', Borrower::findOrFail($borrower), [
                'old' => ['branch_id' => $previousBranchId],
                'new' => ['branch_id' => $data['branch_id'] ?? null],
            ]);
        }

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Borrower updated successfully.']);
        }

        return redirect()->route('borrowers.show', $borrower)->with('success', 'Borrower updated successfully.');
    }

    public function destroy(int $borrower)
    {
        $this->authorizePermission('delete-borrowers');
        $organisationId = (int) auth()->user()->organisation_id;

        $borrowerRecord = DB::table('borrowers')
            ->where('organisation_id', $organisationId)
            ->where('id', $borrower)
            ->when(auth()->user()->hasRole(['loan-officer', 'supervisor']), fn ($q) =>
                $q->where('branch_id', auth()->user()->branch_id ?? 0)
            )
            ->select('id', 'user_id')
            ->first();

        if (!$borrowerRecord) abort(404);

        $hasActiveLoans = DB::table('loans')
            ->where('organisation_id', $organisationId)
            ->where('borrower_id', $borrower)
            ->whereIn('status', ['active', 'overdue'])
            ->exists();

        if ($hasActiveLoans) {
            return back()->with('error', 'Cannot delete a borrower with active or overdue loans.');
        }

        DB::transaction(function () use ($organisationId, $borrower, $borrowerRecord) {
            DB::table('borrowers')->where('organisation_id', $organisationId)->where('id', $borrower)->delete();
            DB::table('users')->where('organisation_id', $organisationId)->where('id', $borrowerRecord->user_id)->delete();
        });

        return redirect()->route('borrowers.index')->with('success', 'Borrower deleted successfully.');
    }

    private function normalisePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        }
        return $phone;
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->hasPermission($permission), 403);
    }
}
