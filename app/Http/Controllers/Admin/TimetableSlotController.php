<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Rules\TenantExists;
use App\Services\AuditService;
use App\Services\Timetable\TimetableConflictService;
use App\Services\Timetable\TimetableService;
use Illuminate\Http\Request;

class TimetableSlotController extends Controller
{
    public function __construct(
        protected TimetableService $timetableService,
        protected TimetableConflictService $conflictService
    ) {
    }

    public function store(Timetable $timetable, Request $request)
    {
        $this->authorizeSchoolAccess($timetable);

        $validated = $request->validate([
            'school_class_id' => ['required', TenantExists::make('classes')],
            'subject_id' => ['required', TenantExists::make('subjects')],
            'teacher_id' => ['nullable', TenantExists::make('teachers')],
            'room_id' => ['nullable', TenantExists::make('rooms')],
            'school_period_id' => ['nullable', TenantExists::make('school_periods')],
            'day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'is_locked' => 'sometimes|boolean',
            'slot_type' => 'sometimes|string|in:lesson,activity,exam',
            'activity_name' => 'nullable|string|max:255',
        ]);

        $validated['is_locked'] = $request->boolean('is_locked', false);
        $validated['slot_type'] = $validated['slot_type'] ?? 'lesson';

        $slot = $this->timetableService->assignSlot($timetable, $validated);

        AuditService::log('create', $slot, "Timetable slot added for Class #{$slot->school_class_id} on {$slot->day_of_week}", 'timetable');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Slot assigned successfully.',
                'slot' => $slot,
                'has_conflicts' => $slot->hasConflicts(),
                'conflicts' => $slot->conflicts,
            ]);
        }

        return redirect()->route('admin.timetables.show', $timetable)
            ->with('success', 'Slot scheduled successfully.');
    }

    public function update(Timetable $timetable, TimetableSlot $slot, Request $request)
    {
        $this->authorizeSchoolAccess($timetable);

        if ($slot->timetable_id !== $timetable->id) {
            abort(404);
        }

        $validated = $request->validate([
            'school_class_id' => ['required', TenantExists::make('classes')],
            'subject_id' => ['required', TenantExists::make('subjects')],
            'teacher_id' => ['nullable', TenantExists::make('teachers')],
            'room_id' => ['nullable', TenantExists::make('rooms')],
            'school_period_id' => ['nullable', TenantExists::make('school_periods')],
            'day_of_week' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'is_locked' => 'sometimes|boolean',
            'slot_type' => 'sometimes|string|in:lesson,activity,exam',
            'activity_name' => 'nullable|string|max:255',
        ]);

        $validated['is_locked'] = $request->boolean('is_locked', $slot->is_locked);
        $validated['slot_type'] = $validated['slot_type'] ?? ($slot->slot_type ?? 'lesson');

        $updatedSlot = $this->timetableService->updateSlot($slot, $validated);

        AuditService::log('update', $updatedSlot, "Timetable slot #{$slot->id} moved/updated", 'timetable');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Slot updated successfully.',
                'slot' => $updatedSlot,
                'has_conflicts' => $updatedSlot->hasConflicts(),
                'conflicts' => $updatedSlot->conflicts,
            ]);
        }

        return redirect()->route('admin.timetables.show', $timetable)
            ->with('success', 'Slot updated successfully.');
    }

    public function destroy(Timetable $timetable, TimetableSlot $slot, Request $request)
    {
        $this->authorizeSchoolAccess($timetable);

        if ($slot->timetable_id !== $timetable->id) {
            abort(404);
        }

        $id = $slot->id;
        $this->timetableService->deleteSlot($slot);

        AuditService::log('delete', null, "Timetable slot #{$id} deleted", 'timetable');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Slot removed successfully.',
            ]);
        }

        return redirect()->route('admin.timetables.show', $timetable)
            ->with('success', 'Slot removed successfully.');
    }

    public function checkConflict(Timetable $timetable, Request $request)
    {
        $this->authorizeSchoolAccess($timetable);

        $validated = $request->validate([
            'school_class_id' => ['nullable', TenantExists::make('classes')],
            'subject_id' => ['nullable', TenantExists::make('subjects')],
            'teacher_id' => ['nullable', TenantExists::make('teachers')],
            'room_id' => ['nullable', TenantExists::make('rooms')],
            'school_period_id' => ['nullable', TenantExists::make('school_periods')],
            'day_of_week' => 'required|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'ignore_slot_id' => 'nullable|integer',
        ]);

        $results = $this->conflictService->checkSlotConflicts(
            $timetable,
            $validated,
            $validated['ignore_slot_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'has_conflicts' => $results['has_hard_conflicts'],
            'hard_count' => $results['hard_count'],
            'soft_count' => $results['soft_count'],
            'conflicts' => $results['all'],
        ]);
    }

    protected function authorizeSchoolAccess(Timetable $timetable): void
    {
        $school = auth()->user()?->school;
        if (! $school || $timetable->school_id !== $school->id) {
            abort(403);
        }
    }
}
