<?php

namespace App\Jobs;

use App\Models\Loan;
use App\Services\Mpesa\MpesaDisbursementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessLoanDisbursement
 *
 * Initiates the B2C outbound M-Pesa disbursement for an approved loan.
 *
 * Dispatched by the loan disbursement controller action after a loan has
 * been created from an approved application (LoanService::createFromApplication).
 *
 * The job calls MpesaDisbursementService::disburse(), which:
 *  - Creates the outbound mpesa_transaction.
 *  - POSTs the B2C request to Daraja.
 *  - Records an attempt in mpesa_transaction_attempts.
 *
 * Activation (loan.status → 'active') happens asynchronously when
 * ProcessMpesaTransaction processes the B2C result callback.
 *
 * Uses the default queue consumed by the application's running workers.
 * Retried 3 times with delay to avoid Daraja rate limits.
 */
class ProcessLoanDisbursement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 3;
    public int   $timeout = 90;
    public array $backoff  = [30, 120, 600];

    public function __construct(
        public readonly int $loanId,
        public readonly int $organisationId,
    ) {
    }

    public function handle(MpesaDisbursementService $disbursementService): void
    {
        $loan = Loan::where('organisation_id', $this->organisationId)
            ->find($this->loanId);

        if (!$loan) {
            Log::warning('ProcessLoanDisbursement: loan not found', [
                'loan_id' => $this->loanId,
            ]);
            return;
        }

        if ($loan->status !== 'disbursement_pending') {
            Log::info('ProcessLoanDisbursement: loan is not in disbursement_pending status — skipping', [
                'loan_id' => $loan->id,
                'status'  => $loan->status,
            ]);
            return;
        }

        $application = $loan->loanApplication;
        if (
            !$application
            || !$application->first_level_approved_by
            || !$application->final_approved_by
            || (int) $application->first_level_approved_by === (int) $application->final_approved_by
            || (int) $application->created_by === (int) $application->first_level_approved_by
            || (int) $application->created_by === (int) $application->final_approved_by
            || !$application->security_requirements_required
            || !$application->hasCompleteSecurityPackage()
        ) {
            Log::error('ProcessLoanDisbursement: loan application is missing required approvals or security evidence', [
                'loan_id' => $loan->id,
                'application_id' => $application?->id,
            ]);
            $loan->update(['status' => 'disbursement_failed']);

            return;
        }

        try {
            $mpesaTx = $disbursementService->disburse($loan);

            Log::info('ProcessLoanDisbursement: B2C request sent', [
                'loan_id'    => $loan->id,
                'mpesa_tx_id'=> $mpesaTx->id,
                'amount_kes' => round($loan->principal_cents / 100, 2),
            ]);
        } catch (\RuntimeException $e) {
            Log::error('ProcessLoanDisbursement: disbursement failed', [
                'loan_id' => $loan->id,
                'error'   => $e->getMessage(),
            ]);
            throw $e;  // trigger retry
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ProcessLoanDisbursement: job failed permanently after all retries', [
            'loan_id' => $this->loanId,
            'error'   => $e->getMessage(),
        ]);

        // Mark the loan as disbursement_failed so the operator is alerted
        Loan::where('organisation_id', $this->organisationId)
            ->where('id', $this->loanId)
            ->where('status', 'disbursement_pending')
            ->update(['status' => 'disbursement_failed']);
    }
}
