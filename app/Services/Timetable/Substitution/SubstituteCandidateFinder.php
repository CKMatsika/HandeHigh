<?php

namespace App\Services\Timetable\Substitution;

use App\Models\Teacher;
use Illuminate\Support\Collection;

class SubstituteCandidateFinder
{
    /**
     * Find all active teachers in the given school except the original teacher.
     */
    public function findCandidates(int $schoolId, int $excludeTeacherId): Collection
    {
        return Teacher::where('school_id', $schoolId)
            ->where('id', '!=', $excludeTeacherId)
            ->where('status', true)
            ->with(['subjects', 'classes'])
            ->get();
    }
}
