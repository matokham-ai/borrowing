<?php

namespace Database\Seeders;

use App\Models\LoanProduct;
use App\Models\Organisation;
use Illuminate\Database\Seeder;

class LoanProductSeeder extends Seeder
{
    public function run(): void
    {
        $organisation = Organisation::withoutGlobalScopes()
            ->where('slug', 'lendflow')
            ->firstOrFail();

        $products = [
            [
                'code' => 'EL',
                'name' => 'Emergency Loan',
                'description' => 'Short-term financing for urgent personal and household needs.',
                'minimum_amount_cents' => 200_000,
                'maximum_amount_cents' => 3_000_000,
                'minimum_term_days' => 14,
                'maximum_term_days' => 60,
                'interest_type' => 'flat',
                'interest_period' => 'per_month',
                'interest_rate' => 0.05,
                'repayment_frequency' => 'weekly',
                'requires_statement' => false,
            ],
            [
                'code' => 'PL',
                'name' => 'Personal Loan',
                'description' => 'Personal credit for planned expenses and household needs.',
                'minimum_amount_cents' => 500_000,
                'maximum_amount_cents' => 10_000_000,
                'minimum_term_days' => 30,
                'maximum_term_days' => 180,
                'interest_type' => 'flat',
                'interest_period' => 'per_month',
                'interest_rate' => 0.04,
                'repayment_frequency' => 'monthly',
                'requires_statement' => true,
            ],
            [
                'code' => 'BWC',
                'name' => 'Biashara Working Capital',
                'description' => 'Short-term working capital for traders and small businesses.',
                'minimum_amount_cents' => 1_000_000,
                'maximum_amount_cents' => 25_000_000,
                'minimum_term_days' => 30,
                'maximum_term_days' => 180,
                'interest_type' => 'flat',
                'interest_period' => 'per_month',
                'interest_rate' => 0.04,
                'repayment_frequency' => 'weekly',
                'requires_statement' => true,
            ],
            [
                // BG is already used by the existing Business Growth product.
                'code' => 'BG-PROP',
                'name' => 'Business Growth Loan',
                'description' => 'Growth capital for established micro and small enterprises.',
                'minimum_amount_cents' => 2_500_000,
                'maximum_amount_cents' => 100_000_000,
                'minimum_term_days' => 90,
                'maximum_term_days' => 365,
                'interest_type' => 'reducing_balance',
                'interest_period' => 'per_month',
                'interest_rate' => 0.03,
                'repayment_frequency' => 'monthly',
                'requires_statement' => true,
            ],
            [
                // AF is already used by the existing Asset Finance product.
                'code' => 'AF-PROP',
                'name' => 'Asset Finance',
                'description' => 'Financing for productive equipment, machinery and business assets.',
                'minimum_amount_cents' => 2_000_000,
                'maximum_amount_cents' => 200_000_000,
                'minimum_term_days' => 90,
                'maximum_term_days' => 730,
                'interest_type' => 'reducing_balance',
                'interest_period' => 'per_month',
                'interest_rate' => 0.025,
                'repayment_frequency' => 'monthly',
                'requires_statement' => true,
            ],
            [
                'code' => 'KA',
                'name' => 'Kilimo Advance',
                'description' => 'Seasonal financing for farm inputs and agricultural production.',
                'minimum_amount_cents' => 500_000,
                'maximum_amount_cents' => 30_000_000,
                'minimum_term_days' => 60,
                'maximum_term_days' => 270,
                'interest_type' => 'flat',
                'interest_period' => 'per_month',
                'interest_rate' => 0.03,
                'repayment_frequency' => 'monthly',
                'requires_statement' => false,
            ],
        ];

        foreach ($products as $product) {
            LoanProduct::withoutGlobalScopes()->firstOrCreate(
                [
                    'organisation_id' => $organisation->id,
                    'code' => $product['code'],
                ],
                array_merge($product, [
                    'organisation_id' => $organisation->id,
                    'fee_type' => 'none',
                    'fee_rate' => 0,
                    'fixed_fee_cents' => 0,
                    'grace_period_days' => 0,
                    'requires_appraisal' => true,
                    'is_active' => false,
                    'version' => 1,
                ])
            );
        }

        $this->command?->info(count($products) . ' proposed LendFlow loan products are available as inactive drafts.');
    }
}
