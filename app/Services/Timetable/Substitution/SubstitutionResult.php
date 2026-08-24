<?php

namespace App\Services\Timetable\Substitution;

use App\Models\Teacher;

class SubstitutionResult
{
    public function __construct(
        public Teacher $teacher,
        public float $score,
        public array $breakdown,
        public array $highlights,
        public array $warnings = [],
        public bool $isEligible = true
    ) {}

    public function toArray(): array
    {
        return [
            'teacher_id' => $this->teacher->id,
            'teacher_name' => $this->teacher->full_name,
            'email' => $this->teacher->email,
            'score' => $this->score,
            'breakdown' => $this->breakdown,
            'highlights' => $this->highlights,
            'warnings' => $this->warnings,
            'is_eligible' => $this->isEligible,
        ];
    }
}
