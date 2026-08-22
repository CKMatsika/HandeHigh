<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['parent', 'super-admin'])) {
            abort(403);
        }

        $guardian = null;
        $students = collect();
        $invoices = collect();
        $results = collect();
        $attendance = collect();
        $announcements = collect();
        $communications = collect();
        
        if ($user->hasRole('parent')) {
            $guardian = $school->guardians()->where('user_id', $user->id)->first();
            if (! $guardian) {
                abort(403);
            }
            $students = $guardian->students()->with(['enrollments.class', 'invoices', 'results'])->get();
            
            // Get all invoices for guardian's children
            $studentIds = $students->pluck('id');
            $invoices = Invoice::whereIn('student_id', $studentIds)
                ->orderBy('issued_at', 'desc')
                ->limit(10)
                ->get();
            
            // Get all results for guardian's children
            $results = Result::whereIn('student_id', $studentIds)
                ->with(['student', 'subject'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            
            // Sample attendance data for children
            $attendance = collect();
            foreach ($students as $student) {
                $attendance->push((object)[
                    'student' => $student->first_name . ' ' . $student->last_name,
                    'date' => now()->subDays(1),
                    'status' => 'present',
                    'rate' => 95
                ]);
                $attendance->push((object)[
                    'student' => $student->first_name . ' ' . $student->last_name,
                    'date' => now()->subDays(2),
                    'status' => 'present',
                    'rate' => 90
                ]);
            }
            
            // Sample announcements
            $announcements = collect([
                (object)['title' => 'Parent-Teacher Meeting', 'message' => 'Scheduled for next Friday at 2 PM', 'date' => now()->subDays(1), 'priority' => 'high'],
                (object)['title' => 'School Holiday', 'message' => 'School will be closed next Monday for public holiday', 'date' => now()->subDays(3), 'priority' => 'medium'],
                (object)['title' => 'Exam Schedule', 'message' => 'Final exams will begin in two weeks', 'date' => now()->subDays(5), 'priority' => 'medium'],
            ]);
            
            // Sample communications
            $communications = collect([
                (object)['type' => 'email', 'subject' => 'Student Progress Report', 'from' => 'Mr. Smith', 'date' => now()->subHours(2), 'status' => 'read'],
                (object)['type' => 'sms', 'subject' => 'Attendance Alert', 'from' => 'School System', 'date' => now()->subHours(6), 'status' => 'unread'],
                (object)['type' => 'email', 'subject' => 'Fee Reminder', 'from' => 'Accounts Office', 'date' => now()->subDays(1), 'status' => 'read'],
            ]);
        } else {
            // For super admin, show sample data
            $students = $school->students()->limit(3)->with(['enrollments.class'])->get();
            $invoices = $school->invoices()->orderBy('issued_at', 'desc')->limit(5)->get();
            $results = collect();
            
            $attendance = collect([
                (object)['student' => 'Sample Student', 'date' => now()->subDays(1), 'status' => 'present', 'rate' => 85],
            ]);
            
            $announcements = collect([
                (object)['title' => 'Sample Announcement', 'message' => 'This is a sample announcement for testing', 'date' => now(), 'priority' => 'medium'],
            ]);
            
            $communications = collect([
                (object)['type' => 'email', 'subject' => 'Sample Communication', 'from' => 'Sample Sender', 'date' => now(), 'status' => 'unread'],
            ]);
        }

        return view('portal.parent.dashboard', [
            'school' => $school,
            'user' => $user,
            'guardian' => $guardian,
            'students' => $students,
            'invoices' => $invoices,
            'results' => $results,
            'attendance' => $attendance,
            'announcements' => $announcements,
            'communications' => $communications,
        ]);
    }

    public function profile()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['parent', 'super-admin'])) {
            abort(403);
        }

        $guardian = null;
        if ($user->hasRole('parent')) {
            $guardian = $school->guardians()->where('user_id', $user->id)->first();
            if (! $guardian) {
                abort(403);
            }
        } else {
            // For super admin, show sample data
            $guardian = null;
        }

        return view('portal.parent.profile', [
            'school' => $school,
            'user' => $user,
            'guardian' => $guardian,
        ]);
    }

    public function studentFees(Student $student)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['parent', 'super-admin'])) {
            abort(403);
        }

        $guardian = null;
        if ($user->hasRole('parent')) {
            $guardian = $school->guardians()->where('user_id', $user->id)->first();
            if (! $guardian || !$guardian->students()->where('student_id', $student->id)->exists()) {
                abort(403);
            }
        } else {
            // For super admin, allow access to any student
            $guardian = null;
        }

        $invoices = $student->invoices()->with('items')->orderBy('issued_at', 'desc')->paginate(10);

        return view('portal.parent.student-fees', [
            'school' => $school,
            'user' => $user,
            'guardian' => $guardian,
            'student' => $student,
            'invoices' => $invoices,
        ]);
    }

    public function studentResults(Student $student)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['parent', 'super-admin'])) {
            abort(403);
        }

        $guardian = null;
        if ($user->hasRole('parent')) {
            $guardian = $school->guardians()->where('user_id', $user->id)->first();
            if (! $guardian || !$guardian->students()->where('student_id', $student->id)->exists()) {
                abort(403);
            }
        } else {
            // For super admin, allow access to any student
            $guardian = null;
        }

        $results = $student->results()->with('subject', 'class')->orderBy('created_at', 'desc')->paginate(10);

        return view('portal.parent.student-results', [
            'school' => $school,
            'user' => $user,
            'guardian' => $guardian,
            'student' => $student,
            'results' => $results,
        ]);
    }

    public function studentStatement(Request $request, Student $student)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || ! $user->hasRole(['parent', 'super-admin'])) {
            abort(403);
        }

        $guardian = null;
        if ($user->hasRole('parent')) {
            $guardian = $school->guardians()->where('user_id', $user->id)->first();
            if (! $guardian || !$guardian->students()->where('student_id', $student->id)->exists()) {
                abort(403);
            }
        } else {
            // For super admin, allow access to any student
            $guardian = null;
        }

        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'term' => ['nullable', 'string', 'max:20'],
        ]);

        $academicYear = $validated['academic_year'];
        $term = $validated['term'] ?? null;

        // Get available academic years for this student
        $years = Invoice::where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->distinct()
            ->pluck('academic_year')
            ->filter()
            ->sortDesc()
            ->values();

        $terms = ['Term 1', 'Term 2', 'Term 3'];

        // Get invoices and payments for the selected period
        $invoicesQuery = Invoice::with(['items', 'allocations.payment'])
            ->where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->where('academic_year', $academicYear);

        if ($term) {
            $invoicesQuery->where('term', $term);
        }

        $invoices = $invoicesQuery->orderBy('issued_at')->get();

        $payments = Payment::with(['allocations.invoice'])
            ->where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->whereHas('allocations.invoice', function ($q) use ($academicYear, $term) {
                $q->where('academic_year', $academicYear);
                if ($term) {
                    $q->where('term', $term);
                }
            })
            ->orderBy('paid_at')
            ->get();

        $events = collect();

        foreach ($invoices as $invoice) {
            $events->push([
                'type' => 'invoice',
                'date' => $invoice->issued_at,
                'reference' => $invoice->number,
                'description' => 'Invoice ' . $invoice->number,
                'debit' => $invoice->total_amount,
                'credit' => 0,
                'balance' => 0,
                'invoice' => $invoice,
            ]);
        }

        foreach ($payments as $payment) {
            foreach ($payment->allocations as $alloc) {
                $events->push([
                    'type' => 'payment',
                    'date' => $payment->paid_at,
                    'reference' => $payment->reference ?? $payment->method,
                    'description' => 'Payment (' . ucfirst(str_replace('_', ' ', $payment->method)) . ')',
                    'debit' => 0,
                    'credit' => $alloc->amount,
                    'balance' => 0,
                    'payment' => $payment,
                ]);
            }
        }

        $events = $events->sortBy('date');

        // Calculate opening balance
        $openingBalance = 0;
        if ($term) {
            // For specific term, get all transactions from previous terms in the same year
            $previousInvoices = Invoice::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->where('academic_year', $academicYear)
                ->where('term', '<', $term)
                ->sum('total_amount');

            $previousPayments = Payment::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->whereHas('allocations.invoice', function ($q) use ($academicYear, $term) {
                    $q->where('academic_year', $academicYear)
                      ->where('term', '<', $term);
                })
                ->with('allocations')
                ->get()
                ->sum(function($payment) {
                    return $payment->allocations->sum('amount');
                });

            $openingBalance = $previousInvoices - $previousPayments;
        } else {
            // For full year, get all transactions from previous years
            $previousInvoices = Invoice::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->where('academic_year', '<', $academicYear)
                ->sum('total_amount');

            $previousPayments = Payment::where('school_id', $school->id)
                ->where('student_id', $student->id)
                ->whereHas('allocations.invoice', function ($q) use ($academicYear) {
                    $q->where('academic_year', '<', $academicYear);
                })
                ->with('allocations')
                ->get()
                ->sum(function($payment) {
                    return $payment->allocations->sum('amount');
                });

            $openingBalance = $previousInvoices - $previousPayments;
        }

        // Calculate running balance
        $runningBalance = $openingBalance;
        $events = $events->map(function ($event) use (&$runningBalance, $openingBalance) {
            $runningBalance += $event['debit'] - $event['credit'];
            $event['balance'] = $runningBalance;
            return $event;
        });

        $closingBalance = $runningBalance;

        return view('portal.parent.student-statement', [
            'school' => $school,
            'user' => $user,
            'guardian' => $guardian,
            'student' => $student,
            'academicYear' => $academicYear,
            'term' => $term,
            'years' => $years,
            'terms' => $terms,
            'events' => $events,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
        ]);
    }
}
