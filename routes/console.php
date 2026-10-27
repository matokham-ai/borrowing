<?php

use App\Models\LoanApplication;
use App\Support\Auditor;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('security:purge-expired-loan-data', function () {
    $now = now();
    $purged = 0;
    $terminalLoanStatuses = ['completed', 'defaulted', 'written_off', 'cancelled'];

    LoanApplication::withoutGlobalScopes()
        ->with('loan')
        ->whereNotNull('security_data')
        ->whereNull('security_retention_until')
        ->where(function ($query) use ($terminalLoanStatuses): void {
            $query->whereIn('status', ['rejected', 'declined', 'expired', 'withdrawn'])
                ->orWhereHas('loan', fn ($loan) => $loan->whereIn('status', $terminalLoanStatuses));
        })
        ->chunkById(100, function ($applications) use ($terminalLoanStatuses): void {
            foreach ($applications as $application) {
                $loan = $application->loan;
                $closedAt = null;

                if ($loan && in_array($loan->status, $terminalLoanStatuses, true)) {
                    $closedAt = match ($loan->status) {
                        'completed' => $loan->completed_at,
                        'defaulted' => $loan->defaulted_at,
                        'written_off' => $loan->written_off_at,
                        default => $loan->updated_at,
                    };
                } else {
                    $closedAt = match ($application->status) {
                        'rejected', 'declined' => $application->decided_at,
                        'expired' => $application->expired_at,
                        'withdrawn' => $application->updated_at,
                        default => null,
                    };
                }

                if ($closedAt) {
                    $application->update([
                        'security_retention_until' => $closedAt
                            ->copy()
                            ->addYears(LoanApplication::SECURITY_DATA_RETENTION_YEARS),
                    ]);
                }
            }
        });

    LoanApplication::withoutGlobalScopes()
        ->whereNotNull('security_data')
        ->whereNotNull('security_retention_until')
        ->where('security_retention_until', '<=', $now)
        ->chunkById(100, function ($applications) use ($now, &$purged): void {
            foreach ($applications as $application) {
                $security = $application->security_data;
                $paths = array_filter([
                    $security['guarantor']['photo'] ?? null,
                    $security['guarantor']['id_front'] ?? null,
                    $security['guarantor']['id_back'] ?? null,
                ]);
                foreach ($security['collateral'] ?? [] as $asset) {
                    foreach ($asset['photos'] ?? [] as $photo) {
                        if (!empty($photo['path'])) {
                            $paths[] = $photo['path'];
                        }
                    }
                }

                $canPurge = true;
                $expectedPrefix = "application-security/{$application->organisation_id}/{$application->id}/";
                foreach (array_unique($paths) as $path) {
                    if (
                        !is_string($path)
                        || !str_starts_with($path, $expectedPrefix)
                        || str_contains(substr($path, strlen($expectedPrefix)), '..')
                    ) {
                        Log::error('Refusing to purge a loan security file outside its application directory.', [
                            'application_id' => $application->id,
                        ]);
                        $canPurge = false;
                        break;
                    }

                    if (Storage::disk('local')->exists($path) && !Storage::disk('local')->delete($path)) {
                        Log::error('Could not delete a loan security file after its retention period.', [
                            'application_id' => $application->id,
                        ]);
                        $canPurge = false;
                        break;
                    }
                }

                if (!$canPurge) {
                    continue;
                }

                $application->update([
                    'security_data' => null,
                    'guarantor_id_fingerprint' => null,
                    'guarantor_consent_at' => null,
                ]);
                Auditor::log('application.security_data_purged', $application, [
                    'orgId' => $application->organisation_id,
                    'new' => [
                        'purged_at' => $now->toIso8601String(),
                        'retention_until' => $application->security_retention_until->toIso8601String(),
                    ],
                ]);
                $purged++;
            }
        });

    $this->info("Purged {$purged} expired loan security record(s).");
})->purpose('Erase guarantor data and private images seven years after application closure');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| All production cron jobs are registered here.  The server cron should
| call: php artisan schedule:run  every minute.
|
*/

// Nightly overdue sweep — runs at 00:15 every day.
// Marks past-due schedule rows as overdue, accrues penalties, creates
// DelinquencyRecord rows, and records missed-payment behaviour events.
Schedule::command('loans:sweep-overdue')
    ->dailyAt('00:15')
    ->withoutOverlapping(30)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/sweep-overdue.log'));

Schedule::command('security:purge-expired-loan-data')
    ->dailyAt('01:15')
    ->withoutOverlapping(60)
    ->appendOutputTo(storage_path('logs/security-retention.log'));
