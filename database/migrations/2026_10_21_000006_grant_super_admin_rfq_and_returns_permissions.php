<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $guard = config('auth.defaults.guard', 'web');
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        $newPermissions = [
            'procurement.rfq.view',
            'procurement.rfq.create',
            'procurement.rfq.edit',
            'procurement.rfq.delete',
            'procurement.rfq.publish',
            'procurement.quotations.view',
            'procurement.quotations.create',
            'procurement.quotations.edit',
            'procurement.quotations.delete',
            'procurement.comparison.view',
            'procurement.comparison.create',
            'procurement.comparison.edit',
            'procurement.comparison.approve',
            'inventory.returns.view',
            'inventory.returns.create',
            'inventory.returns.edit',
            'inventory.returns.delete',
        ];

        foreach ($newPermissions as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => $guard,
            ]);
        }

        // Grant ALL permissions to super-admin
        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', $guard)->first();
        if ($superAdmin) {
            $allPerms = Permission::where('guard_name', $guard)->pluck('name')->all();
            $superAdmin->syncPermissions($allPerms);
        }

        // Grant to school-admin
        $schoolAdmin = Role::where('name', 'school-admin')->where('guard_name', $guard)->first();
        if ($schoolAdmin) {
            $schoolAdmin->givePermissionTo($newPermissions);
        }

        // Grant to procurement-officer
        $procurementOfficer = Role::where('name', 'procurement-officer')->where('guard_name', $guard)->first();
        if ($procurementOfficer) {
            $procurementOfficer->givePermissionTo([
                'procurement.rfq.view',
                'procurement.rfq.create',
                'procurement.rfq.edit',
                'procurement.rfq.delete',
                'procurement.rfq.publish',
                'procurement.quotations.view',
                'procurement.quotations.create',
                'procurement.quotations.edit',
                'procurement.quotations.delete',
                'procurement.comparison.view',
                'procurement.comparison.create',
                'procurement.comparison.edit',
                'inventory.returns.view',
            ]);
        }

        // Grant to stores-person
        $storesPerson = Role::where('name', 'stores-person')->where('guard_name', $guard)->first();
        if ($storesPerson) {
            $storesPerson->givePermissionTo([
                'inventory.returns.view',
                'inventory.returns.create',
            ]);
        }

        $registrar->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Safe no-op or revoke
    }
};
