<?php

namespace Tests\Feature;

use App\Models\Borrower;
use App\Models\BorrowerTier;
use App\Models\LoanProduct;
use App\Models\LoanTierRule;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganisationLendingTierSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tier_update_changes_limits_and_versions_default_rule(): void
    {
        [$user, $tier, $product, $rule] = $this->makeLendingSetup();

        $this->actingAs($user)
            ->put(route('organisation.lending.tiers.update', $tier), [
                'name' => 'Bronze Plus',
                'min_loan_amount' => '250.75',
                'max_loan_amount' => '12000.50',
                'acceptance_percentage' => '75.50',
                'max_term_days' => 25,
            ])
            ->assertRedirect(route('organisation.lending'))
            ->assertSessionHas('success');

        $tier->refresh();
        $this->assertSame('Bronze Plus', $tier->name);
        $this->assertSame(25075, $tier->min_loan_amount_cents);
        $this->assertSame(1200050, $tier->max_loan_amount_cents);
        $this->assertSame(25, $tier->max_term_days);
        $this->assertSame('0.7550', $tier->minimum_financial_score);
        $this->assertSame(2, $tier->version);

        $this->assertDatabaseHas('loan_tier_rules', [
            'id' => $rule->id,
            'is_active' => false,
            'version' => 1,
        ]);
        $this->assertDatabaseHas('loan_tier_rules', [
            'organisation_id' => $user->organisation_id,
            'loan_product_id' => $product->id,
            'borrower_tier_id' => $tier->id,
            'maximum_amount_cents' => 1200050,
            'maximum_term_days' => 25,
            'version' => 2,
            'is_active' => true,
        ]);
    }

    public function test_tier_update_rejects_minimum_above_maximum_without_saving(): void
    {
        [$user, $tier] = $this->makeLendingSetup();

        $this->actingAs($user)
            ->from(route('organisation.lending'))
            ->put(route('organisation.lending.tiers.update', $tier), [
                'name' => 'Bronze',
                'min_loan_amount' => '15000',
                'max_loan_amount' => '10000',
                'acceptance_percentage' => '75',
                'max_term_days' => 30,
            ])
            ->assertRedirect(route('organisation.lending'))
            ->assertSessionHasErrors('min_loan_amount');

        $this->assertSame(500000, $tier->fresh()->min_loan_amount_cents);
        $this->assertSame(1000000, $tier->fresh()->max_loan_amount_cents);
    }

    public function test_tier_can_be_added_and_deleted_when_unused(): void
    {
        [$user, $existingTier] = $this->makeLendingSetup();

        $this->actingAs($user)
            ->post(route('organisation.lending.tiers.store'), [
                'name' => 'Silver',
                'min_loan_amount' => '1000',
                'max_loan_amount' => '5000',
                'acceptance_percentage' => '60',
                'max_term_days' => 60,
            ])
            ->assertRedirect(route('organisation.lending'))
            ->assertSessionHas('success');

        $newTier = BorrowerTier::where('organisation_id', $user->organisation_id)
            ->where('name', 'Silver')
            ->firstOrFail();
        $this->assertSame(2, $newTier->tier_number);
        $this->assertSame(100000, $newTier->min_loan_amount_cents);
        $this->assertSame(500000, $newTier->max_loan_amount_cents);

        $this->delete(route('organisation.lending.tiers.destroy', $newTier))
            ->assertRedirect(route('organisation.lending'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('borrower_tiers', ['id' => $newTier->id]);
        $this->assertTrue((bool) $existingTier->fresh()->is_entry_tier);
    }

    public function test_tier_in_use_by_borrowers_cannot_be_deleted(): void
    {
        [$user, $tier] = $this->makeLendingSetup();

        Borrower::create([
            'organisation_id' => $user->organisation_id,
            'user_id' => $user->id,
            'phone_number' => '254700000001',
            'current_tier_id' => $tier->id,
        ]);

        $this->actingAs($user)
            ->from(route('organisation.lending'))
            ->delete(route('organisation.lending.tiers.destroy', $tier))
            ->assertRedirect(route('organisation.lending'))
            ->assertSessionHasErrors('tiers');

        $this->assertDatabaseHas('borrower_tiers', ['id' => $tier->id]);
    }

    private function makeLendingSetup(): array
    {
        $organisation = Organisation::create([
            'name' => 'Tier Settings Test',
            'slug' => 'tier-settings-test',
        ]);
        $user = User::create([
            'organisation_id' => $organisation->id,
            'name' => 'Tier Settings Admin',
            'email' => 'tier-settings@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);

        $role = Role::create([
            'organisation_id' => $organisation->id,
            'name' => 'admin',
            'display_name' => 'Admin',
            'is_system' => true,
            'is_active' => true,
        ]);
        $user->roles()->syncWithPivotValues([$role->id], ['organisation_id' => $organisation->id]);
        $tier = BorrowerTier::create([
            'organisation_id' => $organisation->id,
            'tier_number' => 1,
            'name' => 'Bronze',
            'max_loan_amount_cents' => 1000000,
            'min_loan_amount_cents' => 500000,
            'max_term_days' => 30,
            'minimum_financial_score' => 0.4,
            'is_entry_tier' => true,
            'is_active' => true,
        ]);
        $product = LoanProduct::create([
            'organisation_id' => $organisation->id,
            'code' => 'TEST',
            'name' => 'Test Product',
            'minimum_amount_cents' => 100000,
            'maximum_amount_cents' => 3000000,
            'minimum_term_days' => 7,
            'maximum_term_days' => 60,
            'interest_rate' => 0.05,
        ]);
        $rule = LoanTierRule::create([
            'organisation_id' => $organisation->id,
            'loan_product_id' => $product->id,
            'borrower_tier_id' => $tier->id,
            'maximum_amount_cents' => 1000000,
            'minimum_amount_cents' => 100000,
            'maximum_term_days' => 30,
            'is_active' => true,
            'version' => 1,
        ]);

        return [$user, $tier, $product, $rule];
    }
}
