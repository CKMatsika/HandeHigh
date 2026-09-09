<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\Subject;
use App\Rules\TenantExists;
use App\Models\SchoolClass;
use App\Models\SchoolHouse;
use App\Models\Dormitory;
use App\Models\Bed;
use App\Models\BedAssignment;
use App\Models\SchoolAsset;
use App\Models\StudentAsset;
use App\Models\StudentTransfer;
use App\Models\StudentLibraryAccess;
use App\Models\StudentPosition;
use App\Models\StudentClub;
use App\Models\StudentSport;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\CreditNote;
use App\Models\LedgerEntry;
use App\Models\JournalEntry;
use App\Services\Finance\FinanceReportingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school) abort(403);

        $query = Student::where('school_id', $school->id)
            ->with(['house', 'currentBedAssignment.bed.dormitory']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('admission_number', 'like', "%{$search}%")
                  ->orWhere('registration_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('grade')) {
            $query->where('grade', $request->grade);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'active');
        }

        if ($request->filled('boarding')) {
            $query->where('is_boarding', $request->boarding === 'yes');
        }

        $students = $query->latest()->paginate(20);

        return view('admin.students.index', compact('school', 'students'));
    }

    public function show(Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        $student->load([
            'house', 'guardians', 'enrollments',
            'currentPositions', 'currentClubs', 'currentSports',
            'allocatedAssets.schoolAsset', 'libraryAccess',
            'currentBedAssignment.bed.dormitory',
        ]);

        $activeTab = request('tab', 'overview');
        $currentYear = date('Y');
        $currentTerm = '1';
        $allSubjects = Subject::where('school_id', $school->id)->get();
        $dormitories = Dormitory::where('school_id', $school->id)->where('is_active', true)->get();
        $schoolAssets = \App\Models\SchoolAsset::where('school_id', $school->id)->where('is_active', true)->where('available_quantity', '>', 0)->get();

        // Prepare Finance Data
        $invoices = Invoice::where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->with(['items.feeStructure', 'allocations.payment', 'ledgerEntries'])
            ->orderByDesc('issued_at')
            ->get();

        $payments = Payment::where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->with(['allocations.invoice'])
            ->orderByDesc('paid_at')
            ->get();

        $creditNotes = CreditNote::where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->whereIn('status', ['applied', 'issued'])
            ->orderByDesc('credit_note_date')
            ->get();

        $totalInvoiced = (float) $invoices->whereNotIn('status', ['cancelled'])->sum('total_amount');
        $totalPaid = (float) $payments->where('status', 'completed')->sum('amount');
        $totalCredits = (float) $creditNotes->sum('applied_amount');
        $outstandingBalance = (float) ($totalInvoiced - $totalPaid - $totalCredits);

        $unpaidInvoicesCount = $invoices->whereIn('status', ['unpaid', 'partial', 'overdue'])->count();

        $financeSummary = [
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
            'total_credits' => $totalCredits,
            'outstanding_balance' => $outstandingBalance,
            'invoices_count' => $invoices->count(),
            'payments_count' => $payments->count(),
            'unpaid_count' => $unpaidInvoicesCount,
        ];

        // Statement Data
        $reportingService = app(FinanceReportingService::class);
        $statementFilter = [
            'academic_year' => request('statement_year', request('academic_year')),
            'term' => request('statement_term', request('term')),
            'start_date' => request('start_date'),
            'end_date' => request('end_date'),
        ];
        $statementData = $reportingService->getStudentStatement($school, $student, array_filter($statementFilter));

        $statementYears = $invoices->pluck('academic_year')->filter()->unique()->sortDesc()->values();
        if ($statementYears->isEmpty()) {
            $statementYears = collect([date('Y'), (string)(date('Y') - 1)]);
        }

        // Student General Ledger Entries
        $invoiceNumbers = $invoices->pluck('number')->toArray();
        $paymentRefs = $payments->pluck('reference')->filter()->toArray();

        $glEntries = JournalEntry::with(['account', 'batch'])
            ->whereHas('batch', function ($q) use ($school, $invoiceNumbers, $paymentRefs, $student) {
                $q->where('school_id', $school->id)
                  ->where(function ($bq) use ($invoiceNumbers, $paymentRefs, $student) {
                      $hasCondition = false;
                      if (!empty($invoiceNumbers)) {
                          $bq->whereIn('reference_number', $invoiceNumbers);
                          $hasCondition = true;
                      }
                      if (!empty($paymentRefs)) {
                          if ($hasCondition) {
                              $bq->orWhereIn('reference_number', $paymentRefs);
                          } else {
                              $bq->whereIn('reference_number', $paymentRefs);
                              $hasCondition = true;
                          }
                      }
                      if ($hasCondition) {
                          $bq->orWhere('description', 'like', "%{$student->first_name}%{$student->last_name}%")
                             ->orWhere('description', 'like', "%{$student->admission_number}%");
                      } else {
                          $bq->where('description', 'like', "%{$student->first_name}%{$student->last_name}%")
                             ->orWhere('description', 'like', "%{$student->admission_number}%");
                      }
                  });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $studentLedgerEntries = LedgerEntry::where('school_id', $school->id)
            ->where('student_id', $student->id)
            ->with('invoice')
            ->orderBy('entry_date', 'desc')
            ->get();

        return view('admin.students.show', compact(
            'school', 'student', 'activeTab', 'currentYear', 'currentTerm', 
            'allSubjects', 'dormitories', 'schoolAssets',
            'financeSummary', 'invoices', 'payments', 'creditNotes', 
            'statementData', 'statementYears', 'glEntries', 'studentLedgerEntries'
        ));
    }

    public function create()
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school) abort(403);

        $guardians = Guardian::where('school_id', $school->id)->get();
        $classes = SchoolClass::where('school_id', $school->id)->where('academic_year', date('Y'))->get();
        $houses = SchoolHouse::where('school_id', $school->id)->where('is_active', true)->get();

        return view('admin.students.create', compact('school', 'guardians', 'classes', 'houses'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school) abort(403);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'other_names' => 'nullable|string|max:255',
            'gender' => 'nullable|string|in:male,female',
            'date_of_birth' => 'nullable|date',
            'admission_number' => 'nullable|string|unique:students,admission_number,NULL,id,school_id,' . $school->id,
            'registration_number' => 'nullable|string|unique:students,registration_number,NULL,id,school_id,' . $school->id,
            'grade' => 'nullable|string',
            'class_name' => 'nullable|string',
            'house_id' => ['nullable', TenantExists::make('school_houses')],
            'is_boarding' => 'boolean',
            'has_transport' => 'boolean',
            'guardian_ids' => 'nullable|array',
            'guardian_ids.*' => [TenantExists::make('guardians')],
        ]);

        $guardianIds = $validated['guardian_ids'] ?? [];
        unset($validated['guardian_ids']);

        $validated['school_id'] = $school->id;
        $validated['status'] = 'active';
        $validated['is_boarding'] = $request->boolean('is_boarding');
        $validated['has_transport'] = $request->boolean('has_transport');

        $student = DB::transaction(function () use ($validated, $guardianIds) {
            $student = Student::create($validated);

            if (!empty($guardianIds)) {
                $student->guardians()->attach($guardianIds);
            }
            return $student;
        });

        return redirect()->route('admin.students.show', $student)
            ->with('success', 'Student profile created successfully.');
    }

    public function edit(Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        $guardians = Guardian::where('school_id', $school->id)->get();
        $classes = SchoolClass::where('school_id', $school->id)->where('academic_year', date('Y'))->get();
        $houses = SchoolHouse::where('school_id', $school->id)->where('is_active', true)->get();

        return view('admin.students.edit', compact('school', 'student', 'guardians', 'classes', 'houses'));
    }

    public function update(Request $request, Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'other_names' => 'nullable|string|max:255',
            'gender' => 'nullable|string|in:male,female',
            'date_of_birth' => 'nullable|date',
            'admission_number' => 'nullable|string|unique:students,admission_number,' . $student->id . ',id,school_id,' . $school->id,
            'registration_number' => 'nullable|string|unique:students,registration_number,' . $student->id . ',id,school_id,' . $school->id,
            'grade' => 'nullable|string',
            'class_name' => 'nullable|string',
            'house_id' => ['nullable', TenantExists::make('school_houses')],
            'is_boarding' => 'boolean',
            'has_transport' => 'boolean',
            'status' => 'nullable|string|in:active,inactive,graduated,transferred,expelled',
            'guardian_ids' => 'nullable|array',
            'guardian_ids.*' => [TenantExists::make('guardians')],
        ]);

        $guardianIds = $validated['guardian_ids'] ?? [];
        unset($validated['guardian_ids']);

        $validated['is_boarding'] = $request->boolean('is_boarding');
        $validated['has_transport'] = $request->boolean('has_transport');

        DB::transaction(function () use ($student, $validated, $guardianIds) {
            $student->update($validated);
            $student->guardians()->sync($guardianIds);
        });

        return redirect()->route('admin.students.show', $student)
            ->with('success', 'Student profile updated successfully.');
    }

    public function destroy(Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        $student->update(['status' => 'inactive']);

        return redirect()->route('admin.students.index')
            ->with('success', 'Student deactivated successfully.');
    }

    // --- SUBJECT MANAGEMENT ---

    public function manageSubjects(Student $student)
    {
        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'subjects']);
    }

    public function addSubject(Request $request, Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'subject_id' => ['required', TenantExists::make('subjects')],
            'academic_year' => 'required|string',
            'term' => 'nullable|string',
        ]);

        $student->subjects()->syncWithoutDetaching([
            $validated['subject_id'] => [
                'academic_year' => $validated['academic_year'],
                'term' => $validated['term'] ?? null,
                'is_active' => true,
            ],
        ]);

        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'subjects'])
            ->with('success', 'Subject added successfully.');
    }

    public function removeSubject(Student $student, Subject $subject)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id || $subject->school_id !== $school->id) abort(403);

        $student->subjects()->detach($subject->id);

        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'subjects'])
            ->with('success', 'Subject removed successfully.');
    }

    // --- CLASS MOVEMENTS ---

    public function moveClass(Request $request, Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'grade' => 'required|string',
            'class_name' => 'required|string',
            'reason' => 'nullable|string',
        ]);

        $oldGrade = $student->grade;
        $oldClass = $student->class_name;

        $student->update([
            'grade' => $validated['grade'],
            'class_name' => $validated['class_name'],
        ]);

        return redirect()->route('admin.students.show', $student)
            ->with('success', "Student moved from {$oldGrade} {$oldClass} to {$validated['grade']} {$validated['class_name']}.");
    }

    public function promote(Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        $gradeOrder = [
            'Grade 1' => 1, 'Grade 2' => 2, 'Grade 3' => 3, 'Grade 4' => 4,
            'Grade 5' => 5, 'Grade 6' => 6, 'Grade 7' => 7,
            'Form 1' => 8, 'Form 2' => 9, 'Form 3' => 10, 'Form 4' => 11,
            'Form 5' => 12, 'Form 6' => 13,
        ];
        $grades = array_keys($gradeOrder);
        $currentIndex = array_search($student->grade, $grades);

        if ($currentIndex !== false && $currentIndex < count($grades) - 1) {
            $newGrade = $grades[$currentIndex + 1];
            $student->update(['grade' => $newGrade]);
            return redirect()->route('admin.students.show', $student)
                ->with('success', "Student promoted to {$newGrade}.");
        }

        return redirect()->route('admin.students.show', $student)
            ->with('error', 'Student is already at the highest grade.');
    }

    public function demote(Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        $gradeOrder = [
            'Grade 1' => 1, 'Grade 2' => 2, 'Grade 3' => 3, 'Grade 4' => 4,
            'Grade 5' => 5, 'Grade 6' => 6, 'Grade 7' => 7,
            'Form 1' => 8, 'Form 2' => 9, 'Form 3' => 10, 'Form 4' => 11,
            'Form 5' => 12, 'Form 6' => 13,
        ];
        $grades = array_keys($gradeOrder);
        $currentIndex = array_search($student->grade, $grades);

        if ($currentIndex !== false && $currentIndex > 0) {
            $newGrade = $grades[$currentIndex - 1];
            $student->update(['grade' => $newGrade]);
            return redirect()->route('admin.students.show', $student)
                ->with('success', "Student demoted to {$newGrade}.");
        }

        return redirect()->route('admin.students.show', $student)
            ->with('error', 'Student is already at the lowest grade.');
    }

    // --- TRANSFER ---

    public function transferForm(Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        return view('admin.students.transfer', compact('school', 'student'));
    }

    public function transfer(Request $request, Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'destination_school' => 'required|string|max:255',
            'destination_address' => 'nullable|string',
            'destination_contact' => 'nullable|string',
            'transfer_date' => 'required|date',
            'reason' => 'nullable|string',
            'academic_year' => 'required|string',
            'term' => 'required|string',
            'conduct_remarks' => 'nullable|string',
            'academic_remarks' => 'nullable|string',
        ]);

        $validated['school_id'] = $school->id;
        $validated['student_id'] = $student->id;
        $validated['transfer_type'] = 'transfer_out';
        $validated['grade_at_transfer'] = $student->grade;
        $validated['class_at_transfer'] = $student->class_name;
        $validated['status'] = 'completed';

        StudentTransfer::create($validated);

        $student->update([
            'status' => 'transferred',
            'exit_type' => 'transferred',
            'exit_date' => $validated['transfer_date'],
            'exit_remarks' => "Transferred to {$validated['destination_school']}.",
        ]);

        return redirect()->route('admin.students.show', $student)
            ->with('success', 'Student transfer completed successfully.');
    }

    // --- EXIT / GRADUATION ---

    public function exitForm(Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        return view('admin.students.exit', compact('school', 'student'));
    }

    public function exitStudent(Request $request, Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'exit_type' => 'required|string|in:graduated,withdrawn,expelled',
            'exit_date' => 'required|date',
            'exit_remarks' => 'nullable|string',
        ]);

        $statusMap = [
            'graduated' => 'graduated',
            'withdrawn' => 'inactive',
            'expelled' => 'inactive',
        ];

        $student->update([
            'exit_type' => $validated['exit_type'],
            'exit_date' => $validated['exit_date'],
            'exit_remarks' => $validated['exit_remarks'] ?? null,
            'status' => $statusMap[$validated['exit_type']] ?? 'inactive',
        ]);

        $label = ucfirst($validated['exit_type']);
        return redirect()->route('admin.students.show', $student)
            ->with('success', "Student has been marked as {$label}.");
    }

    // --- HOUSE ASSIGNMENT ---

    public function assignHouse(Request $request, Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'house_id' => ['required', TenantExists::make('school_houses')],
        ]);

        $student->update(['house_id' => $validated['house_id']]);

        return redirect()->route('admin.students.show', $student)
            ->with('success', 'House assigned successfully.');
    }

    // --- BOARDING MANAGEMENT ---

    public function manageBoarding(Student $student)
    {
        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'boarding']);
    }

    public function assignBed(Request $request, Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'bed_id' => [
                'required',
                function ($attribute, $value, $fail) use ($school) {
                    $exists = Bed::where('id', $value)
                        ->whereHas('dormitory', fn ($q) => $q->where('school_id', $school->id))
                        ->exists();
                    if (! $exists) {
                        $fail('The selected bed is invalid.');
                    }
                },
            ],
            'academic_year' => 'required|string',
            'term' => 'required|string',
            'notes' => 'nullable|string|max:255',
        ]);

        $bed = Bed::whereHas('dormitory', fn ($q) => $q->where('school_id', $school->id))->findOrFail($validated['bed_id']);

        try {
            $allocationService = app(\App\Services\Residency\BedAllocationService::class);
            $allocationService->allocateBed(
                $student,
                $bed,
                $validated['academic_year'],
                $validated['term'],
                $validated['notes'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'boarding'])
                ->withErrors(['bed_id' => $e->getMessage()]);
        }

        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'boarding'])
            ->with('success', 'Bed assigned successfully.');
    }

    public function releaseBed(Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        $allocationService = app(\App\Services\Residency\BedAllocationService::class);
        $allocationService->releaseBed($student);

        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'boarding'])
            ->with('success', 'Bed released successfully.');
    }

    public function transitionResidency(Request $request, Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'target_residency' => 'required|in:boarding,day',
            'academic_year' => 'required|string',
            'term' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        $residencyService = app(\App\Services\Residency\StudentResidencyService::class);
        $residencyService->transitionResidency(
            $student,
            $validated['target_residency'],
            $validated['academic_year'],
            $validated['term'],
            $validated['reason'] ?? null
        );

        $label = $validated['target_residency'] === 'boarding' ? 'Boarder' : 'Day Scholar';
        return redirect()->route('admin.students.show', $student)
            ->with('success', "Student residency successfully updated to {$label} for {$validated['academic_year']} {$validated['term']}.");
    }

    public function getBeds(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) abort(403);

        $dormitoryId = $request->dormitory_id;
        $dormitory = Dormitory::where('school_id', $school->id)->findOrFail($dormitoryId);
        
        $allocationService = app(\App\Services\Residency\BedAllocationService::class);
        $beds = $allocationService->getAvailableBeds($dormitory);

        return response()->json($beds);
    }

    // --- CLUB MANAGEMENT ---

    public function manageClubs(Student $student)
    {
        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'clubs']);
    }

    public function addClub(Request $request, Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'club_name' => 'required|string|max:255',
            'club_type' => 'required|string|in:academic,sports,arts,community,technology,cultural',
            'role' => 'nullable|string',
            'description' => 'nullable|string',
            'joined_date' => 'required|date',
        ]);

        $validated['student_id'] = $student->id;
        $validated['is_active'] = true;

        StudentClub::create($validated);

        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'clubs'])
            ->with('success', 'Club added successfully.');
    }

    public function removeClub(Student $student, StudentClub $club)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id || $club->student_id !== $student->id) abort(403);

        $club->update(['is_active' => false, 'left_date' => now()]);

        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'clubs'])
            ->with('success', 'Club removed successfully.');
    }

    // --- SPORT MANAGEMENT ---

    public function manageSports(Student $student)
    {
        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'sports']);
    }

    public function addSport(Request $request, Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'sport_name' => 'required|string|max:255',
            'sport_category' => 'required|string|in:team_sport,individual_sport,athletics,water_sport,winter_sport',
            'position' => 'nullable|string',
            'team_level' => 'nullable|string',
            'achievements' => 'nullable|string',
            'started_date' => 'required|date',
        ]);

        $validated['student_id'] = $student->id;
        $validated['is_active'] = true;

        StudentSport::create($validated);

        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'sports'])
            ->with('success', 'Sport added successfully.');
    }

    public function removeSport(Student $student, StudentSport $sport)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id || $sport->student_id !== $student->id) abort(403);

        $sport->update(['is_active' => false, 'ended_date' => now()]);

        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'sports'])
            ->with('success', 'Sport removed successfully.');
    }

    // --- LEADERSHIP / PREFECT MANAGEMENT ---

    public function managePositions(Student $student)
    {
        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'positions']);
    }

    public function addPosition(Request $request, Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'position_type' => 'required|string|in:prefect,head_boy,head_girl,class_rep,house_captain,sports_captain,club_president,other',
            'position_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $validated['student_id'] = $student->id;
        $validated['is_active'] = true;

        StudentPosition::create($validated);

        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'positions'])
            ->with('success', 'Position added successfully.');
    }

    public function removePosition(Student $student, StudentPosition $position)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id || $position->student_id !== $student->id) abort(403);

        $position->update(['is_active' => false, 'end_date' => now()]);

        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'positions'])
            ->with('success', 'Position removed successfully.');
    }

    // --- ASSET MANAGEMENT ---

    public function manageAssets(Student $student)
    {
        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'assets']);
    }

    public function allocateAsset(Request $request, Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'school_asset_id' => ['required', TenantExists::make('school_assets')],
            'quantity' => 'required|integer|min:1',
            'allocated_date' => 'required|date',
            'condition_at_issue' => 'nullable|string|in:new,good,fair,poor',
            'notes' => 'nullable|string',
        ]);

        $asset = SchoolAsset::find($validated['school_asset_id']);
        if ($asset->available_quantity < $validated['quantity']) {
            return back()->with('error', 'Insufficient asset availability.');
        }

        DB::transaction(function () use ($student, $validated, $asset) {
            StudentAsset::create([
                'student_id' => $student->id,
                'school_asset_id' => $validated['school_asset_id'],
                'quantity' => $validated['quantity'],
                'allocated_date' => $validated['allocated_date'],
                'condition_at_issue' => $validated['condition_at_issue'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'allocated',
            ]);

            $asset->decrement('available_quantity', $validated['quantity']);
        });

        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'assets'])
            ->with('success', 'Asset allocated successfully.');
    }

    public function returnAsset(Student $student, StudentAsset $asset)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id || $asset->student_id !== $student->id) abort(403);
        if ($asset->status === 'returned') {
            return back()->with('error', 'Asset has already been returned.');
        }

        DB::transaction(function () use ($asset) {
            $asset->update([
                'status' => 'returned',
                'returned_date' => now(),
                'condition_at_return' => request('condition_at_return', 'good'),
            ]);

            $asset->schoolAsset?->increment('available_quantity', $asset->quantity);
        });

        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'assets'])
            ->with('success', 'Asset returned successfully.');
    }

    // --- LIBRARY ACCESS ---

    public function manageLibrary(Student $student)
    {
        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'library']);
    }

    public function updateLibrary(Request $request, Student $student)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $student->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'is_member' => 'boolean',
            'max_books' => 'required|integer|min:1',
            'max_days' => 'required|integer|min:1',
            'fine_per_day' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['is_member'] = $request->boolean('is_member');
        $validated['membership_date'] = $validated['is_member'] ? ($student->libraryAccess->membership_date ?? now()) : null;

        if ($student->libraryAccess) {
            $student->libraryAccess->update($validated);
        } else {
            $validated['student_id'] = $student->id;
            StudentLibraryAccess::create($validated);
        }

        return redirect()->route('admin.students.show', ['student' => $student, 'tab' => 'library'])
            ->with('success', 'Library access updated successfully.');
    }
}
