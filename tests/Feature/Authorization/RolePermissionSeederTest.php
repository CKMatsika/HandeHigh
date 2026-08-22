<?php

namespace Tests\Feature\Authorization;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SdaTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\SdaRole;
use Tests\TestCase;

class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_seeder_creates_exact_standard_roles_with_web_guard(): void
    {
        (new RoleSeeder)->run();

        $expected = RoleSeeder::roles();
        sort($expected);
        $this->assertSame($expected, Role::query()->orderBy('name')->pluck('name')->all());
        $this->assertSame(12, Role::count());
        $this->assertSame(['web'], Role::query()->distinct()->pluck('guard_name')->all());
    }

    public function test_permission_seeder_creates_exact_catalogue_with_web_guard(): void
    {
        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        $expected = RolePermissionSeeder::permissions();
        sort($expected);
        $this->assertSame($expected, Permission::query()->orderBy('name')->pluck('name')->all());
        $this->assertSame(96, Permission::count());
        $this->assertSame(['web'], Permission::query()->distinct()->pluck('guard_name')->all());
    }

    public function test_every_declared_role_exists_and_assignments_are_deterministic(): void
    {
        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        foreach (RolePermissionSeeder::rolePermissions() as $roleName => $permissionNames) {
            $role = Role::findByName($roleName, 'web');
            $expected = $roleName === 'super-admin' ? RolePermissionSeeder::permissions() : $permissionNames;
            sort($expected);

            $this->assertSame($expected, $role->permissions()->orderBy('name')->pluck('name')->all());
        }

        $this->assertContains('users.delete', Role::findByName('school-admin')->permissions->pluck('name')->all());
        $this->assertContains('attendance.create', Role::findByName('teacher')->permissions->pluck('name')->all());
        $this->assertNotContains('users.delete', Role::findByName('teacher')->permissions->pluck('name')->all());
        $this->assertNotContains('dashboard.headmaster', Role::findByName('accountant')->permissions->pluck('name')->all());
    }

    public function test_role_and_permission_seeders_are_idempotent(): void
    {
        $roleSeeder = new RoleSeeder;
        $permissionSeeder = new RolePermissionSeeder;

        $roleSeeder->run();
        $permissionSeeder->run();
        $roleSeeder->run();
        $permissionSeeder->run();

        $this->assertSame(12, Role::count());
        $this->assertSame(96, Permission::count());
        $this->assertSame(12, Role::query()->select(['name', 'guard_name'])->distinct()->count());
        $this->assertSame(96, Permission::query()->select(['name', 'guard_name'])->distinct()->count());
    }

    public function test_database_seeder_runs_structural_authorization_without_demo_users(): void
    {
        (new DatabaseSeeder)->run();
        (new DatabaseSeeder)->run();

        $this->assertSame(12, Role::count());
        $this->assertSame(96, Permission::count());
        $this->assertDatabaseCount('users', 0);
    }

    public function test_seeded_permissions_are_available_after_cache_refresh(): void
    {
        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->assertTrue(Role::findByName('super-admin')->hasPermissionTo('users.delete'));
        $this->assertTrue(Role::findByName('teacher')->hasPermissionTo('attendance.create'));
    }

    public function test_sda_test_seeder_is_disabled_in_production(): void
    {
        $originalEnvironment = app()->environment();
        app()->detectEnvironment(fn () => 'production');
        $initialSdaRoleCount = SdaRole::count();

        try {
            (new SdaTestSeeder)->run();

            $this->assertDatabaseCount('users', 0);
            $this->assertSame($initialSdaRoleCount, SdaRole::count());
        } finally {
            app()->detectEnvironment(fn () => $originalEnvironment);
        }
    }
}
