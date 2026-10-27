<?php

namespace Tests\Feature;

use App\Models\LoanProduct;
use App\Models\BorrowerTier;
use App\Models\LoanTierRule;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLendingNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_loan_products_and_only_sees_their_organisation_products(): void
    {
        [$admin, $organisation] = $this->makeUserWithRole('admin', 'Products Admin');
        $otherOrganisation = Organisation::create([
            'name' => 'Other Organisation',
            'slug' => 'other-organisation',
        ]);

        LoanProduct::create([
            'organisation_id' => $organisation->id,
            'code' => 'LOCAL',
            'name' => 'Local Product',
            'minimum_amount_cents' => 100000,
            'maximum_amount_cents' => 500000,
            'minimum_term_days' => 7,
            'maximum_term_days' => 30,
            'interest_rate' => 0.05,
        ]);
        LoanProduct::create([
            'organisation_id' => $otherOrganisation->id,
            'code' => 'OTHER',
            'name' => 'Other Tenant Product',
            'minimum_amount_cents' => 100000,
            'maximum_amount_cents' => 500000,
            'minimum_term_days' => 7,
            'maximum_term_days' => 30,
            'interest_rate' => 0.05,
        ]);

        $this->actingAs($admin)
            ->get(route('loan-products.index'))
            ->assertOk()
            ->assertSee('Local Product')
            ->assertDontSee('Other Tenant Product')
            ->assertSee('href="' . route('loan-products.index') . '"', false);
    }

    public function test_admin_can_open_payments(): void
    {
        [$admin] = $this->makeUserWithRole('admin', 'Payments Admin');

        $this->actingAs($admin)
            ->get(route('payments.index'))
            ->assertOk()
            ->assertSee('href="' . route('payments.index') . '"', false);
    }

    public function test_repayments_navigation_opens_incoming_repayments(): void
    {
        [$admin] = $this->makeUserWithRole('admin', 'Repayments Admin');

        $this->actingAs($admin)
            ->get(route('loans.repayments'))
            ->assertRedirect(route('payments.index', ['view' => 'incoming']));

        $this->get(route('payments.index', ['view' => 'incoming']))
            ->assertOk()
            ->assertSee('Incoming payments')
            ->assertSee('href="' . route('loans.repayments') . '"', false);
    }

    public function test_payment_pages_remain_restricted_to_admin_roles(): void
    {
        [$loanOfficer] = $this->makeUserWithRole('loan-officer', 'Loan Officer');

        $this->actingAs($loanOfficer)
            ->get(route('payments.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_product_with_currency_and_percentage_conversion(): void
    {
        [$admin, $organisation] = $this->makeUserWithRole('admin', 'Create Product Admin');

        $this->actingAs($admin)
            ->post(route('loan-products.store'), $this->productInput([
                'fee_type' => 'percentage',
                'fee_rate' => '2.5',
            ]))
            ->assertRedirect(route('loan-products.index'))
            ->assertSessionHas('success');

        $product = LoanProduct::withoutGlobalScopes()
            ->where('organisation_id', $organisation->id)
            ->where('code', 'NEW-01')
            ->firstOrFail();

        $this->assertSame(123456, $product->minimum_amount_cents);
        $this->assertSame(2000000, $product->maximum_amount_cents);
        $this->assertSame('0.0750', $product->interest_rate);
        $this->assertSame('0.0250', $product->fee_rate);
        $this->assertSame(0, $product->fixed_fee_cents);
        $this->assertSame(1, $product->version);
        $this->assertSame('per_month', $product->interest_period);
        $this->assertFalse($product->is_active);
    }

    public function test_product_creation_rejects_invalid_limits_and_duplicate_codes_in_the_same_organisation(): void
    {
        [$admin, $organisation] = $this->makeUserWithRole('admin', 'Product Validation Admin');
        $this->createProduct($organisation, ['code' => 'DUPLICATE']);

        $this->actingAs($admin)
            ->from(route('loan-products.create'))
            ->post(route('loan-products.store'), $this->productInput([
                'code' => 'DUPLICATE',
                'maximum_amount' => '1000',
                'minimum_amount' => '2000',
                'maximum_term_days' => 10,
                'minimum_term_days' => 20,
            ]))
            ->assertRedirect(route('loan-products.create'))
            ->assertSessionHasErrors(['code', 'maximum_amount', 'maximum_term_days']);
    }

    public function test_admin_can_view_and_update_a_product_without_changing_other_tenants(): void
    {
        [$admin, $organisation] = $this->makeUserWithRole('admin', 'Update Product Admin');
        $product = $this->createProduct($organisation);
        $otherOrganisation = Organisation::create([
            'name' => 'Separate Product Organisation',
            'slug' => 'separate-product-organisation',
        ]);
        $otherProduct = $this->createProduct($otherOrganisation, [
            'code' => 'OTHER',
            'name' => 'Other Product',
        ]);

        $this->actingAs($admin)
            ->get(route('loan-products.show', $product))
            ->assertOk()
            ->assertSee('Local Product');

        $this->actingAs($admin)
            ->get(route('loan-products.edit', $product))
            ->assertOk()
            ->assertSee('Edit loan product');

        $this->actingAs($admin)
            ->put(route('loan-products.update', $product), $this->productInput([
                'name' => 'Updated Product',
                'interest_rate' => '8.25',
            ]))
            ->assertRedirect(route('loan-products.index'))
            ->assertSessionHas('success');

        $this->assertSame('Updated Product', $product->fresh()->name);
        $this->assertSame('0.0825', $product->fresh()->interest_rate);
        $this->assertSame(2, $product->fresh()->version);
        $this->assertSame('Other Product', $otherProduct->fresh()->name);
    }

    public function test_admin_can_toggle_product_status_and_version_increases(): void
    {
        [$admin, $organisation] = $this->makeUserWithRole('admin', 'Toggle Product Admin');
        $product = $this->createProduct($organisation);

        $this->actingAs($admin)
            ->patch(route('loan-products.toggle-status', $product))
            ->assertRedirect(route('loan-products.index'))
            ->assertSessionHas('success');

        $this->assertFalse($product->fresh()->is_active);
        $this->assertSame(2, $product->fresh()->version);

        $this->patch(route('loan-products.toggle-status', $product))
            ->assertRedirect(route('loan-products.index'));

        $this->assertTrue($product->fresh()->is_active);
        $this->assertSame(3, $product->fresh()->version);
    }

    public function test_monthly_or_reducing_balance_products_cannot_be_activated(): void
    {
        [$admin, $organisation] = $this->makeUserWithRole('admin', 'Unsupported Pricing Admin');
        $product = $this->createProduct($organisation, [
            'interest_period' => 'per_month',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->patch(route('loan-products.toggle-status', $product))
            ->assertRedirect(route('loan-products.index'))
            ->assertSessionHas('error');

        $this->assertFalse($product->fresh()->is_active);

        $product->interest_type = 'reducing_balance';
        $product->interest_period = 'per_loan';
        $product->save();

        $this->patch(route('loan-products.toggle-status', $product))
            ->assertRedirect(route('loan-products.index'))
            ->assertSessionHas('error');
        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_admin_can_delete_an_unused_product_but_cannot_delete_a_product_with_tier_rules(): void
    {
        [$admin, $organisation] = $this->makeUserWithRole('admin', 'Delete Product Admin');
        $unusedProduct = $this->createProduct($organisation, ['code' => 'UNUSED']);

        $this->actingAs($admin)
            ->delete(route('loan-products.destroy', $unusedProduct))
            ->assertRedirect(route('loan-products.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('loan_products', ['id' => $unusedProduct->id]);

        $linkedProduct = $this->createProduct($organisation, ['code' => 'LINKED']);
        $tier = BorrowerTier::create([
            'organisation_id' => $organisation->id,
            'tier_number' => 1,
            'name' => 'Bronze',
            'max_loan_amount_cents' => 2000000,
            'min_loan_amount_cents' => 100000,
            'max_term_days' => 30,
            'minimum_financial_score' => 0.4,
            'is_entry_tier' => true,
            'is_active' => true,
        ]);
        LoanTierRule::create([
            'organisation_id' => $organisation->id,
            'loan_product_id' => $linkedProduct->id,
            'borrower_tier_id' => $tier->id,
            'maximum_amount_cents' => 1000000,
            'minimum_amount_cents' => 100000,
            'maximum_term_days' => 30,
            'is_active' => true,
            'version' => 1,
        ]);

        $this->delete(route('loan-products.destroy', $linkedProduct))
            ->assertRedirect(route('loan-products.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('loan_products', ['id' => $linkedProduct->id]);
    }

    public function test_admin_cannot_access_another_organisations_product(): void
    {
        [$admin] = $this->makeUserWithRole('admin', 'Product Isolation Admin');
        $otherOrganisation = Organisation::create([
            'name' => 'Hidden Product Organisation',
            'slug' => 'hidden-product-organisation',
        ]);
        $product = $this->createProduct($otherOrganisation);

        $this->actingAs($admin)
            ->get(route('loan-products.show', $product))
            ->assertNotFound();

        $this->put(route('loan-products.update', $product), $this->productInput())
            ->assertNotFound();
    }

    private function createProduct(Organisation $organisation, array $overrides = []): LoanProduct
    {
        return LoanProduct::withoutGlobalScopes()->create(array_merge([
            'organisation_id' => $organisation->id,
            'code' => 'LOCAL',
            'name' => 'Local Product',
            'minimum_amount_cents' => 100000,
            'maximum_amount_cents' => 500000,
            'minimum_term_days' => 7,
            'maximum_term_days' => 30,
            'interest_type' => 'flat',
            'interest_period' => 'per_loan',
            'interest_rate' => 0.05,
            'fee_type' => 'none',
            'fee_rate' => 0,
            'fixed_fee_cents' => 0,
            'repayment_frequency' => 'monthly',
            'grace_period_days' => 0,
            'requires_appraisal' => true,
            'requires_statement' => true,
            'is_active' => true,
            'version' => 1,
        ], $overrides));
    }

    private function productInput(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Product',
            'code' => 'NEW-01',
            'description' => 'A test lending product.',
            'minimum_amount' => '1234.56',
            'maximum_amount' => '20000',
            'minimum_term_days' => 7,
            'maximum_term_days' => 60,
            'interest_type' => 'flat',
            'interest_period' => 'per_month',
            'interest_rate' => '7.5',
            'fee_type' => 'none',
            'fee_rate' => '0',
            'fixed_fee' => '0',
            'repayment_frequency' => 'monthly',
            'grace_period_days' => 0,
            'requires_appraisal' => 1,
            'requires_statement' => 1,
            'is_active' => 1,
        ], $overrides);
    }

    private function makeUserWithRole(string $roleName, string $name): array
    {
        $organisation = Organisation::create([
            'name' => $name . ' Organisation',
            'slug' => str($name)->slug() . '-organisation',
        ]);
        $user = User::create([
            'organisation_id' => $organisation->id,
            'name' => $name,
            'email' => str($name)->slug() . '@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $role = Role::create([
            'organisation_id' => $organisation->id,
            'name' => $roleName,
            'display_name' => str($roleName)->headline(),
            'is_system' => true,
            'is_active' => true,
        ]);
        $user->roles()->syncWithPivotValues([$role->id], ['organisation_id' => $organisation->id]);

        return [$user, $organisation];
    }
}
