<?php

namespace Database\Seeders;

use App\Models\Borrower;
use App\Models\BorrowerTier;
use App\Models\Branch;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanPayment;
use App\Models\LoanProduct;
use App\Models\LoanTierRule;
use App\Models\Organisation;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SystemEvent;
use App\Models\User;
use App\Services\Loans\LoanService;
use App\Services\Payments\LoanBalanceService;
use App\Services\Payments\PaymentAllocationService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $organisation = Organisation::firstOrCreate(
            ['slug' => 'lendflow'],
            [
                'name'         => 'LendFlow',
                'country_code' => 'KE',
                'currency'     => 'KES',
                'timezone'     => 'Africa/Nairobi',
                'is_active'    => true,
            ]
        );

        // ── Permissions ──────────────────────────────────────────────────────
        $permissions = [
            // Dashboard
            ['name' => 'view-dashboard',        'display_name' => 'View Dashboard'],

            // Borrowers
            ['name' => 'view-borrowers',         'display_name' => 'View Borrowers'],
            ['name' => 'create-borrowers',       'display_name' => 'Create Borrowers'],
            ['name' => 'edit-borrowers',         'display_name' => 'Edit Borrowers'],
            ['name' => 'delete-borrowers',       'display_name' => 'Delete Borrowers'],

            // Loans
            ['name' => 'view-loans',             'display_name' => 'View Loans'],
            ['name' => 'create-loans',           'display_name' => 'Create Loans'],
            ['name' => 'approve-loans',          'display_name' => 'Approve Loans'],
            ['name' => 'disburse-loans',         'display_name' => 'Disburse Loans'],
            ['name' => 'edit-loans',             'display_name' => 'Edit Loans'],
            ['name' => 'loans.submit', 'display_name' => 'Submit loan applications'],
            ['name' => 'loans.approve.first_level', 'display_name' => 'First-level loan approval'],
            ['name' => 'loans.approve.final', 'display_name' => 'Final loan approval'],
            ['name' => 'loans.return', 'display_name' => 'Return applications for correction'],
            ['name' => 'loans.reject', 'display_name' => 'Reject loan applications'],
            ['name' => 'loans.disburse', 'display_name' => 'Disburse approved loans'],
            ['name' => 'collateral.verify', 'display_name' => 'Independently verify collateral'],
            ['name' => 'branches.manage', 'display_name' => 'Manage branches'],
            ['name' => 'borrowers.verify_kyc', 'display_name' => 'Verify borrower identity documents'],

            // Payments
            ['name' => 'view-payments',          'display_name' => 'View Payments'],
            ['name' => 'record-payments',        'display_name' => 'Record Payments'],

            // Reports
            ['name' => 'view-reports',           'display_name' => 'View Reports'],

            // Settings
            ['name' => 'manage-settings',        'display_name' => 'Manage Settings'],
            ['name' => 'manage-users',           'display_name' => 'Manage Users'],
            ['name' => 'manage-roles',           'display_name' => 'Manage Roles'],
        ];

        foreach ($permissions as $data) {
            Permission::firstOrCreate(
                ['name' => $data['name']],
                array_merge($data, ['is_system' => true, 'is_active' => true])
            );
        }

        $allPermissions = Permission::pluck('id');

        // ── Roles ─────────────────────────────────────────────────────────────
        $superAdmin = Role::firstOrCreate(
            ['organisation_id' => $organisation->id, 'name' => 'super-admin'],
            [
                'display_name' => 'Super Admin',
                'description'  => 'Full unrestricted access.',
                'is_system'    => true,
                'is_active'    => true,
            ]
        );
        $superAdmin->permissions()->sync($allPermissions);

        $admin = Role::firstOrCreate(
            ['organisation_id' => $organisation->id, 'name' => 'admin'],
            [
                'display_name' => 'Admin',
                'description'  => 'Administrative access excluding system settings.',
                'is_system'    => true,
                'is_active'    => true,
            ]
        );
        $adminPermissions = Permission::whereIn('name', [
            'view-dashboard', 'view-borrowers', 'create-borrowers', 'edit-borrowers',
            'view-loans', 'create-loans', 'approve-loans', 'disburse-loans', 'edit-loans',
            'view-payments', 'record-payments', 'view-reports', 'manage-users',
            'loans.submit', 'loans.approve.first_level', 'loans.approve.final', 'loans.return', 'loans.reject',
            'loans.disburse', 'collateral.verify', 'branches.manage', 'borrowers.verify_kyc',
        ])->pluck('id');
        $admin->permissions()->sync($adminPermissions);

        $officer = Role::firstOrCreate(
            ['organisation_id' => $organisation->id, 'name' => 'loan-officer'],
            [
                'display_name' => 'Loan Officer',
                'description'  => 'Day-to-day loan processing.',
                'is_system'    => true,
                'is_active'    => true,
            ]
        );
        $officerPermissions = Permission::whereIn('name', [
            'view-dashboard', 'view-borrowers', 'create-borrowers', 'edit-borrowers',
            'view-loans', 'create-loans', 'view-payments', 'record-payments', 'loans.submit',
        ])->pluck('id');
        $officer->permissions()->sync($officerPermissions);

        $supervisor = Role::firstOrCreate(
            ['organisation_id' => $organisation->id, 'name' => 'supervisor'],
            [
                'display_name' => 'Supervisor',
                'description' => 'Verifies loan applications and provides first-level approval.',
                'is_system' => true,
                'is_active' => true,
            ]
        );
        $supervisor->permissions()->sync(Permission::whereIn('name', [
            'view-dashboard', 'view-borrowers', 'create-borrowers', 'view-loans', 'create-loans',
            'borrowers.verify_kyc', 'loans.submit', 'loans.approve.first_level', 'loans.return',
            'loans.reject', 'collateral.verify',
        ])->pluck('id'));

        $mainBranch = Branch::firstOrCreate(
            ['organisation_id' => $organisation->id, 'code' => 'MAIN'],
            ['name' => 'Main Branch', 'is_active' => true]
        );

        // ── Users ─────────────────────────────────────────────────────────────
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@lendflow.com'],
            [
                'organisation_id' => $organisation->id,
                'name'            => 'Martin Admin',
                'password'        => 'Password123!',
                'is_active'       => true,
            ]
        );
        $adminUser->roles()->syncWithPivotValues(
            [$admin->id],
            ['organisation_id' => $organisation->id]
        );

        $testUser = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'organisation_id' => $organisation->id,
                'branch_id' => $mainBranch->id,
                'name'            => 'Test User',
                'password'        => 'password',
                'is_active'       => true,
            ]
        );
        $testUser->roles()->syncWithPivotValues(
            [$officer->id],
            ['organisation_id' => $organisation->id]
        );

        // ── Borrower Tiers ────────────────────────────────────────────────────
        $tiers = [
            ['tier_number' => 1, 'name' => 'Bronze', 'max_loan_amount_cents' => 5000000,  'min_loan_amount_cents' => 100000,  'max_term_days' => 30,  'is_entry_tier' => true,  'minimum_financial_score' => 0.40],
            ['tier_number' => 2, 'name' => 'Silver', 'max_loan_amount_cents' => 20000000, 'min_loan_amount_cents' => 500000,  'max_term_days' => 60,  'is_entry_tier' => false, 'minimum_financial_score' => 0.60],
            ['tier_number' => 3, 'name' => 'Gold',   'max_loan_amount_cents' => 75000000, 'min_loan_amount_cents' => 1000000, 'max_term_days' => 90,  'is_entry_tier' => false, 'minimum_financial_score' => 0.80],
            ['tier_number' => 4, 'name' => 'Platinum','max_loan_amount_cents' => 200000000,'min_loan_amount_cents' => 5000000, 'max_term_days' => 180, 'is_entry_tier' => false, 'minimum_financial_score' => 0.90],
        ];
        $tierModels = [];
        foreach ($tiers as $t) {
            $tierModels[$t['tier_number']] = BorrowerTier::firstOrCreate(
                ['organisation_id' => $organisation->id, 'tier_number' => $t['tier_number']],
                array_merge($t, ['organisation_id' => $organisation->id, 'is_active' => true, 'version' => 1])
            );
        }

        // ── Loan Products ─────────────────────────────────────────────────────
        $products = [
            ['code' => 'BG', 'name' => 'Business Growth',  'minimum_amount_cents' => 5000000,  'maximum_amount_cents' => 75000000,  'minimum_term_days' => 30, 'maximum_term_days' => 90],
            ['code' => 'WC', 'name' => 'Working Capital',  'minimum_amount_cents' => 1000000,  'maximum_amount_cents' => 20000000,  'minimum_term_days' => 14, 'maximum_term_days' => 60],
            ['code' => 'AF', 'name' => 'Asset Finance',    'minimum_amount_cents' => 10000000, 'maximum_amount_cents' => 200000000, 'minimum_term_days' => 60, 'maximum_term_days' => 180],
            ['code' => 'PL', 'name' => 'Personal Loan',    'minimum_amount_cents' => 500000,   'maximum_amount_cents' => 10000000,  'minimum_term_days' => 7,  'maximum_term_days' => 30],
        ];
        $productModels = [];
        foreach ($products as $p) {
            $productModels[$p['code']] = LoanProduct::firstOrCreate(
                ['organisation_id' => $organisation->id, 'code' => $p['code']],
                array_merge($p, [
                    'organisation_id'    => $organisation->id,
                    'interest_type'      => 'flat',
                    'interest_rate'      => 0.05,
                    'fee_type'           => 'none',
                    'fee_rate'           => 0,
                    'fixed_fee_cents'    => 0,
                    'repayment_frequency'=> 'monthly',
                    'grace_period_days'  => 3,
                    'requires_appraisal' => true,
                    'requires_statement' => true,
                    'is_active'          => true,
                    'version'            => 1,
                ])
            );
        }

        // ── Loan Tier Rules ───────────────────────────────────────────────────
        foreach ($productModels as $product) {
            foreach ($tierModels as $tier) {
                LoanTierRule::firstOrCreate(
                    [
                        'organisation_id' => $organisation->id,
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

        // ── Borrower Users & Borrowers ────────────────────────────────────────
        $borrowerData = [
            ['name' => 'John Otieno',   'email' => 'john.otieno@example.com',   'phone' => '254701000001', 'tier' => 2, 'status' => 'active',  'loans' => 4, 'on_time' => 3],
            ['name' => 'Agnes Mwangi',  'email' => 'agnes.mwangi@example.com',  'phone' => '254701000002', 'tier' => 2, 'status' => 'active',  'loans' => 3, 'on_time' => 3],
            ['name' => 'Peter Kamau',   'email' => 'peter.kamau@example.com',   'phone' => '254701000003', 'tier' => 3, 'status' => 'active',  'loans' => 7, 'on_time' => 5],
            ['name' => 'Grace Wanjiku', 'email' => 'grace.wanjiku@example.com', 'phone' => '254701000004', 'tier' => 1, 'status' => 'active',  'loans' => 1, 'on_time' => 0],
            ['name' => 'Samuel Kiptoo', 'email' => 'samuel.kiptoo@example.com', 'phone' => '254701000005', 'tier' => 1, 'status' => 'active',  'loans' => 0, 'on_time' => 0],
            ['name' => 'Mary Njeri',    'email' => 'mary.njeri@example.com',    'phone' => '254701000006', 'tier' => 3, 'status' => 'active',  'loans' => 6, 'on_time' => 6],
            ['name' => 'David Ochieng', 'email' => 'david.ochieng@example.com', 'phone' => '254701000007', 'tier' => 2, 'status' => 'active',  'loans' => 5, 'on_time' => 4],
            ['name' => 'Faith Akinyi',  'email' => 'faith.akinyi@example.com',  'phone' => '254701000008', 'tier' => 1, 'status' => 'active',  'loans' => 2, 'on_time' => 1],
        ];

        $borrowerModels = [];
        foreach ($borrowerData as $bd) {
            $user = User::updateOrCreate(
                ['email' => $bd['email']],
                [
                    'organisation_id' => $organisation->id,
                    'name'            => $bd['name'],
                    'password'        => 'password',
                    'is_active'       => true,
                ]
            );
            $user->roles()->syncWithPivotValues([$officer->id], ['organisation_id' => $organisation->id]);

            $borrowerModels[$bd['name']] = Borrower::updateOrCreate(
                ['organisation_id' => $organisation->id, 'user_id' => $user->id],
                [
                    'phone_number'            => $bd['phone'],
                    'account_status'          => $bd['status'],
                    'current_tier_id'         => $tierModels[$bd['tier']]->id,
                    'lifetime_loans_count'    => $bd['loans'],
                    'successful_loans_count'  => $bd['on_time'],
                    'on_time_loans_count'     => $bd['on_time'],
                    'consecutive_on_time_loans'=> max(0, $bd['on_time'] - 1),
                    'late_loans_count'        => max(0, $bd['loans'] - $bd['on_time'] - 1),
                    'defaulted_loans_count'   => $bd['name'] === 'Grace Wanjiku' ? 1 : 0,
                    'behaviour_score'         => $bd['on_time'] > 0 ? round($bd['on_time'] / max($bd['loans'], 1) * 100, 4) : 0,
                    'registered_at'           => now()->subMonths(rand(3, 18)),
                ]
            );
        }

        // ── Loan Applications & Loans ─────────────────────────────────────────
        $appData = [
            [
                'borrower'   => 'John Otieno',
                'product'    => 'BG',
                'amount'     => 35000000,
                'term'       => 60,
                'app_status' => 'under_review',
                'decision'   => null,
                'risk'       => 'Low',
                'loan_status'=> null,
            ],
            [
                'borrower'   => 'Agnes Mwangi',
                'product'    => 'WC',
                'amount'     => 18000000,
                'term'       => 30,
                'app_status' => 'approved',
                'decision'   => 'approved',
                'risk'       => 'Low',
                'loan_status'=> 'active',
            ],
            [
                'borrower'   => 'Peter Kamau',
                'product'    => 'AF',
                'amount'     => 72000000,
                'term'       => 90,
                'app_status' => 'under_review',
                'decision'   => null,
                'risk'       => 'Medium',
                'loan_status'=> null,
            ],
            [
                'borrower'   => 'Grace Wanjiku',
                'product'    => 'PL',
                'amount'     => 9500000,
                'term'       => 30,
                'app_status' => 'under_review',
                'decision'   => null,
                'risk'       => 'High',
                'loan_status'=> null,
            ],
            [
                'borrower'   => 'Mary Njeri',
                'product'    => 'BG',
                'amount'     => 50000000,
                'term'       => 90,
                'app_status' => 'approved',
                'decision'   => 'approved',
                'risk'       => 'Low',
                'loan_status'=> 'active',
            ],
            [
                'borrower'   => 'David Ochieng',
                'product'    => 'WC',
                'amount'     => 15000000,
                'term'       => 30,
                'app_status' => 'approved',
                'decision'   => 'approved',
                'risk'       => 'Low',
                'loan_status'=> 'overdue',
            ],
        ];

        foreach ($appData as $ad) {
            $borrower = $borrowerModels[$ad['borrower']];
            $product  = $productModels[$ad['product']];
            $tierRule = LoanTierRule::where([
                'organisation_id' => $organisation->id,
                'loan_product_id' => $product->id,
                'borrower_tier_id'=> $borrower->current_tier_id,
            ])->first();

            $app = LoanApplication::firstOrCreate(
                [
                    'organisation_id' => $organisation->id,
                    'borrower_id'     => $borrower->id,
                    'loan_product_id' => $product->id,
                ],
                [
                    'loan_tier_rule_id'      => $tierRule?->id,
                    'requested_amount_cents' => $ad['amount'],
                    'requested_term_days'    => $ad['term'],
                    'approved_amount_cents'  => $ad['decision'] === 'approved' ? $ad['amount'] : null,
                    'approved_term_days'     => $ad['decision'] === 'approved' ? $ad['term'] : null,
                    'status'                 => $ad['app_status'],
                    'decision'               => $ad['decision'],
                    'financial_score'        => rand(60, 95) / 100,
                    'behaviour_score'        => $borrower->behaviour_score,
                    'submitted_at'           => now()->subDays(rand(1, 14)),
                    'decided_at'             => $ad['decision'] ? now()->subDays(rand(0, 3)) : null,
                ]
            );

            if ($ad['loan_status'] && !Loan::where(['organisation_id' => $organisation->id, 'loan_application_id' => $app->id])->exists()) {
                $principal = $ad['amount'];
                $interest  = (int) ($principal * 0.05);
                $loan = Loan::create([
                    'organisation_id'    => $organisation->id,
                    'borrower_id'        => $borrower->id,
                    'loan_application_id'=> $app->id,
                    'loan_product_id'    => $product->id,
                    'loan_tier_rule_id'  => $tierRule?->id,
                    'principal_cents'    => $principal,
                    'interest_rate'      => 0.05,
                    'interest_cents'     => $interest,
                    'fees_cents'         => 0,
                    'total_payable_cents'=> $principal + $interest,
                    'term_days'          => $ad['term'],
                    'repayment_frequency'=> 'monthly',
                    'status'             => $ad['loan_status'],
                    'approved_at'        => now()->subDays(rand(5, 20)),
                    'disbursed_at'       => now()->subDays(rand(1, 10)),
                    'matures_at'         => now()->addDays($ad['term']),
                ]);

                // Seed one payment for active loans
                if ($ad['loan_status'] === 'active') {
                    LoanPayment::create([
                        'organisation_id'     => $organisation->id,
                        'loan_id'             => $loan->id,
                        'payment_method'      => 'mpesa',
                        'mpesa_receipt_number'=> strtoupper(Str::random(10)),
                        'amount_cents'        => 2500000,
                        'status'              => 'posted',
                        'received_at'         => now()->subMinutes(rand(10, 120)),
                        'posted_at'           => now()->subMinutes(rand(5, 60)),
                    ]);
                }
            }
        }

        // Keep demo loans and payments on the same lifecycle as production data.
        $loanService = app(LoanService::class);
        $allocationService = app(PaymentAllocationService::class);
        $balanceService = app(LoanBalanceService::class);
        Loan::where('organisation_id', $organisation->id)
            ->whereIn('status', ['active', 'overdue', 'completed'])
            ->get()
            ->each(function (Loan $loan) use ($loanService, $allocationService, $balanceService): void {
                $loanService->ensureSchedule($loan);
                $loan->payments()->where('status', 'posted')->get()->each(function (LoanPayment $payment) use ($allocationService, $balanceService): void {
                    $allocationService->allocate($payment);
                    $balanceService->refreshAfterPayment($payment);
                });
            });

        // ── System Events (Activity Feed) ─────────────────────────────────────
        $events = [
            [
                'event_type'     => 'loan.approved',
                'aggregate_type' => 'Loan',
                'payload'        => ['borrower' => 'Agnes Mwangi', 'amount_cents' => 18000000],
                'occurred_at'    => now()->subMinutes(8),
            ],
            [
                'event_type'     => 'payment.received',
                'aggregate_type' => 'LoanPayment',
                'payload'        => ['method' => 'M-Pesa', 'amount_cents' => 2500000],
                'occurred_at'    => now()->subMinutes(21),
            ],
            [
                'event_type'     => 'borrower.registered',
                'aggregate_type' => 'Borrower',
                'payload'        => ['borrower' => 'Samuel Kiptoo'],
                'occurred_at'    => now()->subMinutes(43),
            ],
            [
                'event_type'     => 'loan.delinquency_alert',
                'aggregate_type' => 'Loan',
                'payload'        => ['loan_ref' => 'LN-28391', 'days_overdue' => 14],
                'occurred_at'    => now()->subHour(),
            ],
            [
                'event_type'     => 'borrower.tier_upgraded',
                'aggregate_type' => 'Borrower',
                'payload'        => ['borrower' => 'Mary Njeri', 'from_tier' => 'Silver', 'to_tier' => 'Gold'],
                'occurred_at'    => now()->subHours(2),
            ],
        ];

        foreach ($events as $e) {
            $key = $organisation->id . '-' . $e['event_type'] . '-' . $e['occurred_at']->timestamp;
            SystemEvent::firstOrCreate(
                ['organisation_id' => $organisation->id, 'event_id' => $key],
                array_merge($e, [
                    'organisation_id' => $organisation->id,
                    'event_id'        => $key,
                    'idempotency_key' => $key,
                    'status'          => 'processed',
                    'processed_at'    => $e['occurred_at'],
                ])
            );
        }
    }
}
