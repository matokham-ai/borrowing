<?php

namespace Database\Seeders;

use App\Models\Borrower;
use App\Models\BorrowerTier;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanPayment;
use App\Models\LoanProduct;
use App\Models\LoanTierRule;
use App\Models\Organisation;
use App\Models\SystemEvent;
use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class LendingDashboardSeeder extends Seeder
{
    public function run(): void
    {
        // MultiTenantTrait adds a global scope that requires Auth::check().
        // During seeding there is no authenticated user, so every scoped query
        // returns nothing and creates silently omit organisation_id.
        // We bypass the scope on every read with withoutGlobalScopes().

        $organisation = Organisation::where('slug', 'lendflow')->firstOrFail();
        $orgId        = $organisation->id;

        $officer = Role::withoutGlobalScopes()
            ->where('organisation_id', $orgId)
            ->where('name', 'loan-officer')
            ->firstOrFail();

        // ── Borrower Tiers ────────────────────────────────────────────────────
        $tiersData = [
            [1, 'Bronze',   5_000_000,   100_000,  30,  true,  0.40],
            [2, 'Silver',  20_000_000,   500_000,  60,  false, 0.60],
            [3, 'Gold',    75_000_000, 1_000_000,  90,  false, 0.80],
            [4, 'Platinum',200_000_000, 5_000_000, 180, false, 0.90],
        ];

        $tiers = [];
        foreach ($tiersData as [$num, $name, $max, $min, $term, $entry, $financialScore]) {
            $tiers[$num] = BorrowerTier::withoutGlobalScopes()->firstOrCreate(
                ['organisation_id' => $orgId, 'tier_number' => $num],
                [
                    'name'                 => $name,
                    'max_loan_amount_cents'=> $max,
                    'min_loan_amount_cents'=> $min,
                    'max_term_days'        => $term,
                    'is_entry_tier'        => $entry,
                    'minimum_financial_score' => $financialScore,
                    'is_active'            => true,
                    'version'              => 1,
                ]
            );
        }

        // ── Loan Products ─────────────────────────────────────────────────────
        $productsData = [
            ['BG', 'Business Growth', 5_000_000,   75_000_000,  30, 90],
            ['WC', 'Working Capital', 1_000_000,   20_000_000,  14, 60],
            ['AF', 'Asset Finance',  10_000_000,  200_000_000,  60, 180],
            ['PL', 'Personal Loan',     500_000,   10_000_000,   7, 30],
        ];

        $products = [];
        foreach ($productsData as [$code, $name, $minAmt, $maxAmt, $minTerm, $maxTerm]) {
            $products[$code] = LoanProduct::withoutGlobalScopes()->firstOrCreate(
                ['organisation_id' => $orgId, 'code' => $code],
                [
                    'name'                => $name,
                    'minimum_amount_cents'=> $minAmt,
                    'maximum_amount_cents'=> $maxAmt,
                    'minimum_term_days'   => $minTerm,
                    'maximum_term_days'   => $maxTerm,
                    'interest_type'       => 'flat',
                    'interest_rate'       => 0.05,
                    'fee_type'            => 'none',
                    'fee_rate'            => 0,
                    'fixed_fee_cents'     => 0,
                    'repayment_frequency' => 'monthly',
                    'grace_period_days'   => 3,
                    'requires_appraisal'  => true,
                    'requires_statement'  => true,
                    'is_active'           => true,
                    'version'             => 1,
                ]
            );
        }

        // ── Loan Tier Rules ───────────────────────────────────────────────────
        foreach ($products as $product) {
            foreach ($tiers as $tier) {
                LoanTierRule::withoutGlobalScopes()->firstOrCreate(
                    [
                        'organisation_id' => $orgId,
                        'loan_product_id' => $product->id,
                        'borrower_tier_id'=> $tier->id,
                        'version'         => 1,
                    ],
                    [
                        'maximum_amount_cents' => min($product->maximum_amount_cents, $tier->max_loan_amount_cents),
                        'minimum_amount_cents' => $product->minimum_amount_cents,
                        'maximum_term_days'    => min($product->maximum_term_days, $tier->max_term_days),
                        'is_active'            => true,
                    ]
                );
            }
        }

        // ── Borrowers ─────────────────────────────────────────────────────────
        // Each entry: [name, email, phone, tier_number, lifetime, on_time, defaulted, months_ago_registered]
        $borrowersData = [
            ['John Otieno',   'john.otieno@example.com',   '254701000001', 2, 4, 3, 0, 12],
            ['Agnes Mwangi',  'agnes.mwangi@example.com',  '254701000002', 2, 3, 3, 0, 10],
            ['Peter Kamau',   'peter.kamau@example.com',   '254701000003', 3, 7, 5, 0, 18],
            ['Grace Wanjiku', 'grace.wanjiku@example.com', '254701000004', 1, 1, 0, 1,  4],
            ['Samuel Kiptoo', 'samuel.kiptoo@example.com', '254701000005', 1, 0, 0, 0,  1],
            ['Mary Njeri',    'mary.njeri@example.com',    '254701000006', 3, 6, 6, 0, 15],
            ['David Ochieng', 'david.ochieng@example.com', '254701000007', 2, 5, 4, 0, 14],
            ['Faith Akinyi',  'faith.akinyi@example.com',  '254701000008', 1, 2, 1, 0,  6],
        ];

        $borrowers = [];
        foreach ($borrowersData as [$name, $email, $phone, $tierNum, $lifetime, $onTime, $defaulted, $monthsAgo]) {
            $user = User::withoutGlobalScopes()->updateOrCreate(
                ['email' => $email],
                [
                    'organisation_id' => $orgId,
                    'name'            => $name,
                    'password'        => 'password',
                    'is_active'       => true,
                ]
            );

            // Sync role without the global scope interfering
            $user->roles()->syncWithPivotValues(
                [$officer->id],
                ['organisation_id' => $orgId]
            );

            $late  = max(0, $lifetime - $onTime - $defaulted);
            $score = $lifetime > 0 ? round($onTime / $lifetime * 100, 4) : 0;

            $borrowers[$name] = Borrower::withoutGlobalScopes()->updateOrCreate(
                ['organisation_id' => $orgId, 'user_id' => $user->id],
                [
                    'phone_number'             => $phone,
                    'account_status'           => 'active',
                    'current_tier_id'          => $tiers[$tierNum]->id,
                    'lifetime_loans_count'     => $lifetime,
                    'successful_loans_count'   => $onTime,
                    'on_time_loans_count'      => $onTime,
                    'consecutive_on_time_loans'=> max(0, $onTime - 1),
                    'late_loans_count'         => $late,
                    'defaulted_loans_count'    => $defaulted,
                    'behaviour_score'          => $score,
                    'registered_at'            => Carbon::now()->subMonths($monthsAgo),
                ]
            );
        }

        // ── Loan Applications, Loans & Payments ───────────────────────────────
        // disbursed_at is spread across the last 6 months so the portfolio
        // performance chart has data in multiple columns.
        //
        // Each entry:
        //   borrower, product code, amount (cents), term (days),
        //   app_status, decision, loan_status, disbursed_months_ago
        $appsData = [
            ['John Otieno',   'BG', 35_000_000, 60, 'under_review', null,       null,     null],
            ['Agnes Mwangi',  'WC', 18_000_000, 30, 'approved',     'approved', 'active', 0],
            ['Peter Kamau',   'AF', 72_000_000, 90, 'under_review', null,       null,     null],
            ['Grace Wanjiku', 'PL',  9_500_000, 30, 'under_review', null,       null,     null],
            ['Mary Njeri',    'BG', 50_000_000, 90, 'approved',     'approved', 'active', 1],
            ['David Ochieng', 'WC', 15_000_000, 30, 'approved',     'approved', 'overdue',2],
            ['Faith Akinyi',  'PL',  5_000_000, 30, 'approved',     'approved', 'active', 3],
            ['John Otieno',   'WC', 10_000_000, 30, 'approved',     'approved', 'completed', 4],
            ['Agnes Mwangi',  'BG', 25_000_000, 60, 'approved',     'approved', 'active', 5],
        ];

        foreach ($appsData as [$borrowerName, $productCode, $amount, $term, $appStatus, $decision, $loanStatus, $monthsAgo]) {
            $borrower = $borrowers[$borrowerName];
            $product  = $products[$productCode];

            $tierRule = LoanTierRule::withoutGlobalScopes()
                ->where('organisation_id', $orgId)
                ->where('loan_product_id', $product->id)
                ->where('borrower_tier_id', $borrower->current_tier_id)
                ->where('version', 1)
                ->first();

            // Stable key: one application per borrower+product+status combination
            $app = LoanApplication::withoutGlobalScopes()->firstOrCreate(
                [
                    'organisation_id' => $orgId,
                    'borrower_id'     => $borrower->id,
                    'loan_product_id' => $product->id,
                    'status'          => $appStatus,
                ],
                [
                    'loan_tier_rule_id'      => $tierRule?->id,
                    'requested_amount_cents' => $amount,
                    'requested_term_days'    => $term,
                    'approved_amount_cents'  => $decision === 'approved' ? $amount : null,
                    'approved_term_days'     => $decision === 'approved' ? $term   : null,
                    'decision'               => $decision,
                    'financial_score'        => 0.75,
                    'behaviour_score'        => $borrower->behaviour_score,
                    'submitted_at'           => Carbon::now()->subMonths($monthsAgo ?? 0)->subDays(3),
                    'decided_at'             => $decision ? Carbon::now()->subMonths($monthsAgo ?? 0)->subDay() : null,
                ]
            );

            if (!$loanStatus) {
                continue;
            }

            $existingLoan = Loan::withoutGlobalScopes()
                ->where('organisation_id', $orgId)
                ->where('loan_application_id', $app->id)
                ->first();

            if ($existingLoan) {
                continue;
            }

            $interest   = (int) ($amount * 0.05);
            $disbursedAt = Carbon::now()->subMonths($monthsAgo)->startOfMonth()->addDays(10);

            $loan = Loan::withoutGlobalScopes()->create([
                'organisation_id'     => $orgId,
                'borrower_id'         => $borrower->id,
                'loan_application_id' => $app->id,
                'loan_product_id'     => $product->id,
                'loan_tier_rule_id'   => $tierRule?->id,
                'principal_cents'     => $amount,
                'interest_rate'       => 0.05,
                'interest_cents'      => $interest,
                'fees_cents'          => 0,
                'total_payable_cents' => $amount + $interest,
                'term_days'           => $term,
                'repayment_frequency' => 'monthly',
                'status'              => $loanStatus,
                'approved_at'         => $disbursedAt->copy()->subDay(),
                'disbursed_at'        => $disbursedAt,
                'matures_at'          => $disbursedAt->copy()->addDays($term),
                'completed_at'        => $loanStatus === 'completed' ? $disbursedAt->copy()->addDays($term - 5) : null,
            ]);

            // One payment per active/completed loan, posted in the same month
            // as disbursement so collection chart data aligns with disbursements.
            if (in_array($loanStatus, ['active', 'completed'])) {
                $paymentAt = $disbursedAt->copy()->addDays(15);

                LoanPayment::withoutGlobalScopes()->firstOrCreate(
                    [
                        'organisation_id' => $orgId,
                        'loan_id'         => $loan->id,
                    ],
                    [
                        'payment_method'       => 'mpesa',
                        'mpesa_receipt_number' => strtoupper(Str::random(10)),
                        'amount_cents'         => (int) ($amount * 0.3),
                        'status'               => 'posted',
                        'received_at'          => $paymentAt,
                        'posted_at'            => $paymentAt,
                    ]
                );
            }
        }

        // ── System Events (Activity Feed) ─────────────────────────────────────
        // Keys are stable strings so re-running the seeder is idempotent.
        $eventsData = [
            [
                'key'            => 'seed-loan-approved-agnes',
                'event_type'     => 'loan.approved',
                'aggregate_type' => 'Loan',
                'payload'        => ['borrower' => 'Agnes Mwangi', 'amount_cents' => 18_000_000],
                'occurred_at'    => Carbon::now()->subMinutes(8),
            ],
            [
                'key'            => 'seed-payment-received-agnes',
                'event_type'     => 'payment.received',
                'aggregate_type' => 'LoanPayment',
                'payload'        => ['method' => 'M-Pesa', 'amount_cents' => 5_400_000],
                'occurred_at'    => Carbon::now()->subMinutes(21),
            ],
            [
                'key'            => 'seed-borrower-registered-samuel',
                'event_type'     => 'borrower.registered',
                'aggregate_type' => 'Borrower',
                'payload'        => ['borrower' => 'Samuel Kiptoo'],
                'occurred_at'    => Carbon::now()->subMinutes(43),
            ],
            [
                'key'            => 'seed-delinquency-david',
                'event_type'     => 'loan.delinquency_alert',
                'aggregate_type' => 'Loan',
                'payload'        => ['loan_ref' => 'LN-28391', 'days_overdue' => 14],
                'occurred_at'    => Carbon::now()->subHour(),
            ],
            [
                'key'            => 'seed-tier-upgraded-mary',
                'event_type'     => 'borrower.tier_upgraded',
                'aggregate_type' => 'Borrower',
                'payload'        => ['borrower' => 'Mary Njeri', 'from_tier' => 'Silver', 'to_tier' => 'Gold'],
                'occurred_at'    => Carbon::now()->subHours(2),
            ],
        ];

        foreach ($eventsData as $e) {
            $idempotencyKey = 'seed-' . $orgId . '-' . $e['key'];

            SystemEvent::withoutGlobalScopes()->firstOrCreate(
                ['organisation_id' => $orgId, 'idempotency_key' => $idempotencyKey],
                [
                    'event_type'      => $e['event_type'],
                    'aggregate_type'  => $e['aggregate_type'],
                    'event_id'        => $idempotencyKey,
                    'idempotency_key' => $idempotencyKey,
                    'payload'         => $e['payload'],
                    'status'          => 'processed',
                    'occurred_at'     => $e['occurred_at'],
                    'processed_at'    => $e['occurred_at'],
                ]
            );
        }
    }
}
