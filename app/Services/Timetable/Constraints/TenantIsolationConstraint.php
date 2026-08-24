<?php

namespace App\Services\Timetable\Constraints;

use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\SchoolPeriod;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Services\Timetable\Contracts\TimetableConstraintInterface;
use App\Services\Timetable\TimetableConflict;
use Illuminate\Support\Collection;

class TenantIsolationConstraint implements TimetableConstraintInterface
{
    public function evaluate(Timetable $timetable, Collection $slots, array $context = []): array
    {
        $conflicts = [];
        $schoolId = $timetable->school_id;
        $cache = [];

        foreach ($slots as $slot) {
            // Check SchoolClass
            if ($slot->school_class_id) {
                $classSchoolId = $cache['class'][$slot->school_class_id] ?? null;
                if ($classSchoolId === null) {
                    $class = $slot->relationLoaded('schoolClass') ? $slot->schoolClass : SchoolClass::find($slot->school_class_id);
                    $classSchoolId = $class?->school_id ?? false;
                    $cache['class'][$slot->school_class_id] = $classSchoolId;
                }
                if ($classSchoolId && $classSchoolId !== $schoolId) {
                    $conflicts[] = TimetableConflict::create(
                        type: 'TENANT_MISMATCH',
                        severity: TimetableConflict::SEVERITY_HARD,
                        message: "Class #{$slot->school_class_id} belongs to a different school.",
                        details: ['entity' => 'class', 'entity_id' => $slot->school_class_id, 'expected_school_id' => $schoolId, 'actual_school_id' => $classSchoolId],
                        classIds: [$slot->school_class_id],
                        slotId: $slot->id
                    );
                }
            }

            // Check Subject
            if ($slot->subject_id) {
                $subSchoolId = $cache['subject'][$slot->subject_id] ?? null;
                if ($subSchoolId === null) {
                    $subject = $slot->relationLoaded('subject') ? $slot->subject : Subject::find($slot->subject_id);
                    $subSchoolId = $subject?->school_id ?? false;
                    $cache['subject'][$slot->subject_id] = $subSchoolId;
                }
                if ($subSchoolId && $subSchoolId !== $schoolId) {
                    $conflicts[] = TimetableConflict::create(
                        type: 'TENANT_MISMATCH',
                        severity: TimetableConflict::SEVERITY_HARD,
                        message: "Subject #{$slot->subject_id} belongs to a different school.",
                        details: ['entity' => 'subject', 'entity_id' => $slot->subject_id, 'expected_school_id' => $schoolId, 'actual_school_id' => $subSchoolId],
                        slotId: $slot->id
                    );
                }
            }

            // Check Teacher
            if ($slot->teacher_id) {
                $tSchoolId = $cache['teacher'][$slot->teacher_id] ?? null;
                if ($tSchoolId === null) {
                    $teacher = $slot->relationLoaded('teacher') ? $slot->teacher : Teacher::find($slot->teacher_id);
                    $tSchoolId = $teacher?->school_id ?? false;
                    $cache['teacher'][$slot->teacher_id] = $tSchoolId;
                }
                if ($tSchoolId && $tSchoolId !== $schoolId) {
                    $conflicts[] = TimetableConflict::create(
                        type: 'TENANT_MISMATCH',
                        severity: TimetableConflict::SEVERITY_HARD,
                        message: "Teacher #{$slot->teacher_id} belongs to a different school.",
                        details: ['entity' => 'teacher', 'entity_id' => $slot->teacher_id, 'expected_school_id' => $schoolId, 'actual_school_id' => $tSchoolId],
                        teacherId: $slot->teacher_id,
                        slotId: $slot->id
                    );
                }
            }

            // Check Room
            if ($slot->room_id) {
                $roomSchoolId = $cache['room'][$slot->room_id] ?? null;
                if ($roomSchoolId === null) {
                    $room = $slot->relationLoaded('room') ? $slot->room : Room::find($slot->room_id);
                    $roomSchoolId = $room?->school_id ?? false;
                    $cache['room'][$slot->room_id] = $roomSchoolId;
                }
                if ($roomSchoolId && $roomSchoolId !== $schoolId) {
                    $conflicts[] = TimetableConflict::create(
                        type: 'TENANT_MISMATCH',
                        severity: TimetableConflict::SEVERITY_HARD,
                        message: "Room #{$slot->room_id} belongs to a different school.",
                        details: ['entity' => 'room', 'entity_id' => $slot->room_id, 'expected_school_id' => $schoolId, 'actual_school_id' => $roomSchoolId],
                        roomId: $slot->room_id,
                        slotId: $slot->id
                    );
                }
            }

            // Check SchoolPeriod
            if ($slot->school_period_id) {
                $periodSchoolId = $cache['period'][$slot->school_period_id] ?? null;
                if ($periodSchoolId === null) {
                    $period = $slot->relationLoaded('schoolPeriod') ? $slot->schoolPeriod : SchoolPeriod::find($slot->school_period_id);
                    $periodSchoolId = $period?->school_id ?? false;
                    $cache['period'][$slot->school_period_id] = $periodSchoolId;
                }
                if ($periodSchoolId && $periodSchoolId !== $schoolId) {
                    $conflicts[] = TimetableConflict::create(
                        type: 'TENANT_MISMATCH',
                        severity: TimetableConflict::SEVERITY_HARD,
                        message: "Period #{$slot->school_period_id} belongs to a different school.",
                        details: ['entity' => 'period', 'entity_id' => $slot->school_period_id, 'expected_school_id' => $schoolId, 'actual_school_id' => $periodSchoolId],
                        periodId: $slot->school_period_id,
                        slotId: $slot->id
                    );
                }
            }
        }

        return $conflicts;
    }

    public function getCode(): string
    {
        return 'TENANT_MISMATCH';
    }

    public function getSeverity(): string
    {
        return TimetableConflict::SEVERITY_HARD;
    }

    public function getWeight(): int
    {
        return 100;
    }

    public function getName(): string
    {
        return 'Multi-Tenant Boundary Shielding';
    }
}
