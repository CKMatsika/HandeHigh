<?php

namespace Tests\Feature\Authorization;

use App\Models\School;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private array $adminDashboards = [
        'headmaster' => 'admin.dashboard.headmaster',
        'deputy-headmaster' => 'admin.dashboard.deputy-headmaster',
        'accounts-clerk' => 'admin.dashboard.accounts-clerk',
        'bursar' => 'admin.dashboard.bursar',
        'procurement-officer' => 'admin.dashboard.procurement-officer',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();
        $this->school = School::create(['name' => 'Synthetic Dashboard School', 'code' => 'DASH']);
    }

    public function test_guests_are_redirected_from_every_dashboard(): void
    {
        $routes = array_merge(
            ['dashboard'],
            array_values($this->adminDashboards),
            ['teacher.dashboard', 'student.dashboard', 'parent.dashboard', 'librarian.dashboard']
        );

        foreach ($routes as $route) {
            $this->get(route($route))->assertRedirectToRoute('login');
        }
    }

    public function test_super_admin_can_reach_every_role_specific_admin_dashboard(): void
    {
        $user = $this->userWithRole('super-admin');

        foreach ($this->adminDashboards as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }

    public function test_school_admin_does_not_receive_role_specific_dashboard_permissions(): void
    {
        $user = $this->userWithRole('school-admin');

        foreach ($this->adminDashboards as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_each_role_can_reach_only_its_intended_admin_dashboard(): void
    {
        foreach ($this->adminDashboards as $role => $route) {
            $user = $this->userWithRole($role);

            foreach ($this->adminDashboards as $otherRole => $otherRoute) {
                $response = $this->actingAs($user)->get(route($otherRoute));

                if ($role === $otherRole) {
                    $response->assertOk();
                } else {
                    $response->assertForbidden();
                }
            }
        }
    }

    public function test_portal_roles_cannot_reach_role_specific_admin_dashboards(): void
    {
        foreach (['teacher', 'student', 'parent', 'librarian'] as $role) {
            $user = $this->userWithRole($role);

            foreach ($this->adminDashboards as $route) {
                $this->actingAs($user)->get(route($route))->assertForbidden();
            }
        }
    }

    public function test_generic_dashboard_routes_roles_deterministically(): void
    {
        $destinations = [
            'headmaster' => 'admin.dashboard.headmaster',
            'deputy-headmaster' => 'admin.dashboard.deputy-headmaster',
            'accounts-clerk' => 'admin.dashboard.accounts-clerk',
            'bursar' => 'admin.dashboard.bursar',
            'procurement-officer' => 'admin.dashboard.procurement-officer',
            'teacher' => 'teacher.dashboard',
            'student' => 'student.dashboard',
            'parent' => 'parent.dashboard',
            'librarian' => 'librarian.dashboard',
        ];

        foreach ($destinations as $role => $destination) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('dashboard'))
                ->assertRedirectToRoute($destination);
        }

        foreach (['super-admin', 'school-admin', 'accountant'] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('dashboard'))
                ->assertOk();
        }
    }

    public function test_users_without_a_role_or_school_fail_safely(): void
    {
        $unroled = User::factory()->create(['school_id' => $this->school->id]);
        $this->actingAs($unroled)->get(route('dashboard'))->assertForbidden();

        $noSchool = $this->userWithRole('headmaster', false);
        $this->actingAs($noSchool)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($noSchool)->get(route('admin.dashboard.headmaster'))->assertForbidden();
    }

    private function userWithRole(string $role, bool $withSchool = true): User
    {
        $user = User::factory()->create([
            'school_id' => $withSchool ? $this->school->id : null,
        ]);
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
