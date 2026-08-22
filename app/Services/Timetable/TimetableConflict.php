<?php

namespace App\Services\Timetable;

use JsonSerializable;

class TimetableConflict implements JsonSerializable
{
    public const SEVERITY_HARD = 'HARD';
    public const SEVERITY_SOFT = 'SOFT';
    public const SEVERITY_WARNING = 'WARNING';

    public function __construct(
        public string $type,
        public string $severity,
        public string $message,
        public array $details = [],
        public ?int $teacherId = null,
        public array $classIds = [],
        public ?int $roomId = null,
        public ?int $periodId = null,
        public ?int $slotId = null,
        public ?string $dayOfWeek = null,
        public ?string $startTime = null,
        public ?string $endTime = null
    ) {
    }

    public static function create(
        string $type,
        string $severity,
        string $message,
        array $details = [],
        ?int $teacherId = null,
        array $classIds = [],
        ?int $roomId = null,
        ?int $periodId = null,
        ?int $slotId = null,
        ?string $dayOfWeek = null,
        ?string $startTime = null,
        ?string $endTime = null
    ): self {
        return new self(
            $type,
            $severity,
            $message,
            $details,
            $teacherId,
            $classIds,
            $roomId,
            $periodId,
            $slotId,
            $dayOfWeek,
            $startTime,
            $endTime
        );
    }

    public function isHard(): bool
    {
        return $this->severity === self::SEVERITY_HARD;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'severity' => $this->severity,
            'message' => $this->message,
            'details' => $this->details,
            'teacher_id' => $this->teacherId,
            'class_ids' => $this->classIds,
            'room_id' => $this->roomId,
            'period_id' => $this->periodId,
            'slot_id' => $this->slotId,
            'day_of_week' => $this->dayOfWeek,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
