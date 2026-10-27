<?php

namespace App\Http\Controllers\Statement;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMpesaStatement;
use App\Support\MpesaStatementParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

class StatementController extends Controller
{
    /**
     * POST /statements/upload
     *
     * Accepts a PDF M-Pesa statement, stores it privately, runs the
     * parser synchronously (typically < 3 s for a 6-month statement),
     * caches the result keyed by job_id, and returns immediately.
     *
     * We do NOT create the statement_documents row here because the
     * borrower doesn't exist yet in wizard step 3.
     * BorrowerController::store() creates all DB rows after the borrower
     * is committed, using the file_key and parsed profile from the cache.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'statement' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ], [
            'statement.required' => 'Please upload your M-Pesa PDF statement.',
            'statement.mimes'    => 'Only PDF files are accepted.',
            'statement.max'      => 'The statement must be smaller than 20 MB.',
        ]);

        $file  = $request->file('statement');
        $orgId = (int) auth()->user()->organisation_id;

        // Store privately so the job/store() can access it later
        $fileKey = "statements/{$orgId}/" . Str::uuid() . ".pdf";
        Storage::disk('local')->put($fileKey, file_get_contents($file->getRealPath()));
        $absPath = Storage::disk('local')->path($fileKey);

        $hash  = hash_file('sha256', $file->getRealPath());
        $jobId = (string) Str::uuid();

        try {
            // Parse synchronously — smalot/pdfparser is fast enough
            $result = MpesaStatementParser::parseFile($absPath);

            $profile = $result['profile'];

            // Cache for 30 min — BorrowerController::store() will read this
            Cache::put('mpesa_stmt_' . $jobId, [
                'success'          => true,
                'file_key'         => $fileKey,
                'file_hash'        => $hash,
                'original_filename'=> $file->getClientOriginalName(),
                'file_size_bytes'  => $file->getSize(),
                'mime_type'        => $file->getMimeType() ?: 'application/pdf',
                'period_start'     => $result['period_start'],
                'period_end'       => $result['period_end'],
                'transaction_count'=> $result['transaction_count'],
                'transactions'     => $result['transactions'],   // stored for later DB insert
                'monthly'          => $result['monthly'],
                'profile'          => $profile,
            ], now()->addMinutes(30));

            return response()->json([
                'success'          => true,
                'job_id'           => $jobId,
                'file_key'         => $fileKey,
                'transaction_count'=> $result['transaction_count'],
                'analysis_months'  => $profile['analysis_months'],
                'period_start'     => $result['period_start'],
                'period_end'       => $result['period_end'],
                // KES display values for the UI
                'profile' => [
                    'avg_income'       => self::kes($profile['average_monthly_income_cents']),
                    'avg_expense'      => self::kes($profile['average_monthly_expense_cents']),
                    'avg_net_cashflow' => self::kes($profile['average_monthly_net_cashflow_cents']),
                    'avg_salary'       => self::kes($profile['average_monthly_salary_income_cents']),
                    'avg_business'     => self::kes($profile['average_monthly_business_income_cents']),
                    'avg_debt_service' => self::kes($profile['average_monthly_debt_service_cents']),
                    'debt_service_ratio'  => $profile['debt_service_ratio'],
                    'financial_score'     => $profile['financial_score'],
                    'income_stability'    => $profile['income_stability_score'],
                    'cashflow_score'      => $profile['cashflow_score'],
                ],
                'monthly' => array_map(fn ($m) => [
                    'month'         => $m['month'],
                    'income'        => self::kes($m['income_cents']),
                    'expense'       => self::kes($m['expense_cents']),
                    'debt_service'  => self::kes($m['debt_service_cents']),
                ], $result['monthly']),
            ]);

        } catch (\Exception $e) {
            // Clean up the stored file on failure
            Storage::disk('local')->delete($fileKey);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    private static function kes(int $cents): float
    {
        return round($cents / 100, 2);
    }

    /**
     * POST /borrowers/{borrower}/statement/reupload
     *
     * Allows re-uploading an M-Pesa statement for an existing borrower.
     * Parses synchronously, persists a new statement_document, creates
     * transaction_classifications, financial_analysis_run, and replaces
     * the current financial_profile (marks the old one is_current=false).
     *
     * Returns JSON — consumed by the borrower edit/show page via AJAX or
     * a standard form redirect.
     */
    public function reupload(Request $request, int $borrower): JsonResponse
    {
        $orgId = (int) auth()->user()->organisation_id;

        $request->validate([
            'statement' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ], [
            'statement.required' => 'Please upload the M-Pesa PDF statement.',
            'statement.mimes'    => 'Only PDF files are accepted.',
            'statement.max'      => 'The statement must be smaller than 20 MB.',
        ]);

        // Verify borrower belongs to this org
        $borrowerRow = DB::table('borrowers')
            ->where('organisation_id', $orgId)
            ->where('id', $borrower)
            ->when(auth()->user()->hasRole(['loan-officer', 'supervisor']), fn ($query) =>
                $query->where('branch_id', auth()->user()->branch_id ?? 0)
            )
            ->first();

        if (!$borrowerRow) {
            return response()->json(['success' => false, 'error' => 'Borrower not found.'], 404);
        }

        $file    = $request->file('statement');
        $fileKey = "statements/{$orgId}/" . Str::uuid() . '.pdf';
        Storage::disk('local')->put($fileKey, file_get_contents($file->getRealPath()));
        $absPath = Storage::disk('local')->path($fileKey);
        $hash    = hash_file('sha256', $file->getRealPath());

        try {
            $result  = MpesaStatementParser::parseFile($absPath);
            $profile = $result['profile'];
        } catch (\Exception $e) {
            Storage::disk('local')->delete($fileKey);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }

        DB::transaction(function () use (
            $orgId, $borrower, $borrowerRow, $file, $fileKey, $hash, $result, $profile
        ) {
            // 1. Create statement_documents row
            $statementDocId = DB::table('statement_documents')->insertGetId([
                'organisation_id'        => $orgId,
                'borrower_id'            => $borrower,
                'document_type'          => 'mpesa_statement',
                'provider'               => 'mpesa',
                'file_key'               => $fileKey,
                'original_filename'      => $file->getClientOriginalName(),
                'mime_type'              => $file->getMimeType() ?: 'application/pdf',
                'file_size_bytes'        => $file->getSize(),
                'file_hash'              => $hash,
                'statement_period_start' => $result['period_start'] ?? null,
                'statement_period_end'   => $result['period_end'] ?? null,
                'processing_status'      => 'completed',
                'uploaded_at'            => now(),
                'processed_at'           => now(),
                'transaction_count'      => $result['transaction_count'],
                'document_metadata'      => json_encode(['source' => 'reupload']),
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);

            // 2. Bulk-insert transaction classifications
            foreach (array_chunk($result['transactions'] ?? [], 200) as $chunk) {
                $rows = [];
                foreach ($chunk as $tx) {
                    $rows[] = [
                        'organisation_id'           => $orgId,
                        'statement_document_id'     => $statementDocId,
                        'borrower_id'               => $borrower,
                        'source_transaction_id'     => $tx['receipt_no']    ?? null,
                        'transaction_at'            => ($tx['date'] ?? date('Y-m-d')) . ' ' . ($tx['time'] ?? '00:00:00'),
                        'transaction_type'          => ($tx['amount_cents'] ?? 0) > 0 ? 'credit' : 'debit',
                        'description'               => substr($tx['description'] ?? '', 0, 500),
                        'amount_cents'              => $tx['amount_cents']   ?? 0,
                        'currency'                  => 'KES',
                        'balance_after_cents'       => $tx['balance_cents']  ?? null,
                        'classification'            => $tx['classification'] ?? 'other',
                        'classification_confidence' => 0.85,
                        'classifier_version'        => 'rule-based-v1',
                        'is_income'                 => ($tx['is_income']       ?? false) ? 1 : 0,
                        'is_expense'                => ($tx['is_expense']      ?? false) ? 1 : 0,
                        'is_transfer'               => ($tx['is_transfer']     ?? false) ? 1 : 0,
                        'is_loan_related'           => ($tx['is_loan_related'] ?? false) ? 1 : 0,
                        'is_repayment'              => ($tx['is_repayment']    ?? false) ? 1 : 0,
                        'classification_snapshot'   => null,
                        'created_at'                => now(),
                        'updated_at'                => now(),
                    ];
                }
                if (!empty($rows)) {
                    DB::table('transaction_classifications')->insert($rows);
                }
            }

            // 3. Create financial_analysis_run
            $netCashflow   = ($profile['average_monthly_income_cents']  ?? 0)
                           - ($profile['average_monthly_expense_cents'] ?? 0);
            $discretionary = max(0, $netCashflow - ($profile['average_monthly_debt_service_cents'] ?? 0));

            $runId = DB::table('financial_analysis_runs')->insertGetId([
                'organisation_id'              => $orgId,
                'borrower_id'                  => $borrower,
                'statement_document_id'        => $statementDocId,
                'run_type'                     => 'reupload',
                'status'                       => 'completed',
                'model_version'                => 'mpesa-parser-v1',
                'parser_version'               => 'mpesa-parser-v1',
                'classifier_version'           => 'rule-based-v1',
                'started_at'                   => now(),
                'completed_at'                 => now(),
                'transaction_count'            => $result['transaction_count'],
                'classified_transaction_count' => $result['transaction_count'],
                'financial_score'              => $profile['financial_score'] ?? null,
                'input_snapshot'               => json_encode([
                    'period_start' => $result['period_start'] ?? null,
                    'period_end'   => $result['period_end']   ?? null,
                ]),
                'result_snapshot'              => json_encode($profile),
                'created_at'                   => now(),
                'updated_at'                   => now(),
            ]);

            // 4. Retire the old financial_profile
            DB::table('financial_profiles')
                ->where('organisation_id', $orgId)
                ->where('borrower_id', $borrower)
                ->where('is_current', true)
                ->update(['is_current' => false, 'updated_at' => now()]);

            // 5. Insert the new current financial_profile
            DB::table('financial_profiles')->insert([
                'organisation_id'                              => $orgId,
                'borrower_id'                                  => $borrower,
                'financial_analysis_run_id'                    => $runId,
                'currency'                                     => 'KES',
                'average_monthly_income_cents'                 => $profile['average_monthly_income_cents']          ?? 0,
                'average_monthly_expense_cents'                => $profile['average_monthly_expense_cents']         ?? 0,
                'average_monthly_net_cashflow_cents'           => $netCashflow,
                'average_monthly_salary_income_cents'          => $profile['average_monthly_salary_income_cents']   ?? 0,
                'average_monthly_business_income_cents'        => $profile['average_monthly_business_income_cents'] ?? 0,
                'average_monthly_other_income_cents'           => $profile['average_monthly_other_income_cents']    ?? 0,
                'average_monthly_debt_service_cents'           => $profile['average_monthly_debt_service_cents']    ?? 0,
                'average_monthly_discretionary_cashflow_cents' => $discretionary,
                'income_stability_score'                       => $profile['income_stability_score']  ?? null,
                'cashflow_score'                               => $profile['cashflow_score']          ?? null,
                'financial_score'                              => $profile['financial_score']         ?? null,
                'income_volatility'                            => $profile['income_volatility']       ?? null,
                'expense_volatility'                           => $profile['expense_volatility']      ?? null,
                'debt_service_ratio'                           => $profile['debt_service_ratio']      ?? null,
                'analysis_months'                              => $profile['analysis_months']         ?? 0,
                'transaction_count'                            => $result['transaction_count'],
                'model_version'                                => 'mpesa-parser-v1',
                'profile_snapshot'                             => json_encode([
                    'monthly'      => $result['monthly']      ?? [],
                    'period_start' => $result['period_start'] ?? null,
                    'period_end'   => $result['period_end']   ?? null,
                ]),
                'is_current'                                   => true,
                'calculated_at'                                => now(),
                'created_at'                                   => now(),
                'updated_at'                                   => now(),
            ]);
        });

        return response()->json([
            'success'          => true,
            'transaction_count'=> $result['transaction_count'],
            'analysis_months'  => $profile['analysis_months'],
            'period_start'     => $result['period_start'],
            'period_end'       => $result['period_end'],
            'profile' => [
                'avg_income'          => self::kes($profile['average_monthly_income_cents']),
                'avg_expense'         => self::kes($profile['average_monthly_expense_cents']),
                'avg_net_cashflow'    => self::kes($profile['average_monthly_net_cashflow_cents']),
                'avg_debt_service'    => self::kes($profile['average_monthly_debt_service_cents']),
                'debt_service_ratio'  => $profile['debt_service_ratio'],
                'financial_score'     => $profile['financial_score'],
                'income_stability'    => $profile['income_stability_score'],
                'cashflow_score'      => $profile['cashflow_score'],
            ],
        ]);
    }

}
