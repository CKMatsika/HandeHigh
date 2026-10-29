<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public static function permissions(): array
    {
        return [
            'dashboard.view', 'dashboard.headmaster', 'dashboard.deputy-headmaster',
            'dashboard.accounts-clerk', 'dashboard.bursar', 'dashboard.procurement-officer',
            'student-reconciliation.view', 'student-reconciliation.upload', 'student-reconciliation.review', 'student-reconciliation.export',
            'kiosk.backoffice.view', 'kiosk.cashier.access', 'kiosk.sales.view', 'kiosk.sales.create',
            'kiosk.till.open', 'kiosk.till.close', 'kiosk.products.view', 'kiosk.products.manage',
            'kiosk.stock.view', 'kiosk.stock.manage', 'kiosk.stock-takes.view', 'kiosk.stock-takes.create',
            'kiosk.stock-takes.approve', 'kiosk.reports.view', 'kiosk.z-readings.view',
            'students.view', 'students.create', 'students.edit', 'students.delete',
            'enrollments.view', 'enrollments.create', 'enrollments.edit', 'enrollments.delete',
            'classes.view', 'classes.create', 'classes.edit', 'classes.delete',
            'subjects.view', 'subjects.create', 'subjects.edit', 'subjects.delete',
            'teachers.view', 'teachers.create', 'teachers.edit', 'teachers.delete',
            'attendance.view', 'attendance.create', 'attendance.edit', 'attendance.delete',
            'fees.view', 'fees.create', 'fees.edit', 'fees.delete',
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete',
            'payments.view', 'payments.create', 'payments.edit', 'payments.delete',
            'receipts.view', 'receipts.create', 'receipts.edit', 'receipts.delete',
            'budgets.view', 'budgets.create', 'budgets.edit', 'budgets.delete',
            'accounts.view', 'accounts.create', 'accounts.edit', 'accounts.delete',
            'journals.view', 'journals.create', 'journals.edit', 'journals.delete',
            'bank-reconciliations.view', 'bank-reconciliations.create', 'bank-reconciliations.edit', 'bank-reconciliations.delete',
            'procurement.view', 'procurement.create', 'procurement.edit', 'procurement.delete',
            'procurement.approve', 'procurement.committee', 'procurement.vendors.view',
            'procurement.vendors.create', 'procurement.vendors.edit', 'procurement.vendors.delete',
            'procurement.reports.view',
            'procurement.rfq.view', 'procurement.rfq.create', 'procurement.rfq.edit', 'procurement.rfq.delete', 'procurement.rfq.publish',
            'procurement.quotations.view', 'procurement.quotations.create', 'procurement.quotations.edit', 'procurement.quotations.delete',
            'procurement.comparison.view', 'procurement.comparison.create', 'procurement.comparison.edit', 'procurement.comparison.approve',
            'procurement.requisitions.create', 'procurement.requisitions.edit', 'procurement.requisitions.delete',
            'procurement.purchase-orders.view', 'procurement.purchase-orders.create', 'procurement.purchase-orders.edit', 'procurement.purchase-orders.delete', 'procurement.purchase-orders.issue',
            'procurement.grv.view', 'procurement.grv.create', 'procurement.grv.edit', 'procurement.grv.delete',
            'procurement.supplier-invoices.view', 'procurement.supplier-invoices.create', 'procurement.supplier-invoices.edit', 'procurement.supplier-invoices.delete', 'procurement.supplier-invoices.approve',
            'dashboard.stores-manager',
            'inventory.view', 'inventory.create', 'inventory.edit', 'inventory.delete',
            'inventory.catalog.manage', 'inventory.categories.create', 'inventory.categories.edit', 'inventory.categories.delete',
            'inventory.items.create', 'inventory.items.edit', 'inventory.items.delete',
            'inventory.warehouses.manage', 'inventory.warehouses.create', 'inventory.warehouses.edit', 'inventory.warehouses.delete',
            'inventory.ledger.view',
            'inventory.stock-requests.view', 'inventory.stock-requests.create', 'inventory.stock-requests.approve', 'inventory.stock-requests.delete', 'inventory.stock-issues.create',
            'inventory.stock-takes.view', 'inventory.stock-takes.create', 'inventory.stock-takes.approve', 'inventory.stock-takes.delete',
            'inventory.stock-levels.manage',
            'inventory.returns.view', 'inventory.returns.create', 'inventory.returns.edit', 'inventory.returns.delete',
            'inventory.assets.view', 'inventory.assets.manage', 'inventory.assets.create', 'inventory.assets.edit', 'inventory.assets.delete',
            'inventory.settings.view', 'inventory.settings.edit',
            'communication.view', 'communication.create', 'communication.sms', 'communication.email', 'communication.chat',
            'reports.view', 'reports.financial', 'reports.academic', 'reports.procurement', 'reports.attendance',
            'schools.view', 'schools.edit', 'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.assign',
            'schemes-of-work.view', 'schemes-of-work.create', 'schemes-of-work.edit',
            'schemes-of-work.approve', 'schemes-of-work.reject',
            'timetables.view', 'timetables.create', 'timetables.edit', 'timetables.delete',
            'timetables.generate', 'timetables.publish', 'timetables.export',
        ];
    }

    public static function rolePermissions(): array
    {
        $academic = [
            'student-reconciliation.view', 'student-reconciliation.upload', 'student-reconciliation.review', 'student-reconciliation.export',
            'students.view', 'students.create', 'students.edit', 'students.delete',
            'enrollments.view', 'enrollments.create', 'enrollments.edit', 'enrollments.delete',
            'classes.view', 'classes.create', 'classes.edit', 'classes.delete',
            'subjects.view', 'subjects.create', 'subjects.edit', 'subjects.delete',
            'teachers.view', 'teachers.create', 'teachers.edit', 'teachers.delete',
            'attendance.view', 'attendance.create', 'attendance.edit', 'attendance.delete',
        ];
        $finance = [
            'fees.view', 'fees.create', 'fees.edit', 'fees.delete',
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete',
            'payments.view', 'payments.create', 'payments.edit', 'payments.delete',
            'receipts.view', 'receipts.create', 'receipts.edit', 'receipts.delete',
            'budgets.view', 'budgets.create', 'budgets.edit', 'budgets.delete',
        ];
        $accounting = [
            'accounts.view', 'accounts.create', 'accounts.edit', 'accounts.delete',
            'journals.view', 'journals.create', 'journals.edit', 'journals.delete',
            'bank-reconciliations.view', 'bank-reconciliations.create', 'bank-reconciliations.edit', 'bank-reconciliations.delete',
        ];
        $communication = ['communication.view', 'communication.create', 'communication.sms', 'communication.email', 'communication.chat'];
        $reports = ['reports.view', 'reports.financial', 'reports.academic', 'reports.procurement', 'reports.attendance'];
        $schemes = ['schemes-of-work.view', 'schemes-of-work.create', 'schemes-of-work.edit', 'schemes-of-work.approve', 'schemes-of-work.reject'];
        $timetables = ['timetables.view', 'timetables.create', 'timetables.edit', 'timetables.delete', 'timetables.generate', 'timetables.publish', 'timetables.export'];
        $kioskManager = ['kiosk.backoffice.view', 'kiosk.cashier.access', 'kiosk.sales.view', 'kiosk.sales.create', 'kiosk.till.open', 'kiosk.till.close', 'kiosk.products.view', 'kiosk.products.manage', 'kiosk.stock.view', 'kiosk.stock.manage', 'kiosk.stock-takes.view', 'kiosk.stock-takes.create', 'kiosk.stock-takes.approve', 'kiosk.reports.view', 'kiosk.z-readings.view'];
        $kioskCashier = ['kiosk.cashier.access', 'kiosk.sales.view', 'kiosk.sales.create', 'kiosk.till.open', 'kiosk.till.close', 'kiosk.z-readings.view'];

        return [
            'super-admin' => self::permissions(),
            'kiosk-manager' => $kioskManager,
            'kiosk-cashier' => $kioskCashier,
            'school-admin' => array_merge($kioskManager, ['dashboard.view', 'inventory.view', 'inventory.catalog.manage', 'inventory.warehouses.manage', 'inventory.ledger.view', 'inventory.stock-requests.view', 'inventory.stock-requests.create', 'inventory.stock-requests.approve', 'inventory.stock-issues.create', 'inventory.stock-takes.view', 'inventory.stock-takes.create', 'inventory.stock-takes.approve', 'inventory.stock-levels.manage', 'inventory.returns.view', 'inventory.returns.create', 'inventory.returns.edit', 'inventory.returns.delete', 'inventory.assets.view', 'inventory.assets.manage', 'procurement.requisitions.create', 'procurement.rfq.view', 'procurement.rfq.create', 'procurement.rfq.edit', 'procurement.rfq.delete', 'procurement.rfq.publish', 'procurement.quotations.view', 'procurement.quotations.create', 'procurement.quotations.edit', 'procurement.quotations.delete', 'procurement.comparison.view', 'procurement.comparison.create', 'procurement.comparison.edit', 'procurement.comparison.approve', 'procurement.purchase-orders.create', 'procurement.purchase-orders.issue', 'procurement.grv.view', 'procurement.grv.create', 'procurement.supplier-invoices.view', 'procurement.supplier-invoices.create', 'procurement.supplier-invoices.approve'], $academic, $finance, $accounting, [
                'procurement.view', 'procurement.create', 'procurement.edit', 'procurement.delete', 'procurement.approve',
                'procurement.vendors.view', 'procurement.vendors.create', 'procurement.vendors.edit', 'procurement.vendors.delete',
            ], $communication, $reports, ['users.view', 'users.create', 'users.edit', 'users.delete', 'roles.view', 'roles.assign'], $timetables),
            'headmaster' => array_merge($kioskManager, ['dashboard.headmaster', 'inventory.view', 'inventory.ledger.view', 'inventory.stock-requests.view', 'inventory.stock-requests.create', 'inventory.stock-requests.approve', 'inventory.stock-takes.view', 'inventory.stock-takes.approve', 'inventory.assets.view', 'procurement.requisitions.create'], $academic, $finance, [
                'procurement.view', 'procurement.approve', 'procurement.vendors.view',
            ], $communication, $reports, ['users.view', 'users.create', 'users.edit'], $schemes, $timetables),
            'deputy-headmaster' => array_merge(['dashboard.deputy-headmaster', 'inventory.stock-requests.view', 'inventory.stock-requests.create', 'inventory.stock-requests.approve', 'inventory.stock-takes.view', 'inventory.stock-takes.approve', 'inventory.assets.view', 'procurement.requisitions.create'], $academic, $finance, [
                'procurement.view', 'procurement.approve',
            ], $communication, $reports, ['users.view', 'users.create', 'users.edit'], $schemes, $timetables),
            'accounts-clerk' => array_merge(['dashboard.accounts-clerk'],
                ['procurement.requisitions.create'],
                ['procurement.grv.view', 'procurement.supplier-invoices.view', 'procurement.supplier-invoices.create', 'procurement.supplier-invoices.approve'],
                ['students.view', 'enrollments.view'],
                ['fees.view', 'fees.create', 'fees.edit', 'invoices.view', 'invoices.create', 'invoices.edit', 'payments.view', 'payments.create', 'payments.edit', 'receipts.view', 'receipts.create', 'receipts.edit'],
                ['accounts.view', 'accounts.create', 'accounts.edit', 'journals.view', 'journals.create', 'journals.edit', 'bank-reconciliations.view', 'bank-reconciliations.create', 'bank-reconciliations.edit'],
                ['communication.view', 'communication.create', 'communication.sms', 'communication.email'],
                ['reports.view', 'reports.financial', 'reports.attendance']),
            'bursar' => array_merge($kioskManager, ['dashboard.bursar', 'inventory.stock-requests.view', 'inventory.stock-requests.create', 'inventory.stock-requests.approve', 'inventory.stock-issues.create', 'inventory.stock-takes.view', 'inventory.stock-takes.approve', 'inventory.stock-levels.manage', 'inventory.assets.view'],
                ['students.view', 'procurement.requisitions.create', 'inventory.view', 'inventory.ledger.view'], $finance, ['accounts.view', 'accounts.create', 'accounts.edit', 'journals.view', 'journals.create', 'journals.edit', 'bank-reconciliations.view', 'bank-reconciliations.create', 'bank-reconciliations.edit'],
                ['procurement.view', 'procurement.approve', 'procurement.vendors.view'],
                ['procurement.supplier-invoices.view', 'procurement.supplier-invoices.approve'],
                ['communication.view', 'communication.create', 'communication.sms', 'communication.email'], ['reports.view', 'reports.financial', 'reports.procurement']),
            'procurement-officer' => array_merge(['dashboard.procurement-officer'],
                ['procurement.view', 'procurement.create', 'procurement.edit', 'procurement.delete', 'procurement.committee', 'procurement.vendors.view', 'procurement.vendors.create', 'procurement.vendors.edit', 'procurement.vendors.delete', 'procurement.reports.view', 'procurement.rfq.view', 'procurement.rfq.create', 'procurement.rfq.edit', 'procurement.rfq.delete', 'procurement.rfq.publish', 'procurement.quotations.view', 'procurement.quotations.create', 'procurement.quotations.edit', 'procurement.quotations.delete', 'procurement.comparison.view', 'procurement.comparison.create', 'procurement.comparison.edit', 'procurement.requisitions.create', 'procurement.purchase-orders.create', 'procurement.purchase-orders.issue'],
                ['procurement.grv.view', 'inventory.returns.view'],
                ['procurement.supplier-invoices.view', 'procurement.supplier-invoices.create'],
                ['inventory.view', 'inventory.catalog.manage'],
                $communication, ['reports.view', 'reports.procurement']),
            'stores-person' => [
                'inventory.view', 'inventory.catalog.manage', 'inventory.warehouses.manage', 'inventory.ledger.view',
                'inventory.stock-requests.view', 'inventory.stock-issues.create',
                'inventory.stock-takes.view', 'inventory.stock-takes.create', 'inventory.stock-levels.manage',
                'inventory.returns.view', 'inventory.returns.create',
                'inventory.assets.view', 'inventory.assets.manage',
                'procurement.requisitions.create', 'procurement.grv.view', 'procurement.grv.create',
                'procurement.supplier-invoices.view', 'procurement.supplier-invoices.create',
            ],
            'teacher' => array_merge(['dashboard.view', 'procurement.requisitions.create', 'inventory.stock-requests.view', 'inventory.stock-requests.create'], ['students.view', 'enrollments.view', 'classes.view', 'subjects.view'], ['attendance.view', 'attendance.create', 'attendance.edit'], $communication, ['reports.view', 'reports.academic', 'reports.attendance'], array_slice($schemes, 0, 3), ['timetables.view', 'timetables.export']),
            'accountant' => array_merge($kioskManager, $accounting, ['fees.view', 'invoices.view', 'payments.view', 'receipts.view', 'budgets.view', 'inventory.view', 'inventory.ledger.view', 'inventory.assets.view', 'procurement.requisitions.create', 'procurement.grv.view', 'procurement.supplier-invoices.view', 'procurement.supplier-invoices.create', 'procurement.supplier-invoices.approve'], ['communication.view', 'communication.create', 'communication.sms', 'communication.email'], ['reports.view', 'reports.financial']),
        ];
    }

    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        (new RoleSeeder)->run();

        foreach (self::permissions() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }

        foreach (self::rolePermissions() as $roleName => $permissionNames) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guard,
            ]);

            $role->syncPermissions($permissionNames);
        }

        $registrar->forgetCachedPermissions();
    }
}
