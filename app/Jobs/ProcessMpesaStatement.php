<?php

namespace App\Jobs;

use App\Support\MpesaStatementParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessMpesaStatement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries   = 2;

    public function __construct(
        private readonly string $jobId,           // cache key for poll
        private readonly int    $statementDocId,  // statement_documents.id
        private readonly string $filePath,        // absolute path to the PDF
        private readonly int    $organisationId,
    ) {}

    public function handle(): void
    {
        $this->updateDocStatus('processing');

        try {
            // ── 1. Parse the PDF ──────────────────────────────────────────
            $result = MpesaStatementParser::parseFile($this->filePath);

            $profile      = $result['profile'];
            $transactions = $result['transactions'];
            $monthly      = $result['monthly'];
            $txCount      = $result['transaction_count'];

            // ── 2. Create the analysis run record ─────────────────────────
            $runId = DB::table('financial_analysis_runs')->insertGetId([
                'organisation_id'               => $this->organisationId,
                'borrower_id'                   => $this->getBorrowerId(),
                'statement_document_id'         => $this->statementDocId,
                'run_type'                      => 'initial_appraisal',
                'status'                        => 'completed',
                'model_version'                 => 'mpesa-parser-v1',
                'parser_version'                => 'mpesa-parser-v1',
                'classifier_version'            => 'rule-based-v1',
                'started_at'                    => now(),
                'completed_at'                  => now(),
                'transaction_count'             => $txCount,
                'classified_transaction_count'  => $txCount,
                'financial_score'               => $profile['financial_score'],
                'input_snapshot'                => json_encode([
                    'statement_document_id' => $this->statementDocId,
                    'period_start'          => $result['period_start'],
                    'period_end'            => $result['period_end'],
                ]),
                'result_snapshot'               => json_encode($profile),
                'request_reference'             => $this->jobId,
                'created_at'                    => now(),
                'updated_at'                    => now(),
            ]);

            // ── 3. Persist transaction classifications ─────────────────────
            // Insert in chunks to avoid oversized queries
            $borrowerId = $this->getBorrowerId();
            $chunks     = array_chunk($transactions, 200);

            foreach ($chunks as $chunk) {
                $rows = [];
                foreach ($chunk as $tx) {
                    // Skip if this receipt code already exists for this document
                    // (handles re-processing the same file)
                    if ($tx['receipt_no'] && DB::table('transaction_classifications')
                        ->where('organisation_id', $this->organisationId)
                        ->where('statement_document_id', $this->statementDocId)
                        ->where('source_transaction_id', $tx['receipt_no'])
                        ->exists()) {
                        continue;
                    }

                    $rows[] = [
                        'organisation_id'          => $this->organisationId,
                        'statement_document_id'    => $this->statementDocId,
                        'borrower_id'              => $borrowerId,
                        'source_transaction_id'    => $tx['receipt_no'] ?? null,
                        'transaction_at'           => $tx['date'] . ' ' . ($tx['time'] ?? '00:00:00'),
                        'transaction_type'         => $tx['amount_cents'] > 0 ? 'credit' : 'debit',
                        'description'              => substr($tx['description'] ?? '', 0, 500),
                        'amount_cents'             => $tx['amount_cents'],
                        'currency'                 => 'KES',
                        'balance_after_cents'      => $tx['balance_cents'] ?? null,
                        'classification'           => $tx['classification'],
                        'classification_confidence'=> 0.85, // rule-based default confidence
                        'classifier_version'       => 'rule-based-v1',
                        'is_income'                => $tx['is_income']       ? 1 : 0,
                        'is_expense'               => $tx['is_expense']      ? 1 : 0,
                        'is_transfer'              => $tx['is_transfer']     ? 1 : 0,
                        'is_loan_related'          => $tx['is_loan_related'] ? 1 : 0,
                        'is_repayment'             => $tx['is_repayment']    ? 1 : 0,
                        'classification_snapshot'  => json_encode([
                            'monthly_data' => null, // don't bloat per-row
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                if (!empty($rows)) {
                    DB::table('transaction_classifications')->insert($rows);
                }
            }

            // ── 4. Upsert financial profile ────────────────────────────────
            // Mark any previous current profile as stale first
            DB::table('financial_profiles')
                ->where('organisation_id', $this->organisationId)
                ->where('borrower_id', $borrowerId)
                ->where('is_current', true)
                ->update(['is_current' => false, 'updated_at' => now()]);

            DB::table('financial_profiles')->insert([
                'organisation_id'                           => $this->organisationId,
                'borrower_id'                               => $borrowerId,
                'financial_analysis_run_id'                 => $runId,
                'currency'                                  => 'KES',
                'average_monthly_income_cents'              => $profile['average_monthly_income_cents'],
                'average_monthly_expense_cents'             => $profile['average_monthly_expense_cents'],
                'average_monthly_net_cashflow_cents'        => $profile['average_monthly_net_cashflow_cents'],
                'average_monthly_salary_income_cents'       => $profile['average_monthly_salary_income_cents'],
                'average_monthly_business_income_cents'     => $profile['average_monthly_business_income_cents'],
                'average_monthly_other_income_cents'        => $profile['average_monthly_other_income_cents'],
                'average_monthly_debt_service_cents'        => $profile['average_monthly_debt_service_cents'],
                'average_monthly_discretionary_cashflow_cents' => $profile['average_monthly_discretionary_cashflow_cents'],
                'income_stability_score'                    => $profile['income_stability_score'],
                'cashflow_score'                            => $profile['cashflow_score'],
                'financial_score'                           => $profile['financial_score'],
                'income_volatility'                         => $profile['income_volatility'],
                'expense_volatility'                        => $profile['expense_volatility'],
                'debt_service_ratio'                        => $profile['debt_service_ratio'],
                'analysis_months'                           => $profile['analysis_months'],
                'transaction_count'                         => $txCount,
                'model_version'                             => 'mpesa-parser-v1',
                'profile_snapshot'                          => json_encode([
                    'monthly'      => $monthly,
                    'period_start' => $result['period_start'],
                    'period_end'   => $result['period_end'],
                ]),
                'is_current'                                => true,
                'calculated_at'                             => now(),
                'created_at'                                => now(),
                'updated_at'                                => now(),
            ]);

            // ── 5. Update statement document as processed ──────────────────
            DB::table('statement_documents')
                ->where('id', $this->statementDocId)
                ->update([
                    'processing_status' => 'completed',
                    'processed_at'      => now(),
                    'transaction_count' => $txCount,
                    'statement_period_start' => $result['period_start'],
                    'statement_period_end'   => $result['period_end'],
                    'processing_snapshot'    => json_encode([
                        'run_id'         => $runId,
                        'financial_score'=> $profile['financial_score'],
                        'analysis_months'=> $profile['analysis_months'],
                    ]),
                    'updated_at' => now(),
                ]);

            // ── 6. Store result in cache for the browser to poll ───────────
            $this->storeResult([
                'success'         => true,
                'transaction_count' => $txCount,
                'analysis_months' => $profile['analysis_months'],
                'period_start'    => $result['period_start'],
                'period_end'      => $result['period_end'],
                'profile' => [
                    // KES values for display (divide cents by 100)
                    'avg_income'       => round($profile['average_monthly_income_cents'] / 100, 2),
                    'avg_expense'      => round($profile['average_monthly_expense_cents'] / 100, 2),
                    'avg_net_cashflow' => round($profile['average_monthly_net_cashflow_cents'] / 100, 2),
                    'avg_salary'       => round($profile['average_monthly_salary_income_cents'] / 100, 2),
                    'avg_business'     => round($profile['average_monthly_business_income_cents'] / 100, 2),
                    'avg_debt_service' => round($profile['average_monthly_debt_service_cents'] / 100, 2),
                    'debt_service_ratio'   => $profile['debt_service_ratio'],
                    'financial_score'      => $profile['financial_score'],
                    'income_stability'     => $profile['income_stability_score'],
                    'cashflow_score'       => $profile['cashflow_score'],
                ],
                'monthly'           => $monthly,
                'statement_doc_id'  => $this->statementDocId,
            ]);

            Log::info('M-Pesa statement processed', [
                'job'        => $this->jobId,
                'doc_id'     => $this->statementDocId,
                'tx_count'   => $txCount,
                'months'     => $profile['analysis_months'],
                'fin_score'  => $profile['financial_score'],
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessMpesaStatement failed', [
                'job'   => $this->jobId,
                'doc'   => $this->statementDocId,
                'error' => $e->getMessage(),
            ]);

            $this->updateDocStatus('failed');

            $this->storeResult([
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessMpesaStatement job hard-failed', [
            'job'   => $this->jobId,
            'error' => $e->getMessage(),
        ]);

        $this->updateDocStatus('failed');

        $this->storeResult([
            'success' => false,
            'error'   => 'Statement processing failed after retries: ' . $e->getMessage(),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function getBorrowerId(): int
    {
        return (int) DB::table('statement_documents')
            ->where('id', $this->statementDocId)
            ->value('borrower_id');
    }

    private function updateDocStatus(string $status): void
    {
        DB::table('statement_documents')
            ->where('id', $this->statementDocId)
            ->update(['processing_status' => $status, 'updated_at' => now()]);
    }

    private function storeResult(array $result): void
    {
        Cache::put('mpesa_statement_' . $this->jobId, $result, now()->addMinutes(15));
    }
}
