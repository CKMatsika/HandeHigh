<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\SchoolPeriod;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableFixedActivity;
use App\Rules\TenantExists;
use App\Services\AuditService;
use Illuminate\Http\Request;

class TimetableFixedActivityController extends Controller
{
    public function index(Request $request)
    {
        $school = auth()->user()?->school;
        if (! $school) {
            abort(403);
        }

        $activities = $school->fixedActivities()
            ->with(['timetable', 'schoolClass', 'teacher', 'room', 'schoolPeriod'])
            ->latest()
            ->get();

        $classes = SchoolClass::where('school_id', $school->id)->orderBy('grade')->orderBy('name')->get();
        $teachers = Teacher::where('school_id', $school->id)->active()->orderBy('first_name')->get();
        $rooms = Room::where('school_id', $school->id)->active()->get();
        $periods = SchoolPeriod::where('school_id', $school->id)->active()->orderBy('period_sequence')->get();
        $timetables = Timetable::where('school_id', $school->id)->get();

        return view('admin.timetables.fixed_activities', compact(
            'activities',
            'classes',
            'teachers',
            'rooms',
            'periods',
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
            'name' => 'required|string|max:255',
            'activity_type' => 'required|string|in:assembly,chapel,sport,club,staff_meeting,other',
            'day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'timetable_id' => ['nullable', TenantExists::make('timetables')],
            'school_period_id' => ['nullable', TenantExists::make('school_periods')],
            'school_class_id' => ['nullable', TenantExists::make('classes')],
            'teacher_id' => ['nullable', TenantExists::make('teachers')],
            'room_id' => ['nullable', TenantExists::make('rooms')],
            'is_locked' => 'sometimes|boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        $validated['is_locked'] = $request->boolean('is_locked', true);

        $activity = $school->fixedActivities()->create($validated);

        AuditService::log('create', $activity, "Fixed activity created: {$activity->name} on {$activity->day_of_week}", 'timetable');

        return redirect()->route('admin.timetables.fixed-activities.index')
            ->with('success', "Fixed activity '{$activity->name}' created successfully.");
    }

    public function update(Request $request, TimetableFixedActivity $fixedActivity)
    {
        $school = auth()->user()?->school;
        if (! $school || $fixedActivity->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'activity_type' => 'required|string|in:assembly,chapel,sport,club,staff_meeting,other',
            'day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'timetable_id' => ['nullable', TenantExists::make('timetables')],
            'school_period_id' => ['nullable', TenantExists::make('school_periods')],
            'school_class_id' => ['nullable', TenantExists::make('classes')],
            'teacher_id' => ['nullable', TenantExists::make('teachers')],
            'room_id' => ['nullable', TenantExists::make('rooms')],
            'is_locked' => 'sometimes|boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        $validated['is_locked'] = $request->boolean('is_locked', true);

        $fixedActivity->update($validated);

        AuditService::log('update', $fixedActivity, "Fixed activity updated: {$fixedActivity->name}", 'timetable');

        return redirect()->route('admin.timetables.fixed-activities.index')
            ->with('success', "Fixed activity '{$fixedActivity->name}' updated successfully.");
    }

    public function destroy(TimetableFixedActivity $fixedActivity)
    {
        $school = auth()->user()?->school;
        if (! $school || $fixedActivity->school_id !== $school->id) {
            abort(403);
        }

        $name = $fixedActivity->name;
        $fixedActivity->delete();

        AuditService::log('delete', null, "Fixed activity deleted: {$name}", 'timetable');

        return redirect()->route('admin.timetables.fixed-activities.index')
            ->with('success', "Fixed activity '{$name}' deleted successfully.");
    }
}
