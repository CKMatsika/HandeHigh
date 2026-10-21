<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Database\Seeders\RolePermissionSeeder;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        $guard = config('auth.defaults.guard', 'web');

        // Create or ensure all defined permissions exist
        foreach (RolePermissionSeeder::permissions() as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $guard,
            ]);
        }

        // Grant ALL permissions to super-admin
        $superAdmin = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => $guard,
        ]);
        $superAdmin->syncPermissions(RolePermissionSeeder::permissions());

        // Update school-admin with its defined set
        $rolePermissions = RolePermissionSeeder::rolePermissions();
        if (isset($rolePermissions['school-admin'])) {
            $schoolAdmin = Role::firstOrCreate([
                'name' => 'school-admin',
                'guard_name' => $guard,
            ]);
            $schoolAdmin->syncPermissions($rolePermissions['school-admin']);
        }

        // Update stores-person if present
        if (isset($rolePermissions['stores-person'])) {
            $storesPerson = Role::firstOrCreate([
                'name' => 'stores-person',
                'guard_name' => $guard,
            ]);
            $storesPerson->syncPermissions($rolePermissions['stores-person']);
        }

        $registrar->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op or keep permissions
    }
};
