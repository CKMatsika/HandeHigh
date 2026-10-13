<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Attendance;
use App\Models\BankReconciliation;
use App\Models\Budget;
use App\Models\CreditNote;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\JournalBatch;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Receipt;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;

class TenantRecordPolicy
{
    private const PERMISSIONS = [
        Student::class => 'students',
        Enrollment::class => 'enrollments',
        FeeStructure::class => 'fees',
        Invoice::class => 'invoices',
        CreditNote::class => 'invoices',
        Account::class => 'accounts',
        Cashbook::class => 'accounts',
        Budget::class => 'budgets',
        BankReconciliation::class => 'bank-reconciliations',
        JournalBatch::class => 'journals',
        SchoolClass::class => 'classes',
        Subject::class => 'subjects',
        Teacher::class => 'teachers',
        Attendance::class => 'attendance',
        Receipt::class => 'receipts',
        \App\Models\SchemeOfWork::class => 'schemes-of-work',
        \App\Models\Timetable::class => 'timetables',
        \App\Models\Room::class => 'timetables',
        \App\Models\TeacherAbsence::class => 'timetables',
        \App\Models\TimetableSubstitution::class => 'timetables',
        \App\Models\TimetableOperationalChange::class => 'timetables',
        \App\Models\Vendor::class => 'procurement.vendors',
    ];

    public function before(User $user, string $ability): ?bool
    {
        if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }

    public function view(User $user, Model $record): bool
    {
        return $this->allows($user, $record, 'view');
    }

    public function update(User $user, Model $record): bool
    {
        return $this->allows($user, $record, 'edit');
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->allows($user, $record, 'delete');
    }

    private function allows(User $user, Model $record, string $action): bool
    {
        $tenant = app(TenantContext::class);

        if (! $tenant->owns($record)) {
            return false;
        }

        $resource = self::PERMISSIONS[$record::class] ?? null;

        if ($resource === null) {
            return true;
        }

        return $user->can("{$resource}.{$action}");
    }
}
