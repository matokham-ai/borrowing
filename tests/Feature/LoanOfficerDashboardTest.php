<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Borrower;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\Organisation;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanOfficerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_loan_officer_login_ignores_an_intended_admin_url(): void
    {
        [$user] = $this->makeLoanOfficer();

        $this->from('/admin')
            ->post(route('login.attempt'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('loan-officer.dashboard'));
    }

    public function test_loan_officer_sees_a_personal_dashboard_and_navigation(): void
    {
        [$user, $branch] = $this->makeLoanOfficer();

        $this->actingAs($user)
            ->get(route('loan-officer.dashboard'))
            ->assertOk()
            ->assertSee('My recent applications')
            ->assertSee($branch->name)
            ->assertSee(route('applications.index'))
            ->assertSee(route('borrowers.index'))
            ->assertDontSee(route('users.index'));

        $this->get(route('applications.create'))->assertOk();
        $this->get(route('borrowers.index'))->assertOk();

        $this->get(route('loan-officer.profile.edit'))
            ->assertOk()
            ->assertSee('My Profile');
    }

    public function test_loan_officer_can_view_only_guarantors_from_their_own_applications(): void
    {
        [$user, $branch] = $this->makeLoanOfficer();
        $borrowerUser = User::create([
            'organisation_id' => $user->organisation_id,
            'name' => 'Borrower One',
            'email' => 'borrower-one@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $borrower = Borrower::create([
            'organisation_id' => $user->organisation_id,
            'branch_id' => $branch->id,
            'registered_by' => $user->id,
            'user_id' => $borrowerUser->id,
            'phone_number' => '254700000111',
        ]);
        $product = LoanProduct::create([
            'organisation_id' => $user->organisation_id,
            'code' => 'GUAR',
            'name' => 'Guarantor Test Loan',
            'minimum_amount_cents' => 10000,
            'maximum_amount_cents' => 1000000,
            'minimum_term_days' => 7,
            'maximum_term_days' => 90,
            'interest_rate' => 0.05,
        ]);

        $application = LoanApplication::create([
            'organisation_id' => $user->organisation_id,
            'branch_id' => $branch->id,
            'created_by' => $user->id,
            'borrower_id' => $borrower->id,
            'loan_product_id' => $product->id,
            'requested_amount_cents' => 100000,
            'requested_term_days' => 30,
            'status' => 'submitted',
            'security_data' => [
                'guarantor' => [
                    'name' => 'Visible Guarantor',
                    'phone' => '254700000222',
                    'relationship' => 'Sibling',
                ],
            ],
        ]);
        LoanApplication::create([
            'organisation_id' => $user->organisation_id,
            'branch_id' => $branch->id,
            'created_by' => User::create([
                'organisation_id' => $user->organisation_id,
                'branch_id' => $branch->id,
                'name' => 'Another Officer',
                'email' => 'another-officer@example.test',
                'password' => 'password',
                'is_active' => true,
            ])->id,
            'borrower_id' => $borrower->id,
            'loan_product_id' => $product->id,
            'requested_amount_cents' => 100000,
            'requested_term_days' => 30,
            'status' => 'submitted',
            'security_data' => ['guarantor' => ['name' => 'Hidden Guarantor']],
        ]);

        $this->actingAs($user)
            ->get(route('loan-officer.dashboard'))
            ->assertOk()
            ->assertSee(route('loan-officer.guarantors.index'));

        $this->get(route('loan-officer.guarantors.index'))
            ->assertOk()
            ->assertSee('My guarantors')
            ->assertSee('Visible Guarantor')
            ->assertSee(route('applications.show', $application))
            ->assertDontSee('Hidden Guarantor');
    }

    private function makeLoanOfficer(): array
    {
        $organisation = Organisation::create([
            'name' => 'Loan Officer Dashboard Test',
            'slug' => 'loan-officer-dashboard-test',
        ]);
        $branch = Branch::create([
            'organisation_id' => $organisation->id,
            'name' => 'Central Branch',
            'code' => 'CENTRAL',
            'is_active' => true,
        ]);
        $user = User::create([
            'organisation_id' => $organisation->id,
            'branch_id' => $branch->id,
            'name' => 'Test Loan Officer',
            'email' => 'officer@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $role = Role::firstOrCreate(
            ['organisation_id' => $organisation->id, 'name' => 'loan-officer'],
            [
                'display_name' => 'Loan Officer',
                'is_system' => true,
                'is_active' => true,
            ]
        );
        $permissions = collect([
            'view-dashboard' => 'View Dashboard',
            'view-borrowers' => 'View Borrowers',
            'create-borrowers' => 'Create Borrowers',
            'edit-borrowers' => 'Edit Borrowers',
            'view-loans' => 'View Loans',
            'create-loans' => 'Create Loans',
            'loans.submit' => 'Submit loan applications',
        ])->map(fn ($displayName, $name) => Permission::firstOrCreate(
            ['name' => $name],
            ['display_name' => $displayName, 'is_system' => true, 'is_active' => true]
        )->id);
        $role->permissions()->sync($permissions);
        $user->roles()->syncWithPivotValues([$role->id], ['organisation_id' => $organisation->id]);

        return [$user, $branch];
    }
}
