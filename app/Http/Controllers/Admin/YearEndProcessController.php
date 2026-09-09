<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BedAssignment;
use App\Models\BorrowRecord;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAsset;
use App\Models\StudentClearance;
use App\Models\YearEndProcess;
use App\Services\YearEnd\YearEndProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class YearEndProcessController extends Controller
{
    protected YearEndProcessingService $yearEndService;

    public function __construct(YearEndProcessingService $yearEndService)
    {
        $this->yearEndService = $yearEndService;
    }

    /**
     * Display Year-End Processes Dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school) abort(403);

        $currentYear = date('Y');
        $nextYear = (string) (((int) $currentYear) + 1);

        // Fetch recent transition processes
        $processes = YearEndProcess::where('school_id', $school->id)
            ->with(['creator', 'approver'])
            ->latest()
            ->paginate(10);

        // Active draft if any
        $activeDraft = YearEndProcess::where('school_id', $school->id)
            ->where('status', 'draft')
            ->latest()
            ->first();

        // Clearance statistics
        $pendingClearanceCount = StudentClearance::where('school_id', $school->id)
            ->where('status', 'pending_clearance')
            ->count();

        $fullyClearedCount = StudentClearance::where('school_id', $school->id)
            ->where('status', 'fully_cleared')
            ->count();

        $permanentlyExitedCount = StudentClearance::where('school_id', $school->id)
            ->where('status', 'permanently_exited')
            ->count();

        $totalActiveStudents = Student::where('school_id', $school->id)
            ->where('status', 'active')
            ->count();

        $graduatingCandidateCount = Student::where('school_id', $school->id)
            ->where('status', 'active')
            ->whereIn('grade', YearEndProcessingService::GRADUATING_GRADES)
            ->count();

        return view('admin.year_end.index', compact(
            'school',
            'processes',
            'activeDraft',
            'currentYear',
            'nextYear',
            'pendingClearanceCount',
            'fullyClearedCount',
            'permanentlyExitedCount',
            'totalActiveStudents',
            'graduatingCandidateCount'
        ));
    }

    /**
     * Generate an automated Year-End Transition Draft.
     */
    public function createDraft(Request $request)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school) abort(403);

        $validated = $request->validate([
            'source_academic_year' => 'required|string',
            'target_academic_year' => 'required|string|different:source_academic_year',
        ]);

        $draft = $this->yearEndService->generateTransitionDraft(
            $school,
            $validated['source_academic_year'],
            $validated['target_academic_year'],
            $user
        );

        return redirect()->route('admin.year-end.draft', $draft)
            ->with('success', "Year-End Transition Draft for {$draft->source_academic_year} → {$draft->target_academic_year} generated successfully.");
    }

    /**
     * Show interactive transition draft review.
     */
    public function showDraft(Request $request, YearEndProcess $process)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $process->school_id !== $school->id) abort(403);

        $payload = $process->draft_payload ?? [];

        // Available grades and classes in school
        $schoolClasses = SchoolClass::where('school_id', $school->id)->get();
        $distinctGrades = collect($payload)->pluck('current_grade')->unique()->sort()->values();

        // Filtering
        $selectedGrade = $request->get('grade');
        $selectedAction = $request->get('action');
        $search = strtolower($request->get('search', ''));

        $filteredItems = collect($payload)->filter(function ($item) use ($selectedGrade, $selectedAction, $search) {
            if ($selectedGrade && ($item['current_grade'] ?? '') !== $selectedGrade) {
                return false;
            }
            if ($selectedAction && ($item['proposed_action'] ?? '') !== $selectedAction) {
                return false;
            }
            if ($search) {
                $nameMatch = str_contains(strtolower($item['name'] ?? ''), $search);
                $admMatch = str_contains(strtolower($item['admission_number'] ?? ''), $search);
                if (!$nameMatch && !$admMatch) {
                    return false;
                }
            }
            return true;
        })->values()->all();

        return view('admin.year_end.draft', compact(
            'school',
            'process',
            'filteredItems',
            'distinctGrades',
            'schoolClasses',
            'selectedGrade',
            'selectedAction',
            'search'
        ));
    }

    /**
     * Update individual student action or target class in draft.
     */
    public function updateDraftStudent(Request $request, YearEndProcess $process)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $process->school_id !== $school->id) abort(403);
        if ($process->status !== 'draft') {
            return back()->with('error', 'Cannot edit a process that is not in draft status.');
        }

        $validated = $request->validate([
            'student_id' => 'required|integer',
            'proposed_action' => 'required|string|in:promote,repeat,move_to_clearance,transfer_out,exit_now',
            'target_grade' => 'nullable|string',
            'target_class_name' => 'nullable|string',
        ]);

        $payload = $process->draft_payload ?? [];
        $found = false;
        $promotions = 0;
        $clearances = 0;
        $retentions = 0;
        $transfers = 0;

        foreach ($payload as &$item) {
            if ($item['student_id'] == $validated['student_id']) {
                $item['proposed_action'] = $validated['proposed_action'];
                $item['target_grade'] = $validated['target_grade'] ?? $item['target_grade'];
                $item['target_class_name'] = $validated['target_class_name'] ?? $item['target_class_name'];
                $found = true;
            }

            // Recount summary
            match ($item['proposed_action']) {
                'promote' => $promotions++,
                'repeat', 'retain' => $retentions++,
                'move_to_clearance', 'graduate' => $clearances++,
                'transfer_out' => $transfers++,
                default => null,
            };
        }

        if ($found) {
            $summary = [
                'total_students' => count($payload),
                'promotions_count' => $promotions,
                'clearance_count' => $clearances,
                'retentions_count' => $retentions,
                'transfers_count' => $transfers,
            ];

            $process->update([
                'draft_payload' => $payload,
                'summary' => $summary,
            ]);

            return back()->with('success', 'Student transition preference updated.');
        }

        return back()->with('error', 'Student not found in draft.');
    }

    /**
     * Approve and execute Year-End Transition Draft.
     */
    public function approveAndExecute(Request $request, YearEndProcess $process)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $process->school_id !== $school->id) abort(403);
        if ($process->status !== 'draft') {
            return back()->with('error', 'Process has already been executed or cancelled.');
        }

        $this->yearEndService->executeTransitionBatch($process, $user);

        return redirect()->route('admin.year-end.index')
            ->with('success', "Year-End Process for {$process->source_academic_year} → {$process->target_academic_year} executed successfully! Students have been promoted and graduating leavers are now in the Clearance Center.");
    }

    /**
     * Clearance Command Center.
     */
    public function clearanceIndex(Request $request)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school) abort(403);

        $statusFilter = $request->get('status', 'pending');
        $gradeFilter = $request->get('grade');
        $search = $request->get('search');

        $query = StudentClearance::where('student_clearances.school_id', $school->id)
            ->with(['student', 'yearEndProcess', 'financeClearedBy', 'libraryClearedBy', 'assetsClearedBy', 'boardingClearedBy', 'exitedBy']);

        if ($statusFilter === 'pending') {
            $query->where('student_clearances.status', 'pending_clearance');
        } elseif ($statusFilter === 'fully_cleared') {
            $query->where('student_clearances.status', 'fully_cleared');
        } elseif ($statusFilter === 'permanently_exited') {
            $query->where('student_clearances.status', 'permanently_exited');
        } elseif ($statusFilter === 'blocked_finance') {
            $query->where('finance_status', 'pending')->where('student_clearances.status', '!=', 'permanently_exited');
        } elseif ($statusFilter === 'blocked_library') {
            $query->where('library_status', 'pending')->where('student_clearances.status', '!=', 'permanently_exited');
        } elseif ($statusFilter === 'blocked_assets') {
            $query->where('assets_status', 'pending')->where('student_clearances.status', '!=', 'permanently_exited');
        }

        if ($gradeFilter) {
            $query->where('graduation_grade', $gradeFilter);
        }

        if ($search) {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('admission_number', 'like', "%{$search}%");
            });
        }

        $clearances = $query->latest()->paginate(15);

        // Counts for tab badges
        $counts = [
            'all' => StudentClearance::where('school_id', $school->id)->count(),
            'pending' => StudentClearance::where('school_id', $school->id)->where('status', 'pending_clearance')->count(),
            'fully_cleared' => StudentClearance::where('school_id', $school->id)->where('status', 'fully_cleared')->count(),
            'permanently_exited' => StudentClearance::where('school_id', $school->id)->where('status', 'permanently_exited')->count(),
            'blocked_finance' => StudentClearance::where('school_id', $school->id)->where('finance_status', 'pending')->where('status', '!=', 'permanently_exited')->count(),
            'blocked_library' => StudentClearance::where('school_id', $school->id)->where('library_status', 'pending')->where('status', '!=', 'permanently_exited')->count(),
            'blocked_assets' => StudentClearance::where('school_id', $school->id)->where('assets_status', 'pending')->where('status', '!=', 'permanently_exited')->count(),
        ];

        return view('admin.year_end.clearance', compact(
            'school',
            'clearances',
            'statusFilter',
            'gradeFilter',
            'search',
            'counts'
        ));
    }

    /**
     * Show detailed clearance checklist for an individual student.
     */
    public function clearanceShow(StudentClearance $clearance)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $clearance->school_id !== $school->id) abort(403);

        $student = $clearance->student;

        // Fetch live department details
        $borrowedBooks = BorrowRecord::where('student_id', $student->id)
            ->whereNull('returned_at')
            ->with('book')
            ->get();

        $allocatedAssets = StudentAsset::where('student_id', $student->id)
            ->where('status', 'allocated')
            ->with('schoolAsset')
            ->get();

        $activeBed = BedAssignment::where('student_id', $student->id)
            ->where('is_current', true)
            ->with(['bed.dormitory.hostel'])
            ->first();

        // Refresh live clearance evaluations
        $liveEvaluation = $this->yearEndService->evaluateStudentClearance($student);

        // Update clearance record if values differ
        if ($clearance->status !== 'permanently_exited') {
            $clearance->update([
                'finance_balance' => $liveEvaluation['finance_balance'],
                'unreturned_books_count' => $liveEvaluation['unreturned_books_count'],
                'unreturned_assets_count' => $liveEvaluation['unreturned_assets_count'],
            ]);
            $clearance->refreshOverallStatus();
            $clearance->save();
        }

        return view('admin.year_end.clearance_show', compact(
            'school',
            'clearance',
            'student',
            'borrowedBooks',
            'allocatedAssets',
            'activeBed',
            'liveEvaluation'
        ));
    }

    /**
     * Approve or waive a specific department clearance checkpoint.
     */
    public function clearDepartment(Request $request, StudentClearance $clearance)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $clearance->school_id !== $school->id) abort(403);

        $validated = $request->validate([
            'department' => 'required|string|in:finance,library,assets,boarding',
            'action' => 'required|string|in:cleared,waived,pending',
            'remarks' => 'nullable|string|max:500',
        ]);

        $dept = $validated['department'];
        $action = $validated['action'];
        $remarks = $validated['remarks'] ?? null;

        $updates = [
            "{$dept}_status" => $action,
            "{$dept}_cleared_by" => $action !== 'pending' ? $user->id : null,
            "{$dept}_cleared_at" => $action !== 'pending' ? now() : null,
            "{$dept}_remarks" => $remarks,
        ];

        $clearance->update($updates);
        $clearance->refreshOverallStatus();
        $clearance->save();

        return back()->with('success', ucfirst($dept) . ' clearance status updated to ' . ucfirst($action) . '.');
    }

    /**
     * Bulk clear or waive selected clearances for a department.
     */
    public function bulkClearDepartment(Request $request)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school) abort(403);

        $validated = $request->validate([
            'clearance_ids' => 'required|array',
            'clearance_ids.*' => 'integer|exists:student_clearances,id',
            'department' => 'required|string|in:finance,library,assets,boarding',
            'action' => 'required|string|in:cleared,waived',
            'remarks' => 'nullable|string|max:500',
        ]);

        $dept = $validated['department'];
        $action = $validated['action'];
        $remarks = $validated['remarks'] ?? 'Bulk clearance approval';

        $clearances = StudentClearance::where('school_id', $school->id)
            ->whereIn('id', $validated['clearance_ids'])
            ->where('status', '!=', 'permanently_exited')
            ->get();

        foreach ($clearances as $c) {
            $c->update([
                "{$dept}_status" => $action,
                "{$dept}_cleared_by" => $user->id,
                "{$dept}_cleared_at" => now(),
                "{$dept}_remarks" => $remarks,
            ]);
            $c->refreshOverallStatus();
            $c->save();
        }

        return back()->with('success', count($clearances) . ' students updated for ' . ucfirst($dept) . ' clearance.');
    }

    /**
     * Finalize permanent exit and graduate student.
     */
    public function finalizeExit(Request $request, StudentClearance $clearance)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $clearance->school_id !== $school->id) abort(403);

        if (!$clearance->isFullyCleared() && !$request->boolean('force_override')) {
            return back()->with('error', 'Student cannot be permanently exited until all clearance checkpoints are satisfied or waived.');
        }

        $validated = $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $this->yearEndService->finalizeStudentExit($clearance, $user, $validated);

        return redirect()->route('admin.year-end.clearance.show', $clearance)
            ->with('success', "Student {$clearance->student->full_name} has been permanently graduated & archived. Clearance Certificate #{$clearance->certificate_number} issued.");
    }

    /**
     * Bulk finalize exit for all fully cleared students.
     */
    public function bulkFinalizeExit(Request $request)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school) abort(403);

        $clearedStudents = StudentClearance::where('school_id', $school->id)
            ->where('status', 'fully_cleared')
            ->get();

        if ($clearedStudents->isEmpty()) {
            return back()->with('info', 'No fully cleared students available for bulk exit.');
        }

        $count = 0;
        foreach ($clearedStudents as $c) {
            $this->yearEndService->finalizeStudentExit($c, $user, ['remarks' => 'Bulk graduation and exit clearance finalized.']);
            $count++;
        }

        return back()->with('success', "Successfully finalized permanent exit and issued certificates for {$count} cleared graduates.");
    }

    /**
     * Print official clearance certificate.
     */
    public function printCertificate(StudentClearance $clearance)
    {
        $user = Auth::user();
        $school = $user->school;
        if (!$school || $clearance->school_id !== $school->id) abort(403);

        $student = $clearance->student;

        return view('admin.year_end.certificate', compact('school', 'clearance', 'student'));
    }
}
