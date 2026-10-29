<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Additive permission setup for the read-only academic reconciliation workspace. */
class AcademicStudentReconciliationAccessSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = ['student-reconciliation.view', 'student-reconciliation.upload', 'student-reconciliation.review', 'student-reconciliation.export'];
        foreach ($permissions as $name) Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);

        foreach (['super-admin', 'school-admin'] as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);
            $role->givePermissionTo($permissions);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
