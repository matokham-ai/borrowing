<?php

namespace Tests\Feature;

use App\Models\LoanProduct;
use App\Models\Organisation;
use Database\Seeders\LoanProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanProductSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_adds_six_inactive_monthly_pricing_drafts_without_overwriting_existing_products(): void
    {
        $organisation = Organisation::create([
            'name' => 'LendFlow',
            'slug' => 'lendflow',
        ]);
        $existing = LoanProduct::create([
            'organisation_id' => $organisation->id,
            'code' => 'BG',
            'name' => 'Business Growth',
            'minimum_amount_cents' => 5000000,
            'maximum_amount_cents' => 75000000,
            'minimum_term_days' => 30,
            'maximum_term_days' => 90,
            'interest_type' => 'flat',
            'interest_rate' => 0.05,
            'is_active' => true,
            'version' => 4,
        ]);

        $this->seed(LoanProductSeeder::class);

        $products = LoanProduct::withoutGlobalScopes()
            ->where('organisation_id', $organisation->id)
            ->get()
            ->keyBy('code');

        $this->assertCount(7, $products);
        $this->assertSame('Business Growth', $products['BG']->name);
        $this->assertTrue($products['BG']->is_active);
        $this->assertSame(4, $products['BG']->version);
        $this->assertSame($existing->id, $products['BG']->id);

        foreach (['EL', 'PL', 'BWC', 'BG-PROP', 'AF-PROP', 'KA'] as $code) {
            $this->assertFalse($products[$code]->is_active);
            $this->assertSame('per_month', $products[$code]->interest_period);
        }

        $this->assertSame('reducing_balance', $products['BG-PROP']->interest_type);
        $this->assertSame(0.03, (float) $products['BG-PROP']->interest_rate);

        $products['EL']->name = 'Admin-edited emergency product';
        $products['EL']->save();

        $this->seed(LoanProductSeeder::class);

        $this->assertSame(
            'Admin-edited emergency product',
            LoanProduct::withoutGlobalScopes()->where('organisation_id', $organisation->id)->where('code', 'EL')->value('name')
        );
        $this->assertCount(7, LoanProduct::withoutGlobalScopes()->where('organisation_id', $organisation->id)->get());
    }
}
