<?php

namespace Tests\Feature\Authorization;

use App\Models\School;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;

    protected function setUp(): void
    {
        parent::setUp();

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        $this->schoolA = School::create(['name' => 'Synthetic School A', 'code' => 'TENANTA']);
        $this->schoolB = School::create(['name' => 'Synthetic School B', 'code' => 'TENANTB']);

        Route::middleware(['web', 'auth', \App\Http\Middleware\ResolveTenant::class])
            ->post('/__tenant-context-test', function (Request $request, TenantContext $tenant) {
                return response()->json([
                    'id' => $tenant->id(),
                    'has_tenant' => $tenant->hasTenant(),
                ]);
            });
    }

    public function test_authenticated_users_resolve_their_persisted_school(): void
    {
        foreach (['school-admin', 'headmaster', 'teacher', 'student', 'parent', 'librarian', 'accountant'] as $role) {
            $user = $this->userWithRole($role, $this->schoolA);

            $this->actingAs($user)
                ->post('/__tenant-context-test')
                ->assertOk()
                ->assertJson(['id' => $this->schoolA->id, 'has_tenant' => true]);
        }
    }

    public function test_super_admin_uses_its_persisted_school_without_an_implicit_bypass(): void
    {
        $user = $this->userWithRole('super-admin', $this->schoolB);

        $this->actingAs($user)
            ->post('/__tenant-context-test', ['school_id' => $this->schoolA->id])
            ->assertJson(['id' => $this->schoolB->id]);
    }

    public function test_body_query_and_header_school_ids_cannot_override_the_tenant(): void
    {
        $user = $this->userWithRole('teacher', $this->schoolA);

        $this->actingAs($user)
            ->withHeaders(['X-School-Id' => (string) $this->schoolB->id])
            ->post('/__tenant-context-test?school_id=' . $this->schoolB->id, [
                'school_id' => $this->schoolB->id,
            ])
            ->assertJson(['id' => $this->schoolA->id]);
    }

    public function test_users_without_a_school_are_denied_tenant_routes(): void
    {
        $user = $this->userWithRole('teacher', null);

        $this->actingAs($user)
            ->post('/__tenant-context-test')
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_context_isolated_between_sequential_requests(): void
    {
        $userA = $this->userWithRole('teacher', $this->schoolA);
        $userB = $this->userWithRole('teacher', $this->schoolB);

        $this->actingAs($userA)->post('/__tenant-context-test')->assertJson(['id' => $this->schoolA->id]);
        $this->actingAs($userB)->post('/__tenant-context-test')->assertJson(['id' => $this->schoolB->id]);
        $this->actingAs($userA)->post('/__tenant-context-test')->assertJson(['id' => $this->schoolA->id]);
    }

    public function test_school_provisioning_is_super_admin_only_and_exempt_from_tenant_presence(): void
    {
        $superAdminWithoutSchool = $this->userWithRole('super-admin', null);
        $schoolAdminWithoutSchool = $this->userWithRole('school-admin', null);

        $this->actingAs($superAdminWithoutSchool)
            ->get(route('admin.schools.index'))
            ->assertOk();

        $this->actingAs($schoolAdminWithoutSchool)
            ->get(route('admin.schools.index'))
            ->assertForbidden();
    }

    public function test_dashboard_and_portal_routes_retain_existing_authorization_and_tenant_middleware(): void
    {
        $routes = app('router')->getRoutes();

        foreach ([
            'dashboard',
            'admin.dashboard.headmaster',
            'admin.dashboard.deputy-headmaster',
            'admin.dashboard.accounts-clerk',
            'admin.dashboard.bursar',
            'admin.dashboard.procurement-officer',
            'teacher.dashboard',
            'student.dashboard',
            'parent.dashboard',
            'librarian.dashboard',
        ] as $name) {
            $middleware = $routes->getByName($name)->gatherMiddleware();
            $this->assertTrue(
                in_array(\App\Http\Middleware\ResolveTenant::class, $middleware, true)
                || in_array('tenant', $middleware, true),
                $name
            );
        }
    }

    public function test_ai_api_routes_remain_unregistered(): void
    {
        $this->assertCount(0, collect(app('router')->getRoutes())->filter(
            fn ($route) => str_starts_with($route->uri(), 'api/ai')
        ));
    }

    private function userWithRole(string $role, ?School $school): User
    {
        $user = User::factory()->create(['school_id' => $school?->id]);
        $user->assignRole(Role::findByName($role, 'web'));

        return $user;
    }
}
