<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\SchoolPeriod;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Services\AuditService;
use App\Services\Timetable\TimetableConflictService;
use App\Services\Timetable\TimetableService;
use App\Services\Timetable\TimetableValidationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TimetableController extends Controller
{
    public function __construct(
        protected TimetableService $timetableService,
        protected TimetableConflictService $conflictService,
        protected TimetableValidationService $validationService
    ) {
    }

    public function index(Request $request)
    {
        $school = auth()->user()->school;
        if (! $school) {
            abort(403);
        }

        $timetables = Timetable::where('school_id', $school->id)
            ->withCount('slots')
            ->latest()
            ->get();

        $periodsCount = $school->schoolPeriods()->count();
        $fixedActivitiesCount = $school->fixedActivities()->count();
        $examinationsCount = $school->timetableExaminations()->count();

        return view('admin.timetables.index', compact(
            'timetables',
            'periodsCount',
            'fixedActivitiesCount',
            'examinationsCount'
        ));
    }

    public function create()
    {
        $school = auth()->user()->school;
        if (! $school) {
            abort(403);
        }

        $academicYears = ['2024-2025', '2025-2026', '2026-2027', '2027-2028'];
        $terms = ['First Term', 'Second Term', 'Third Term', 'Term 1', 'Term 2', 'Term 3'];

        return view('admin.timetables.create', compact('academicYears', 'terms'));
    }

    public function store(Request $request)
    {
        $school = auth()->user()->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'academic_year' => 'required|string|max:50',
            'term' => 'required|string|max:50',
        ]);

        $timetable = $this->timetableService->createTimetable($school, $validated);

        AuditService::log('create', $timetable, "Timetable created: {$timetable->name}", 'timetable');

        return redirect()->route('admin.timetables.show', $timetable)
            ->with('success', 'Timetable created successfully. You can now build and review the schedule.');
    }

    public function show(Timetable $timetable, Request $request)
    {
        $this->authorizeSchoolAccess($timetable);

        $school = auth()->user()->school;
        $viewType = $request->get('view', 'master'); // master, class, teacher
        $classId = $request->get('class_id');
        $teacherId = $request->get('teacher_id');

        $classes = SchoolClass::where('school_id', $school->id)->orderBy('grade')->orderBy('name')->get();
        $teachers = Teacher::where('school_id', $school->id)->active()->orderBy('first_name')->get();
        $periods = SchoolPeriod::where('school_id', $school->id)->active()->orderBy('period_sequence')->get();

        $validation = $this->validationService->validate($timetable);

        $masterData = null;
        $classData = null;
        $teacherData = null;

        if ($viewType === 'class' && $classId) {
            $classData = $this->timetableService->buildClassGrid($timetable, (int) $classId);
        } elseif ($viewType === 'teacher' && $teacherId) {
            $teacherData = $this->timetableService->buildTeacherGrid($timetable, (int) $teacherId);
        } else {
            $masterData = $this->timetableService->buildMasterGrid($timetable);
        }

        return view('admin.timetables.show', compact(
            'timetable',
            'viewType',
            'classes',
            'teachers',
            'periods',
            'validation',
            'masterData',
            'classData',
            'teacherData',
            'classId',
            'teacherId'
        ));
    }

    public function edit(Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);

        return view('admin.timetables.edit', compact('timetable'));
    }

    public function update(Request $request, Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $timetable->update($validated);

        AuditService::log('update', $timetable, "Timetable updated: {$timetable->name}", 'timetable');

        return redirect()->route('admin.timetables.show', $timetable)
            ->with('success', 'Timetable updated successfully.');
    }

    public function destroy(Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);

        $name = $timetable->name;
        $timetable->delete();

        AuditService::log('delete', null, "Timetable deleted: {$name}", 'timetable');

        return redirect()->route('admin.timetables.index')
            ->with('success', 'Timetable deleted successfully.');
    }

    public function publish(Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);

        try {
            $result = $this->timetableService->publishTimetable($timetable);

            AuditService::log('publish', $timetable, "Timetable published: {$timetable->name}", 'timetable');

            return redirect()->route('admin.timetables.show', $timetable)
                ->with('success', 'Timetable published successfully! It is now active for classes, teachers, and students.');
        } catch (ValidationException $e) {
            return redirect()->route('admin.timetables.conflicts', $timetable)
                ->with('error', $e->validator->errors()->first());
        }
    }

    public function unpublish(Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);

        $this->timetableService->unpublishTimetable($timetable);

        AuditService::log('unpublish', $timetable, "Timetable reverted to draft: {$timetable->name}", 'timetable');

        return redirect()->route('admin.timetables.show', $timetable)
            ->with('success', 'Timetable reverted to draft mode.');
    }

    public function conflicts(Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);

        $validation = $this->validationService->validate($timetable);

        return view('admin.timetables.conflicts', compact('timetable', 'validation'));
    }

    public function validateSchedule(Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);

        $validation = $this->validationService->validate($timetable);

        return response()->json([
            'success' => true,
            'data' => $validation,
        ]);
    }

    public function export(Timetable $timetable, Request $request)
    {
        $this->authorizeSchoolAccess($timetable);

        $type = $request->get('type', 'master'); // master, class, teacher
        $classId = $request->get('class_id');
        $teacherId = $request->get('teacher_id');

        $data = null;
        $title = $timetable->name;

        if ($type === 'class' && $classId) {
            $data = $this->timetableService->buildClassGrid($timetable, (int) $classId);
            $className = $data['class']?->name ?? 'Class';
            $title = "Class Timetable — {$className} ({$timetable->academic_year})";
        } elseif ($type === 'teacher' && $teacherId) {
            $data = $this->timetableService->buildTeacherGrid($timetable, (int) $teacherId);
            $teacherName = $data['teacher']?->full_name ?? 'Teacher';
            $title = "Teacher Timetable — {$teacherName} ({$timetable->academic_year})";
        } else {
            $data = $this->timetableService->buildMasterGrid($timetable);
            $title = "Master School Timetable — {$timetable->school->name} ({$timetable->academic_year} - {$timetable->term})";
        }

        return view('admin.timetables.export', compact('timetable', 'type', 'data', 'title'));
    }

    protected function authorizeSchoolAccess(Timetable $timetable): void
    {
        $school = auth()->user()?->school;
        if (! $school || $timetable->school_id !== $school->id) {
            abort(403, 'Unauthorized access to timetable outside tenant boundary.');
        }
    }
}
