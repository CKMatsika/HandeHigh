<?php

namespace App\Services\Timetable\Contracts;

use App\Models\Timetable;
use Illuminate\Support\Collection;

interface TimetableConstraintInterface
{
    /**
     * Evaluate the constraint against the timetable and its slots.
     *
     * @param  Timetable  $timetable
     * @param  Collection  $slots
     * @param  array  $context  Additional context (fixed activities, exams, periods, curriculum)
     * @return array Array of TimetableConflict objects
     */
    public function evaluate(Timetable $timetable, Collection $slots, array $context = []): array;

    /**
     * Constraint unique identifier code.
     */
    public function getCode(): string;

    /**
     * Severity: 'HARD', 'SOFT', or 'WARNING'.
     */
    public function getSeverity(): string;

    /**
     * Weight for scoring (used in Phase 3D optimization).
     */
    public function getWeight(): int;

    /**
     * Human readable name of the constraint.
     */
    public function getName(): string;
}
