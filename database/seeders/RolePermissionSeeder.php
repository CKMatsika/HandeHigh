<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Dashboard permissions
            'dashboard.view',
            'dashboard.headmaster',
            'dashboard.deputy-headmaster',
            'dashboard.accounts-clerk',
            'dashboard.bursar',
            'dashboard.procurement-officer',
            
            // Academic permissions
            'students.view',
            'students.create',
            'students.edit',
            'students.delete',
            'enrollments.view',
            'enrollments.create',
            'enrollments.edit',
            'enrollments.delete',
            'classes.view',
            'classes.create',
            'classes.edit',
            'classes.delete',
            'subjects.view',
            'subjects.create',
            'subjects.edit',
            'subjects.delete',
            'teachers.view',
            'teachers.create',
            'teachers.edit',
            'teachers.delete',
            'attendance.view',
            'attendance.create',
            'attendance.edit',
            'attendance.delete',
            
            // Finance permissions
            'fees.view',
            'fees.create',
            'fees.edit',
            'fees.delete',
            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'invoices.delete',
            'payments.view',
            'payments.create',
            'payments.edit',
            'payments.delete',
            'receipts.view',
            'receipts.create',
            'receipts.edit',
            'receipts.delete',
            'budgets.view',
            'budgets.create',
            'budgets.edit',
            'budgets.delete',
            
            // Accounting permissions
            'accounts.view',
            'accounts.create',
            'accounts.edit',
            'accounts.delete',
            'journals.view',
            'journals.create',
            'journals.edit',
            'journals.delete',
            'bank-reconciliations.view',
            'bank-reconciliations.create',
            'bank-reconciliations.edit',
            'bank-reconciliations.delete',
            
            // Procurement permissions
            'procurement.view',
            'procurement.create',
            'procurement.edit',
            'procurement.delete',
            'procurement.approve',
            'procurement.committee',
            'procurement.vendors.view',
            'procurement.vendors.create',
            'procurement.vendors.edit',
            'procurement.vendors.delete',
            'procurement.reports.view',
            
            // Communication permissions
            'communication.view',
            'communication.create',
            'communication.sms',
            'communication.email',
            'communication.chat',
            
            // Reports permissions
            'reports.view',
            'reports.financial',
            'reports.academic',
            'reports.procurement',
            'reports.attendance',
            
            // System permissions
            'schools.view',
            'schools.edit',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.view',
            'roles.assign',

            // Schemes of Work permissions
            'schemes-of-work.view',
            'schemes-of-work.create',
            'schemes-of-work.edit',
            'schemes-of-work.approve',
            'schemes-of-work.reject',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        $superAdmin = Role::findByName('super-admin');
        $superAdmin->givePermissionTo(Permission::all());

        $schoolAdmin = Role::findByName('school-admin');
        $schoolAdmin->givePermissionTo([
            'dashboard.view',
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
            'procurement.view', 'procurement.create', 'procurement.edit', 'procurement.delete', 'procurement.approve',
            'procurement.vendors.view', 'procurement.vendors.create', 'procurement.vendors.edit', 'procurement.vendors.delete',
            'communication.view', 'communication.create', 'communication.sms', 'communication.email', 'communication.chat',
            'reports.view', 'reports.financial', 'reports.academic', 'reports.procurement', 'reports.attendance',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.assign',
        ]);

        $headmaster = Role::findByName('headmaster');
        $headmaster->givePermissionTo([
            'dashboard.headmaster',
            'students.view', 'students.create', 'students.edit',
            'enrollments.view', 'enrollments.create', 'enrollments.edit',
            'classes.view', 'classes.create', 'classes.edit',
            'subjects.view', 'subjects.create', 'subjects.edit',
            'teachers.view', 'teachers.create', 'teachers.edit',
            'attendance.view', 'attendance.create', 'attendance.edit',
            'fees.view', 'fees.create', 'fees.edit',
            'invoices.view', 'invoices.create', 'invoices.edit',
            'payments.view', 'payments.create', 'payments.edit',
            'budgets.view', 'budgets.create', 'budgets.edit',
            'procurement.view', 'procurement.approve',
            'procurement.vendors.view',
            'communication.view', 'communication.create', 'communication.sms', 'communication.email', 'communication.chat',
            'reports.view', 'reports.financial', 'reports.academic', 'reports.procurement', 'reports.attendance',
            'users.view', 'users.create', 'users.edit',
            'schemes-of-work.view', 'schemes-of-work.approve', 'schemes-of-work.reject',
        ]);

        $deputyHeadmaster = Role::findByName('deputy-headmaster');
        $deputyHeadmaster->givePermissionTo([
            'dashboard.deputy-headmaster',
            'students.view', 'students.create', 'students.edit',
            'enrollments.view', 'enrollments.create', 'enrollments.edit',
            'classes.view', 'classes.create', 'classes.edit',
            'subjects.view', 'subjects.create', 'subjects.edit',
            'teachers.view', 'teachers.create', 'teachers.edit',
            'attendance.view', 'attendance.create', 'attendance.edit',
            'fees.view', 'fees.create', 'fees.edit',
            'invoices.view', 'invoices.create', 'invoices.edit',
            'payments.view', 'payments.create', 'payments.edit',
            'budgets.view', 'budgets.create', 'budgets.edit',
            'procurement.view', 'procurement.approve',
            'communication.view', 'communication.create', 'communication.sms', 'communication.email', 'communication.chat',
            'reports.view', 'reports.financial', 'reports.academic', 'reports.procurement', 'reports.attendance',
            'users.view', 'users.create', 'users.edit',
            'schemes-of-work.view', 'schemes-of-work.approve', 'schemes-of-work.reject',
        ]);

        $accountsClerk = Role::findByName('accounts-clerk');
        $accountsClerk->givePermissionTo([
            'dashboard.accounts-clerk',
            'students.view',
            'enrollments.view',
            'fees.view', 'fees.create', 'fees.edit',
            'invoices.view', 'invoices.create', 'invoices.edit',
            'payments.view', 'payments.create', 'payments.edit',
            'receipts.view', 'receipts.create', 'receipts.edit',
            'accounts.view', 'accounts.create', 'accounts.edit',
            'journals.view', 'journals.create', 'journals.edit',
            'bank-reconciliations.view', 'bank-reconciliations.create', 'bank-reconciliations.edit',
            'communication.view', 'communication.create', 'communication.sms', 'communication.email',
            'reports.view', 'reports.financial', 'reports.attendance',
        ]);

        $bursar = Role::findByName('bursar');
        $bursar->givePermissionTo([
            'dashboard.bursar',
            'students.view',
            'fees.view', 'fees.create', 'fees.edit',
            'invoices.view', 'invoices.create', 'invoices.edit',
            'payments.view', 'payments.create', 'payments.edit',
            'receipts.view', 'receipts.create', 'receipts.edit',
            'budgets.view', 'budgets.create', 'budgets.edit',
            'accounts.view', 'accounts.create', 'accounts.edit',
            'journals.view', 'journals.create', 'journals.edit',
            'bank-reconciliations.view', 'bank-reconciliations.create', 'bank-reconciliations.edit',
            'procurement.view', 'procurement.approve',
            'procurement.vendors.view',
            'communication.view', 'communication.create', 'communication.sms', 'communication.email',
            'reports.view', 'reports.financial', 'reports.procurement',
        ]);

        $procurementOfficer = Role::findByName('procurement-officer');
        $procurementOfficer->givePermissionTo([
            'dashboard.procurement-officer',
            'procurement.view', 'procurement.create', 'procurement.edit', 'procurement.delete',
            'procurement.committee',
            'procurement.vendors.view', 'procurement.vendors.create', 'procurement.vendors.edit', 'procurement.vendors.delete',
            'procurement.reports.view',
            'communication.view', 'communication.create', 'communication.sms', 'communication.email', 'communication.chat',
            'reports.view', 'reports.procurement',
        ]);

        $teacher = Role::findByName('teacher');
        $teacher->givePermissionTo([
            'dashboard.view',
            'students.view',
            'enrollments.view',
            'classes.view',
            'subjects.view',
            'attendance.view', 'attendance.create', 'attendance.edit',
            'communication.view', 'communication.create', 'communication.sms', 'communication.email', 'communication.chat',
            'reports.view', 'reports.academic', 'reports.attendance',
            'schemes-of-work.view', 'schemes-of-work.create', 'schemes-of-work.edit',
        ]);

        $accountant = Role::findByName('accountant');
        $accountant->givePermissionTo([
            'accounts.view', 'accounts.create', 'accounts.edit',
            'journals.view', 'journals.create', 'journals.edit',
            'bank-reconciliations.view', 'bank-reconciliations.create', 'bank-reconciliations.edit',
            'fees.view',
            'invoices.view',
            'payments.view',
            'receipts.view',
            'budgets.view',
            'communication.view', 'communication.create', 'communication.sms', 'communication.email',
            'reports.view', 'reports.financial',
        ]);
    }
}
