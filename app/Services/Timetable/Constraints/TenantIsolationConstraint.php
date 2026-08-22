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

        foreach ($slots as $slot) {
            // Check SchoolClass
            if ($slot->school_class_id) {
                $class = SchoolClass::find($slot->school_class_id);
                if ($class && $class->school_id !== $schoolId) {
                    $conflicts[] = TimetableConflict::create(
                        type: 'TENANT_MISMATCH',
                        severity: TimetableConflict::SEVERITY_HARD,
                        message: "Class #{$slot->school_class_id} belongs to a different school.",
                        details: ['entity' => 'class', 'entity_id' => $slot->school_class_id, 'expected_school_id' => $schoolId, 'actual_school_id' => $class->school_id],
                        classIds: [$slot->school_class_id],
                        slotId: $slot->id
                    );
                }
            }

            // Check Subject
            if ($slot->subject_id) {
                $subject = Subject::find($slot->subject_id);
                if ($subject && $subject->school_id !== $schoolId) {
                    $conflicts[] = TimetableConflict::create(
                        type: 'TENANT_MISMATCH',
                        severity: TimetableConflict::SEVERITY_HARD,
                        message: "Subject #{$slot->subject_id} belongs to a different school.",
                        details: ['entity' => 'subject', 'entity_id' => $slot->subject_id, 'expected_school_id' => $schoolId, 'actual_school_id' => $subject->school_id],
                        slotId: $slot->id
                    );
                }
            }

            // Check Teacher
            if ($slot->teacher_id) {
                $teacher = Teacher::find($slot->teacher_id);
                if ($teacher && $teacher->school_id !== $schoolId) {
                    $conflicts[] = TimetableConflict::create(
                        type: 'TENANT_MISMATCH',
                        severity: TimetableConflict::SEVERITY_HARD,
                        message: "Teacher #{$slot->teacher_id} belongs to a different school.",
                        details: ['entity' => 'teacher', 'entity_id' => $slot->teacher_id, 'expected_school_id' => $schoolId, 'actual_school_id' => $teacher->school_id],
                        teacherId: $slot->teacher_id,
                        slotId: $slot->id
                    );
                }
            }

            // Check Room
            if ($slot->room_id) {
                $room = Room::find($slot->room_id);
                if ($room && $room->school_id !== $schoolId) {
                    $conflicts[] = TimetableConflict::create(
                        type: 'TENANT_MISMATCH',
                        severity: TimetableConflict::SEVERITY_HARD,
                        message: "Room #{$slot->room_id} belongs to a different school.",
                        details: ['entity' => 'room', 'entity_id' => $slot->room_id, 'expected_school_id' => $schoolId, 'actual_school_id' => $room->school_id],
                        roomId: $slot->room_id,
                        slotId: $slot->id
                    );
                }
            }

            // Check SchoolPeriod
            if ($slot->school_period_id) {
                $period = SchoolPeriod::find($slot->school_period_id);
                if ($period && $period->school_id !== $schoolId) {
                    $conflicts[] = TimetableConflict::create(
                        type: 'TENANT_MISMATCH',
                        severity: TimetableConflict::SEVERITY_HARD,
                        message: "Period #{$slot->school_period_id} belongs to a different school.",
                        details: ['entity' => 'period', 'entity_id' => $slot->school_period_id, 'expected_school_id' => $schoolId, 'actual_school_id' => $period->school_id],
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
