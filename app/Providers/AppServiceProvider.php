<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\Attendance;
use App\Models\BankReconciliation;
use App\Models\Budget;
use App\Models\CreditNote;
use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\JournalBatch;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Bill;
use App\Models\Book;
use App\Models\BorrowRecord;
use App\Models\Cashbook;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Loan;
use App\Models\Payroll;
use App\Models\Project;
use App\Models\Receipt;
use App\Models\SchemeOfWork;
use App\Models\CareerGuidanceAssessment;
use App\Models\CareerPath;
use App\Models\FlashCardSet;
use App\Models\Timetable;
use App\Models\Vendor;
use App\Policies\SchoolPolicy;
use App\Policies\TenantRecordPolicy;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class, fn () => new TenantContext);
    }

    public function boot(): void
    {
        \App\Models\Invoice::observe(\App\Observers\InvoiceObserver::class);

        $bindings = [
            'student' => Student::class,
            'enrollment' => Enrollment::class,
            'invoice' => Invoice::class,
            'fee' => FeeStructure::class,
            'account' => Account::class,
            'journalBatch' => JournalBatch::class,
            'budget' => Budget::class,
            'creditNote' => CreditNote::class,
            'bankReconciliation' => BankReconciliation::class,
            'teacher' => Teacher::class,
            'class' => SchoolClass::class,
            'subject' => Subject::class,
            'attendance' => Attendance::class,
            'cashbook' => Cashbook::class,
            'receipt' => Receipt::class,
            'employee' => Employee::class,
            'leave' => Leave::class,
            'loan' => Loan::class,
            'payroll' => Payroll::class,
            'department' => Department::class,
            'project' => Project::class,
            'bill' => Bill::class,
            'vendor' => Vendor::class,
            'customer' => Customer::class,
            'book' => Book::class,
            'borrowRecord' => BorrowRecord::class,
            'conversation' => Conversation::class,
            'assessment' => CareerGuidanceAssessment::class,
            'path' => CareerPath::class,
            'schemeOfWork' => SchemeOfWork::class,
            'flashCardSet' => FlashCardSet::class,
            'timetable' => Timetable::class,
            'room' => \App\Models\Room::class,
            'absence' => \App\Models\TeacherAbsence::class,
            'substitution' => \App\Models\TimetableSubstitution::class,
            'change' => \App\Models\TimetableOperationalChange::class,
        ];

        foreach ($bindings as $parameter => $model) {
            Route::bind($parameter, fn ($value) => app(TenantContext::class)->resolve($model, $value));
        }

        Route::bind('slot', function ($value) {
            $context = app(TenantContext::class);
            $user = request()->user()
                ?? (Auth::guard('sanctum')->check() ? Auth::guard('sanctum')->user() : null)
                ?? Auth::user();
            $school = $context->school() ?? $user?->school ?? ($user?->school_id ? School::find($user->school_id) : null);
            if (! $school) {
                abort(404);
            }
            return \App\Models\TimetableSlot::whereHas('timetable', fn ($q) => $q->where('school_id', $school->id))->findOrFail($value);
        });

        Gate::policy(School::class, SchoolPolicy::class);
        foreach ($bindings as $model) {
            Gate::policy($model, TenantRecordPolicy::class);
        }

        // Register Timetable Event Listeners
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\Timetable\TeacherChanged::class,
            \App\Listeners\Timetable\SendTimetableChangeNotifications::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\Timetable\RoomChanged::class,
            \App\Listeners\Timetable\SendTimetableChangeNotifications::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\Timetable\LessonMoved::class,
            \App\Listeners\Timetable\SendTimetableChangeNotifications::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\Timetable\LessonCancelled::class,
            \App\Listeners\Timetable\SendTimetableChangeNotifications::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\Timetable\LessonRestored::class,
            \App\Listeners\Timetable\SendTimetableChangeNotifications::class
        );
    }
}
