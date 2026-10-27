<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_login_redirects_to_the_branch_dashboard(): void
    {
        [$user] = $this->makeSupervisor();

        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('supervisor.dashboard'));
    }

    public function test_supervisor_dashboard_shows_branch_operations_and_scoped_navigation(): void
    {
        [$user, $branch] = $this->makeSupervisor();

        $this->actingAs($user)
            ->get(route('supervisor.dashboard'))
            ->assertOk()
            ->assertSee('Branch operations')
            ->assertSee($branch->name)
            ->assertSee('Applications to review')
            ->assertSee(route('applications.index'))
            ->assertDontSee(route('users.index'));

        $this->get(route('applications.index'))
            ->assertOk()
            ->assertDontSee('New Application');

        $this->get(route('supervisor.profile.edit'))
            ->assertOk()
            ->assertSee('My Profile');

        $this->get(route('supervisor.guarantors.index'))
            ->assertOk()
            ->assertSee('Guarantor register')
            ->assertSee('Guarantors')
            ->assertSee('No guarantor records found');
    }

    public function test_users_without_supervisor_role_cannot_open_the_supervisor_dashboard(): void
    {
        $organisation = Organisation::create([
            'name' => 'Dashboard Access Test',
            'slug' => 'dashboard-access-test',
        ]);
        $user = User::create([
            'organisation_id' => $organisation->id,
            'name' => 'Regular User',
            'email' => 'regular@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('supervisor.dashboard'))
            ->assertForbidden();
    }

    private function makeSupervisor(): array
    {
        $organisation = Organisation::create([
            'name' => 'Supervisor Dashboard Test',
            'slug' => 'supervisor-dashboard-test',
        ]);
        $branch = Branch::create([
            'organisation_id' => $organisation->id,
            'name' => 'Westlands Branch',
            'code' => 'WEST',
            'is_active' => true,
        ]);
        $user = User::create([
            'organisation_id' => $organisation->id,
            'branch_id' => $branch->id,
            'name' => 'Test Supervisor',
            'email' => 'supervisor@example.test',
            'password' => 'password',
            'is_active' => true,
        ]);
        $role = Role::firstOrCreate(
            ['organisation_id' => $organisation->id, 'name' => 'supervisor'],
            [
                'display_name' => 'Supervisor',
                'is_system' => true,
                'is_active' => true,
            ]
        );
        $user->roles()->syncWithPivotValues([$role->id], ['organisation_id' => $organisation->id]);

        return [$user, $branch];
    }
}
