<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Additive kiosk RBAC setup. It does not change other roles' existing permissions. */
class KioskAccessSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = RolePermissionSeeder::permissions();
        $kioskPermissions = array_values(array_filter($permissions, fn ($name) => str_starts_with($name, 'kiosk.')));
        foreach ($kioskPermissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }

        $manager = Role::firstOrCreate(['name' => 'kiosk-manager', 'guard_name' => $guard]);
        $cashier = Role::firstOrCreate(['name' => 'kiosk-cashier', 'guard_name' => $guard]);
        $managerNames = ['kiosk.backoffice.view', 'kiosk.cashier.access', 'kiosk.sales.view', 'kiosk.sales.create', 'kiosk.till.open', 'kiosk.till.close', 'kiosk.products.view', 'kiosk.products.manage', 'kiosk.stock.view', 'kiosk.stock.manage', 'kiosk.stock-takes.view', 'kiosk.stock-takes.create', 'kiosk.stock-takes.approve', 'kiosk.reports.view', 'kiosk.z-readings.view'];
        $cashierNames = ['kiosk.cashier.access', 'kiosk.sales.view', 'kiosk.sales.create', 'kiosk.till.open', 'kiosk.till.close', 'kiosk.z-readings.view'];
        $manager->syncPermissions($managerNames);
        $cashier->syncPermissions($cashierNames);

        $existingManagementRoles = Role::whereIn('name', ['school-admin', 'headmaster', 'bursar', 'accountant'])->get();
        foreach ($existingManagementRoles as $role) {
            $role->givePermissionTo($managerNames);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
