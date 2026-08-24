<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeacherAbsence;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Models\TimetableSubstitution;
use App\Services\Timetable\Substitution\SubstitutionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableSubstitutionController extends Controller
{
    public function __construct(
        protected SubstitutionService $substitutionService
    ) {}

    public function index(Request $request): View
    {
        $schoolId = $request->user()->school_id;

        $query = TimetableSubstitution::where('school_id', $schoolId)
            ->with(['originalTeacher', 'substituteTeacher', 'slot.schoolClass', 'slot.subject', 'slot.room', 'approvedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }
        if ($request->filled('teacher_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('original_teacher_id', $request->teacher_id)
                    ->orWhere('substitute_teacher_id', $request->teacher_id);
            });
        }

        $substitutions = $query->latest('date')->paginate(15);
        $teachers = Teacher::where('school_id', $schoolId)->where('status', true)->orderBy('first_name')->get();

        return view('admin.timetables.substitutions.index', [
            'substitutions' => $substitutions,
            'teachers' => $teachers,
        ]);
    }

    /**
     * Get candidate substitute recommendations for a specific slot and date.
     */
    public function recommend(Request $request, TimetableSlot $slot): JsonResponse|View
    {
        $dateStr = $request->get('date', now()->toDateString());
        $recommendations = $this->substitutionService->recommendSubstitutes($slot, $dateStr);

        if ($request->wantsJson()) {
            return response()->json([
                'slot_id' => $slot->id,
                'date' => $dateStr,
                'original_teacher' => $slot->teacher?->full_name,
                'class' => $slot->schoolClass?->name,
                'subject' => $slot->subject?->name,
                'time' => $slot->getFormattedTime(),
                'recommendations' => $recommendations->map(fn ($r) => $r->toArray()),
            ]);
        }

        return view('admin.timetables.substitutions.recommend', [
            'slot' => $slot,
            'date' => $dateStr,
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * Assign and approve a substitute directly or create a pending request.
     */
    public function store(Request $request, TimetableSlot $slot): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'substitute_teacher_id' => 'required|exists:teachers,id',
            'date' => 'required|date',
            'teacher_absence_id' => 'nullable|exists:teacher_absences,id',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'auto_approve' => 'nullable|boolean',
            'expected_revision' => 'nullable|integer',
        ]);

        $substitute = Teacher::where('school_id', $slot->timetable->school_id)->findOrFail($validated['substitute_teacher_id']);
        $absence = ! empty($validated['teacher_absence_id']) ? TeacherAbsence::where('school_id', $slot->timetable->school_id)->findOrFail($validated['teacher_absence_id']) : null;

        try {
            if ($request->boolean('auto_approve', true)) {
                // Directly create and approve through change service
                $substitution = $this->substitutionService->createPendingSubstitution(
                    slot: $slot,
                    substituteTeacher: $substitute,
                    date: $validated['date'],
                    absence: $absence,
                    reason: $validated['reason'] ?? 'Teacher substitution',
                    notes: $validated['notes'] ?? null
                );

                $this->substitutionService->approveSubstitution(
                    substitution: $substitution,
                    approver: $request->user(),
                    expectedRevision: $validated['expected_revision'] ?? null
                );

                $msg = 'Substitute teacher assigned and approved successfully.';
            } else {
                $substitution = $this->substitutionService->createPendingSubstitution(
                    slot: $slot,
                    substituteTeacher: $substitute,
                    date: $validated['date'],
                    absence: $absence,
                    reason: $validated['reason'] ?? 'Teacher substitution',
                    notes: $validated['notes'] ?? null
                );

                $msg = 'Pending substitution created for approval.';
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'substitution' => $substitution->fresh(['originalTeacher', 'substituteTeacher', 'slot']),
                ]);
            }

            return redirect()->back()->with('success', $msg);
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Approve a pending substitution.
     */
    public function approve(Request $request, TimetableSubstitution $substitution): JsonResponse|RedirectResponse
    {
        $expectedRevision = $request->input('expected_revision');

        try {
            $approved = $this->substitutionService->approveSubstitution(
                substitution: $substitution,
                approver: $request->user(),
                expectedRevision: $expectedRevision ? (int) $expectedRevision : null
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Substitution approved and timetable updated.',
                    'substitution' => $approved,
                ]);
            }

            return redirect()->back()->with('success', 'Substitution approved and timetable updated.');
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Reject a pending substitution.
     */
    public function reject(Request $request, TimetableSubstitution $substitution): JsonResponse|RedirectResponse
    {
        $reason = $request->input('reason');
        $this->substitutionService->rejectSubstitution($substitution, $request->user(), $reason);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Substitution rejected.',
            ]);
        }

        return redirect()->back()->with('success', 'Substitution rejected.');
    }
}
