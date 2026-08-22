<?php

namespace App\Services\Timetable;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolPeriod;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TimetableService
{
    public function __construct(
        protected TimetableConflictService $conflictService,
        protected TimetableValidationService $validationService
    ) {
    }

    /**
     * Create a new timetable.
     */
    public function createTimetable(School $school, array $data): Timetable
    {
        $timetable = $school->timetables()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'academic_year' => $data['academic_year'],
            'term' => $data['term'],
            'status' => 'draft',
            'settings' => $data['settings'] ?? [
                'school_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'preferences' => [
                    'balance_teacher_workload' => true,
                    'avoid_consecutive_same_subject' => true,
                    'prefer_morning_core_subjects' => true,
                ],
            ],
        ]);

        return $timetable;
    }

    /**
     * Publish a timetable after validating for hard conflicts.
     */
    public function publishTimetable(Timetable $timetable): array
    {
        $publishCheck = $this->validationService->validateForPublish($timetable);

        if (! $publishCheck['allowed']) {
            throw ValidationException::withMessages([
                'timetable' => [$publishCheck['message']],
            ]);
        }

        $timetable->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Timetable published successfully.',
            'timetable' => $timetable->fresh(),
        ];
    }

    /**
     * Unpublish / revert to draft.
     */
    public function unpublishTimetable(Timetable $timetable): Timetable
    {
        $timetable->update([
            'status' => 'draft',
            'published_at' => null,
        ]);

        return $timetable->fresh();
    }

    /**
     * Add or assign a slot with automatic conflict evaluation.
     */
    public function assignSlot(Timetable $timetable, array $slotData): TimetableSlot
    {
        $slot = $timetable->slots()->create($slotData);
        $this->conflictService->syncSlotConflicts($timetable);

        return $slot->fresh(['schoolClass', 'subject', 'teacher', 'room', 'schoolPeriod']);
    }

    /**
     * Update/move a slot with automatic conflict re-evaluation.
     */
    public function updateSlot(TimetableSlot $slot, array $slotData): TimetableSlot
    {
        $slot->update($slotData);
        $this->conflictService->syncSlotConflicts($slot->timetable);

        return $slot->fresh(['schoolClass', 'subject', 'teacher', 'room', 'schoolPeriod']);
    }

    /**
     * Remove a slot.
     */
    public function deleteSlot(TimetableSlot $slot): void
    {
        $timetable = $slot->timetable;
        $slot->delete();
        $this->conflictService->syncSlotConflicts($timetable);
    }

    /**
     * Build matrix grid representation for a specific Class.
     */
    public function buildClassGrid(Timetable $timetable, int $classId): array
    {
        $days = $timetable->settings['school_days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $periods = SchoolPeriod::where('school_id', $timetable->school_id)
            ->active()
            ->orderBy('period_sequence')
            ->get();

        $slots = $timetable->slots()
            ->where('school_class_id', $classId)
            ->with(['subject', 'teacher', 'room', 'schoolPeriod'])
            ->get();

        $grid = [];
        foreach ($days as $day) {
            $grid[$day] = [];
            foreach ($periods as $period) {
                $matchingSlot = $slots->first(function ($s) use ($day, $period) {
                    if (strcasecmp($s->day_of_week, $day) !== 0) {
                        return false;
                    }
                    if ($s->school_period_id && $s->school_period_id === $period->id) {
                        return true;
                    }
                    $sStart = substr((string) ($s->start_time instanceof \DateTimeInterface ? $s->start_time->format('H:i') : $s->start_time), 0, 5);
                    $pStart = substr((string) $period->start_time, 0, 5);

                    return $sStart === $pStart;
                });

                $grid[$day][$period->id] = [
                    'period' => $period,
                    'slot' => $matchingSlot,
                ];
            }
        }

        return [
            'days' => $days,
            'periods' => $periods,
            'grid' => $grid,
            'class' => SchoolClass::find($classId),
        ];
    }

    /**
     * Build matrix grid representation for a specific Teacher.
     */
    public function buildTeacherGrid(Timetable $timetable, int $teacherId): array
    {
        $days = $timetable->settings['school_days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $periods = SchoolPeriod::where('school_id', $timetable->school_id)
            ->active()
            ->orderBy('period_sequence')
            ->get();

        $slots = $timetable->slots()
            ->where('teacher_id', $teacherId)
            ->with(['schoolClass', 'subject', 'room', 'schoolPeriod'])
            ->get();

        $grid = [];
        foreach ($days as $day) {
            $grid[$day] = [];
            foreach ($periods as $period) {
                $matchingSlot = $slots->first(function ($s) use ($day, $period) {
                    if (strcasecmp($s->day_of_week, $day) !== 0) {
                        return false;
                    }
                    if ($s->school_period_id && $s->school_period_id === $period->id) {
                        return true;
                    }
                    $sStart = substr((string) ($s->start_time instanceof \DateTimeInterface ? $s->start_time->format('H:i') : $s->start_time), 0, 5);
                    $pStart = substr((string) $period->start_time, 0, 5);

                    return $sStart === $pStart;
                });

                $grid[$day][$period->id] = [
                    'period' => $period,
                    'slot' => $matchingSlot,
                ];
            }
        }

        return [
            'days' => $days,
            'periods' => $periods,
            'grid' => $grid,
            'teacher' => Teacher::find($teacherId),
        ];
    }

    /**
     * Build master school grid with all classes across all periods and days.
     */
    public function buildMasterGrid(Timetable $timetable): array
    {
        $days = $timetable->settings['school_days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $periods = SchoolPeriod::where('school_id', $timetable->school_id)
            ->active()
            ->orderBy('period_sequence')
            ->get();

        $classes = SchoolClass::where('school_id', $timetable->school_id)
            ->orderBy('grade')
            ->orderBy('name')
            ->get();

        $slots = $timetable->slots()
            ->with(['schoolClass', 'subject', 'teacher', 'room', 'schoolPeriod'])
            ->get();

        $fixedActivities = $timetable->school->fixedActivities()
            ->where(function ($q) use ($timetable) {
                $q->where('timetable_id', $timetable->id)->orWhereNull('timetable_id');
            })
            ->get();

        $examinations = $timetable->school->timetableExaminations()
            ->where(function ($q) use ($timetable) {
                $q->where('timetable_id', $timetable->id)->orWhereNull('timetable_id');
            })
            ->get();

        return [
            'days' => $days,
            'periods' => $periods,
            'classes' => $classes,
            'slots' => $slots,
            'fixed_activities' => $fixedActivities,
            'examinations' => $examinations,
        ];
    }
}
