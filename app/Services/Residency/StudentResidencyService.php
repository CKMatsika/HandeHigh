<?php

namespace App\Services\Residency;

use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class StudentResidencyService
{
    /**
     * Determine the effective residency of a student for a given academic context.
     * Returns 'boarding' or 'day'.
     */
    public function getEffectiveResidency(Student $student, ?string $academicYear = null, ?string $term = null): string
    {
        return $student->getEffectiveResidency($academicYear, $term);
    }

    /**
     * Check if a student is officially a boarder for the given academic year and term.
     */
    public function isBoarder(Student $student, ?string $academicYear = null, ?string $term = null): bool
    {
        return $this->getEffectiveResidency($student, $academicYear, $term) === 'boarding';
    }

    /**
     * Transition a student's residency between Day Scholar and Boarder.
     *
     * @param Student $student
     * @param string $targetResidency 'boarding' or 'day'
     * @param string $academicYear
     * @param string $term
     * @param string|null $reason
     * @return Enrollment
     */
    public function transitionResidency(
        Student $student,
        string $targetResidency,
        string $academicYear,
        string $term,
        ?string $reason = null
    ): Enrollment {
        if (! in_array($targetResidency, ['boarding', 'day'])) {
            throw new InvalidArgumentException("Invalid residency type: {$targetResidency}. Must be 'boarding' or 'day'.");
        }

        return DB::transaction(function () use ($student, $targetResidency, $academicYear, $term, $reason) {
            $isBoarding = ($targetResidency === 'boarding');

            // Find or create the relevant enrollment for this academic context
            $enrollment = Enrollment::firstOrNew([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'academic_year' => $academicYear,
                'term' => $term,
            ]);

            if (! $enrollment->exists) {
                $enrollment->grade = $student->grade ?? 'Form 1';
                $enrollment->class_name = $student->class_name;
                $enrollment->enrollment_date = now()->toDateString();
                $enrollment->status = 'active';
            }

            $previousResidency = $enrollment->student_type ?? ($enrollment->is_boarding ? 'boarding' : 'day');
            
            $enrollment->student_type = $targetResidency;
            $enrollment->is_boarding = $isBoarding;
            $enrollment->save();

            // Update student current compatibility flag
            $student->update([
                'is_boarding' => $isBoarding,
            ]);

            // If transitioning from Boarder to Day Scholar, safely release any active bed assignment
            if ($previousResidency === 'boarding' && $targetResidency === 'day') {
                $activeAssignment = $student->currentBedAssignment;
                if ($activeAssignment) {
                    $activeAssignment->update([
                        'is_current' => false,
                        'released_date' => now()->toDateString(),
                        'notes' => $reason ? "Released due to Day Scholar transition: {$reason}" : 'Released due to Day Scholar transition.',
                    ]);
                }
            }

            Log::info("Student [ID: {$student->id}, {$student->full_name}] residency transitioned from '{$previousResidency}' to '{$targetResidency}' for {$academicYear} {$term}.");

            return $enrollment;
        });
    }
}
