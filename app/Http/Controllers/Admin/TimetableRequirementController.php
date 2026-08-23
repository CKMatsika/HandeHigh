<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Timetable\StoreTimetableRequirementRequest;
use App\Models\Curriculum;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableRequirement;
use App\Services\AuditService;
use Illuminate\Http\Request;

class TimetableRequirementController extends Controller
{
    public function index(Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);
        $school = auth()->user()->school;

        $requirements = TimetableRequirement::where('school_id', $school->id)
            ->where(function ($q) use ($timetable) {
                $q->where('timetable_id', $timetable->id)->orWhereNull('timetable_id');
            })
            ->with(['schoolClass', 'subject', 'teacher', 'room'])
            ->get();

        $classes = SchoolClass::where('school_id', $school->id)->orderBy('grade')->orderBy('name')->get();
        $subjects = Subject::where('school_id', $school->id)->orderBy('name')->get();
        $teachers = Teacher::where('school_id', $school->id)->active()->orderBy('first_name')->get();
        $rooms = Room::where('school_id', $school->id)->orderBy('name')->get();

        $curriculumCount = Curriculum::where('school_id', $school->id)
            ->where('academic_year', $timetable->academic_year)
            ->when($timetable->term, fn ($q) => $q->where('term', $timetable->term))
            ->count();

        return view('admin.timetables.requirements', compact(
            'timetable',
            'requirements',
            'classes',
            'subjects',
            'teachers',
            'rooms',
            'curriculumCount'
        ));
    }

    public function store(Timetable $timetable, StoreTimetableRequirementRequest $request)
    {
        $this->authorizeSchoolAccess($timetable);

        $validated = $request->validated();
        $validated['school_id'] = $timetable->school_id;
        $validated['timetable_id'] = $timetable->id;
        $validated['max_daily_lessons'] = $validated['max_daily_lessons'] ?? 2;
        $validated['is_double_period_allowed'] = $request->boolean('is_double_period_allowed', false);
        $validated['priority'] = $validated['priority'] ?? 1;

        $requirement = TimetableRequirement::create($validated);

        AuditService::log('create', $requirement, "Timetable requirement added for Class #{$requirement->school_class_id}, Subject #{$requirement->subject_id}", 'timetable');

        return redirect()->route('admin.timetables.requirements.index', $timetable)
            ->with('success', 'Scheduling requirement saved successfully.');
    }

    public function importFromCurriculum(Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);
        $school = auth()->user()->school;

        $curricula = Curriculum::where('school_id', $school->id)
            ->where('academic_year', $timetable->academic_year)
            ->when($timetable->term, fn ($q) => $q->where('term', $timetable->term))
            ->get();

        $imported = 0;
        foreach ($curricula as $c) {
            $teacherId = null;
            if ($c->teacher_id) {
                $teacher = Teacher::where('school_id', $school->id)
                    ->where(function ($q) use ($c) {
                        $q->where('user_id', $c->teacher_id)->orWhere('id', $c->teacher_id);
                    })
                    ->first();
                $teacherId = $teacher?->id;
            }

            TimetableRequirement::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'timetable_id' => $timetable->id,
                    'school_class_id' => $c->class_id,
                    'subject_id' => $c->subject_id,
                ],
                [
                    'teacher_id' => $teacherId,
                    'weekly_periods' => $c->weekly_periods ?? 1,
                    'max_daily_lessons' => 2,
                    'priority' => 1,
                ]
            );
            $imported++;
        }

        AuditService::log('import', $timetable, "Imported {$imported} scheduling requirement(s) from curriculum", 'timetable');

        return redirect()->route('admin.timetables.requirements.index', $timetable)
            ->with('success', "Imported {$imported} scheduling requirement(s) from curriculum.");
    }

    public function destroy(Timetable $timetable, TimetableRequirement $requirement)
    {
        $this->authorizeSchoolAccess($timetable);

        if ((int) $requirement->school_id !== (int) $timetable->school_id) {
            abort(404);
        }

        $requirement->delete();

        AuditService::log('delete', null, "Timetable requirement #{$requirement->id} removed", 'timetable');

        return redirect()->route('admin.timetables.requirements.index', $timetable)
            ->with('success', 'Scheduling requirement removed.');
    }

    protected function authorizeSchoolAccess(Timetable $timetable): void
    {
        $school = auth()->user()?->school;
        if (! $school || (int) $timetable->school_id !== (int) $school->id) {
            abort(403);
        }
    }
}
