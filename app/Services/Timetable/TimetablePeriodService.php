<?php

namespace App\Services\Timetable;

use App\Models\School;
use App\Models\SchoolPeriod;
use Illuminate\Validation\ValidationException;

class TimetablePeriodService
{
    /**
     * Create a new school period after validating constraints.
     */
    public function createPeriod(School $school, array $data): SchoolPeriod
    {
        $this->validatePeriodData($school, $data);

        return $school->schoolPeriods()->create($data);
    }

    /**
     * Update an existing period after validating constraints.
     */
    public function updatePeriod(SchoolPeriod $period, array $data): SchoolPeriod
    {
        $this->validatePeriodData($period->school, $data, $period->id);

        $period->update($data);

        return $period->fresh();
    }

    /**
     * Validate sequence, start/end times, and overlapping periods.
     */
    public function validatePeriodData(School $school, array $data, ?int $ignorePeriodId = null): void
    {
        $startTime = substr((string) ($data['start_time'] ?? ''), 0, 5);
        $endTime = substr((string) ($data['end_time'] ?? ''), 0, 5);
        $dayOfWeek = $data['day_of_week'] ?? null;
        $sequence = (int) ($data['period_sequence'] ?? 1);

        if ($startTime >= $endTime) {
            throw ValidationException::withMessages([
                'end_time' => ['The period end time must be after the start time.'],
            ]);
        }

        // Check duplicate sequence for the day
        $seqQuery = SchoolPeriod::where('school_id', $school->id)
            ->where('period_sequence', $sequence)
            ->when($dayOfWeek, fn ($q) => $q->where(function ($sq) use ($dayOfWeek) {
                $sq->where('day_of_week', $dayOfWeek)->orWhereNull('day_of_week');
            }))
            ->when($ignorePeriodId, fn ($q) => $q->where('id', '!=', $ignorePeriodId));

        if ($seqQuery->exists()) {
            throw ValidationException::withMessages([
                'period_sequence' => ["Period sequence {$sequence} is already in use for " . ($dayOfWeek ?? 'all days') . '.'],
            ]);
        }

        // Check time overlap with active periods on the same day
        $overlapQuery = SchoolPeriod::where('school_id', $school->id)
            ->where('is_active', true)
            ->when($dayOfWeek, fn ($q) => $q->where(function ($sq) use ($dayOfWeek) {
                $sq->where('day_of_week', $dayOfWeek)->orWhereNull('day_of_week');
            }))
            ->when($ignorePeriodId, fn ($q) => $q->where('id', '!=', $ignorePeriodId))
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            });

        if ($overlapQuery->exists()) {
            $overlapping = $overlapQuery->first();
            throw ValidationException::withMessages([
                'start_time' => ["This time interval ({$startTime} - {$endTime}) overlaps with existing period '{$overlapping->name}' ({$overlapping->getFormattedTime()})."],
            ]);
        }
    }

    /**
     * Seed standard default periods for a school.
     */
    public function seedDefaultPeriods(School $school): void
    {
        if ($school->schoolPeriods()->exists()) {
            return;
        }

        $defaults = [
            ['name' => 'Assembly / Registration', 'period_sequence' => 1, 'start_time' => '07:45', 'end_time' => '08:15', 'period_type' => 'assembly'],
            ['name' => 'Period 1', 'period_sequence' => 2, 'start_time' => '08:15', 'end_time' => '09:00', 'period_type' => 'lesson'],
            ['name' => 'Period 2', 'period_sequence' => 3, 'start_time' => '09:00', 'end_time' => '09:45', 'period_type' => 'lesson'],
            ['name' => 'Period 3', 'period_sequence' => 4, 'start_time' => '09:45', 'end_time' => '10:30', 'period_type' => 'lesson'],
            ['name' => 'Morning Break', 'period_sequence' => 5, 'start_time' => '10:30', 'end_time' => '11:00', 'period_type' => 'break'],
            ['name' => 'Period 4', 'period_sequence' => 6, 'start_time' => '11:00', 'end_time' => '11:45', 'period_type' => 'lesson'],
            ['name' => 'Period 5', 'period_sequence' => 7, 'start_time' => '11:45', 'end_time' => '12:30', 'period_type' => 'lesson'],
            ['name' => 'Lunch Break', 'period_sequence' => 8, 'start_time' => '12:30', 'end_time' => '13:30', 'period_type' => 'lunch'],
            ['name' => 'Period 6', 'period_sequence' => 9, 'start_time' => '13:30', 'end_time' => '14:15', 'period_type' => 'lesson'],
            ['name' => 'Period 7', 'period_sequence' => 10, 'start_time' => '14:15', 'end_time' => '15:00', 'period_type' => 'lesson'],
            ['name' => 'Sport / Clubs', 'period_sequence' => 11, 'start_time' => '15:00', 'end_time' => '16:30', 'period_type' => 'sport'],
        ];

        foreach ($defaults as $period) {
            $school->schoolPeriods()->create(array_merge($period, ['is_active' => true]));
        }
    }
}
