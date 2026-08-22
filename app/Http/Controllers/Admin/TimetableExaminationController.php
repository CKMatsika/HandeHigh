<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableExamination;
use App\Rules\TenantExists;
use App\Services\AuditService;
use Illuminate\Http\Request;

class TimetableExaminationController extends Controller
{
    public function index(Request $request)
    {
        $school = auth()->user()?->school;
        if (! $school) {
            abort(403);
        }

        $examinations = $school->timetableExaminations()
            ->with(['timetable', 'subject', 'schoolClass', 'room', 'supervisorTeacher'])
            ->latest()
            ->get();

        $subjects = Subject::where('school_id', $school->id)->orderBy('name')->get();
        $classes = SchoolClass::where('school_id', $school->id)->orderBy('grade')->orderBy('name')->get();
        $teachers = Teacher::where('school_id', $school->id)->active()->orderBy('first_name')->get();
        $rooms = Room::where('school_id', $school->id)->active()->get();
        $exams = Exam::where('school_id', $school->id)->get();
        $timetables = Timetable::where('school_id', $school->id)->get();

        return view('admin.timetables.examinations', compact(
            'examinations',
            'subjects',
            'classes',
            'teachers',
            'rooms',
            'exams',
            'timetables'
        ));
    }

    public function store(Request $request)
    {
        $school = auth()->user()?->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'exam_type' => 'required|string|in:internal,mock,national,other',
            'day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'exam_date' => 'nullable|date',
            'timetable_id' => ['nullable', TenantExists::make('timetables')],
            'exam_id' => ['nullable', TenantExists::make('exams')],
            'subject_id' => ['nullable', TenantExists::make('subjects')],
            'school_class_id' => ['nullable', TenantExists::make('classes')],
            'supervisor_teacher_id' => ['nullable', TenantExists::make('teachers')],
            'room_id' => ['nullable', TenantExists::make('rooms')],
            'is_locked' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        // National exams are strictly hard locked
        if ($validated['exam_type'] === 'national') {
            $validated['is_locked'] = true;
        } else {
            $validated['is_locked'] = $request->boolean('is_locked', true);
        }

        $exam = $school->timetableExaminations()->create($validated);

        AuditService::log('create', $exam, "Timetable examination created: {$exam->title} ({$exam->exam_type})", 'timetable');

        return redirect()->route('admin.timetables.examinations.index')
            ->with('success', "Examination slot '{$exam->title}' created successfully.");
    }

    public function update(Request $request, TimetableExamination $examination)
    {
        $school = auth()->user()?->school;
        if (! $school || $examination->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'exam_type' => 'required|string|in:internal,mock,national,other',
            'day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'exam_date' => 'nullable|date',
            'timetable_id' => ['nullable', TenantExists::make('timetables')],
            'exam_id' => ['nullable', TenantExists::make('exams')],
            'subject_id' => ['nullable', TenantExists::make('subjects')],
            'school_class_id' => ['nullable', TenantExists::make('classes')],
            'supervisor_teacher_id' => ['nullable', TenantExists::make('teachers')],
            'room_id' => ['nullable', TenantExists::make('rooms')],
            'is_locked' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validated['exam_type'] === 'national') {
            $validated['is_locked'] = true;
        } else {
            $validated['is_locked'] = $request->boolean('is_locked', true);
        }

        $examination->update($validated);

        AuditService::log('update', $examination, "Timetable examination updated: {$examination->title}", 'timetable');

        return redirect()->route('admin.timetables.examinations.index')
            ->with('success', "Examination slot '{$examination->title}' updated successfully.");
    }

    public function destroy(TimetableExamination $examination)
    {
        $school = auth()->user()?->school;
        if (! $school || $examination->school_id !== $school->id) {
            abort(403);
        }

        $title = $examination->title;
        $examination->delete();

        AuditService::log('delete', null, "Timetable examination deleted: {$title}", 'timetable');

        return redirect()->route('admin.timetables.examinations.index')
            ->with('success', "Examination slot '{$title}' deleted successfully.");
    }
}
