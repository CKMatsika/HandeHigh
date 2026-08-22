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
            'communication.view', 'communication.create', 'communication.sms', 'communication.email', 'communication.chat',
            'reports.view', 'reports.financial', 'reports.academic', 'reports.procurement', 'reports.attendance',
            'schools.view', 'schools.edit', 'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.assign',
            'schemes-of-work.view', 'schemes-of-work.create', 'schemes-of-work.edit',
            'schemes-of-work.approve', 'schemes-of-work.reject',
        ];
    }

    public static function rolePermissions(): array
    {
        $academic = [
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

        return [
            'super-admin' => self::permissions(),
            'school-admin' => array_merge(['dashboard.view'], $academic, $finance, $accounting, [
                'procurement.view', 'procurement.create', 'procurement.edit', 'procurement.delete', 'procurement.approve',
                'procurement.vendors.view', 'procurement.vendors.create', 'procurement.vendors.edit', 'procurement.vendors.delete',
            ], $communication, $reports, ['users.view', 'users.create', 'users.edit', 'users.delete', 'roles.view', 'roles.assign']),
            'headmaster' => array_merge(['dashboard.headmaster'], $academic, $finance, [
                'procurement.view', 'procurement.approve', 'procurement.vendors.view',
            ], $communication, $reports, ['users.view', 'users.create', 'users.edit'], $schemes),
            'deputy-headmaster' => array_merge(['dashboard.deputy-headmaster'], $academic, $finance, [
                'procurement.view', 'procurement.approve',
            ], $communication, $reports, ['users.view', 'users.create', 'users.edit'], $schemes),
            'accounts-clerk' => array_merge(['dashboard.accounts-clerk'],
                ['students.view', 'enrollments.view'],
                ['fees.view', 'fees.create', 'fees.edit', 'invoices.view', 'invoices.create', 'invoices.edit', 'payments.view', 'payments.create', 'payments.edit', 'receipts.view', 'receipts.create', 'receipts.edit'],
                ['accounts.view', 'accounts.create', 'accounts.edit', 'journals.view', 'journals.create', 'journals.edit', 'bank-reconciliations.view', 'bank-reconciliations.create', 'bank-reconciliations.edit'],
                ['communication.view', 'communication.create', 'communication.sms', 'communication.email'],
                ['reports.view', 'reports.financial', 'reports.attendance']),
            'bursar' => array_merge(['dashboard.bursar'],
                ['students.view'], $finance, ['accounts.view', 'accounts.create', 'accounts.edit', 'journals.view', 'journals.create', 'journals.edit', 'bank-reconciliations.view', 'bank-reconciliations.create', 'bank-reconciliations.edit'],
                ['procurement.view', 'procurement.approve', 'procurement.vendors.view'],
                ['communication.view', 'communication.create', 'communication.sms', 'communication.email'], ['reports.view', 'reports.financial', 'reports.procurement']),
            'procurement-officer' => array_merge(['dashboard.procurement-officer'],
                ['procurement.view', 'procurement.create', 'procurement.edit', 'procurement.delete', 'procurement.committee', 'procurement.vendors.view', 'procurement.vendors.create', 'procurement.vendors.edit', 'procurement.vendors.delete', 'procurement.reports.view'],
                $communication, ['reports.view', 'reports.procurement']),
            'teacher' => array_merge(['dashboard.view'], ['students.view', 'enrollments.view', 'classes.view', 'subjects.view'], ['attendance.view', 'attendance.create', 'attendance.edit'], $communication, ['reports.view', 'reports.academic', 'reports.attendance'], array_slice($schemes, 0, 3)),
            'accountant' => array_merge($accounting, ['fees.view', 'invoices.view', 'payments.view', 'receipts.view', 'budgets.view'], ['communication.view', 'communication.create', 'communication.sms', 'communication.email'], ['reports.view', 'reports.financial']),
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
