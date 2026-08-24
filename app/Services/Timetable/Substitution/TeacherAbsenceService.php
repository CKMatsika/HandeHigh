<?php

namespace App\Services\Timetable\Substitution;

use App\Events\Timetable\TeacherAbsenceRecorded;
use App\Models\SchoolPeriod;
use App\Models\Teacher;
use App\Models\TeacherAbsence;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class TeacherAbsenceService
{
    /**
     * Record a new teacher absence and identify affected timetable slots.
     */
    public function recordAbsence(
        int $schoolId,
        int $teacherId,
        string|Carbon $startDate,
        string|Carbon $endDate,
        string $reason,
        ?array $affectedPeriodIds = null,
        ?string $notes = null,
        ?User $recorder = null
    ): array {
        $teacher = Teacher::where('id', $teacherId)->where('school_id', $schoolId)->first();
        if (! $teacher) {
            throw new InvalidArgumentException("Teacher #{$teacherId} not found in this school.");
        }

        $start = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $end = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        if ($end->lt($start)) {
            throw new InvalidArgumentException("End date cannot be earlier than start date.");
        }

        $absence = TeacherAbsence::create([
            'school_id' => $schoolId,
            'teacher_id' => $teacher->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'affected_period_ids' => $affectedPeriodIds,
            'reason' => $reason,
            'notes' => $notes,
            'status' => 'active',
            'recorded_by' => $recorder?->id,
        ]);

        $affectedSlots = $this->findAffectedSlots($absence);

        event(new TeacherAbsenceRecorded($absence, $affectedSlots, $recorder));

        return [
            'absence' => $absence,
            'affected_slots' => $affectedSlots,
        ];
    }

    /**
     * Identify all timetable lesson slots affected by a teacher absence.
     */
    public function findAffectedSlots(TeacherAbsence $absence, ?Timetable $timetable = null): Collection
    {
        $targetTimetable = $timetable ?? Timetable::where('school_id', $absence->school_id)
            ->where(function ($q) {
                $q->where('status', 'published')->orWhere('is_operational', true);
            })
            ->latest('published_at')
            ->first();

        if (! $targetTimetable) {
            return collect();
        }

        $start = Carbon::parse($absence->start_date);
        $end = Carbon::parse($absence->end_date);
        $period = CarbonPeriod::create($start, $end);

        // Collect days of week present in the absence date range
        $datesByDayOfWeek = [];
        foreach ($period as $date) {
            $dayName = $date->format('l');
            $datesByDayOfWeek[$dayName][] = $date->toDateString();
        }

        $days = array_keys($datesByDayOfWeek);

        $slotsQuery = $targetTimetable->slots()
            ->where('teacher_id', $absence->teacher_id)
            ->where('status', '!=', 'cancelled')
            ->whereIn('day_of_week', $days)
            ->with(['schoolClass', 'subject', 'room', 'schoolPeriod', 'substitutions']);

        if (! empty($absence->affected_period_ids)) {
            $slotsQuery->whereIn('school_period_id', $absence->affected_period_ids);
        }

        $slots = $slotsQuery->get();

        // Expand slots by specific calendar dates in the absence range
        $expandedSlots = collect();
        foreach ($slots as $slot) {
            $matchingDates = $datesByDayOfWeek[$slot->day_of_week] ?? [];
            foreach ($matchingDates as $dateStr) {
                $item = clone $slot;
                $item->occurrence_date = $dateStr;
                $existingSub = $slot->substitutions
                    ->where('date', $dateStr)
                    ->where('status', 'approved')
                    ->first();
                $item->existing_substitution = $existingSub;
                $item->is_covered = ($existingSub !== null);
                $expandedSlots->push($item);
            }
        }

        return $expandedSlots;
    }
}
