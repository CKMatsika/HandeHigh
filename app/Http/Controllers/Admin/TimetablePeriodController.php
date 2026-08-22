<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolPeriod;
use App\Services\AuditService;
use App\Services\Timetable\TimetablePeriodService;
use Illuminate\Http\Request;

class TimetablePeriodController extends Controller
{
    public function __construct(
        protected TimetablePeriodService $periodService
    ) {
    }

    public function index()
    {
        $school = auth()->user()?->school;
        if (! $school) {
            abort(403);
        }

        $periods = $school->schoolPeriods()
            ->orderBy('period_sequence')
            ->get();

        return view('admin.timetables.periods', compact('periods'));
    }

    public function store(Request $request)
    {
        $school = auth()->user()?->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'period_sequence' => 'required|integer|min:1|max:50',
            'day_of_week' => 'nullable|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'period_type' => 'required|string|in:lesson,break,lunch,assembly,chapel,sport,club,other',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $period = $this->periodService->createPeriod($school, $validated);

        AuditService::log('create', $period, "School period created: {$period->name} ({$period->getFormattedTime()})", 'timetable');

        return redirect()->route('admin.timetables.periods.index')
            ->with('success', "Period '{$period->name}' created successfully.");
    }

    public function update(Request $request, SchoolPeriod $period)
    {
        $school = auth()->user()?->school;
        if (! $school || $period->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'period_sequence' => 'required|integer|min:1|max:50',
            'day_of_week' => 'nullable|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'period_type' => 'required|string|in:lesson,break,lunch,assembly,chapel,sport,club,other',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $this->periodService->updatePeriod($period, $validated);

        AuditService::log('update', $period, "School period updated: {$period->name}", 'timetable');

        return redirect()->route('admin.timetables.periods.index')
            ->with('success', "Period '{$period->name}' updated successfully.");
    }

    public function destroy(SchoolPeriod $period)
    {
        $school = auth()->user()?->school;
        if (! $school || $period->school_id !== $school->id) {
            abort(403);
        }

        $name = $period->name;
        $period->delete();

        AuditService::log('delete', null, "School period deleted: {$name}", 'timetable');

        return redirect()->route('admin.timetables.periods.index')
            ->with('success', "Period '{$name}' deleted successfully.");
    }

    public function seedDefaults()
    {
        $school = auth()->user()?->school;
        if (! $school) {
            abort(403);
        }

        $this->periodService->seedDefaultPeriods($school);

        return redirect()->route('admin.timetables.periods.index')
            ->with('success', 'Default school periods initialized successfully.');
    }
}
