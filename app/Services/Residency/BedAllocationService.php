<?php

namespace App\Services\Residency;

use App\Models\Bed;
use App\Models\BedAssignment;
use App\Models\Dormitory;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BedAllocationService
{
    protected StudentResidencyService $residencyService;

    public function __construct(StudentResidencyService $residencyService)
    {
        $this->residencyService = $residencyService;
    }

    /**
     * Allocate a bed to a student with concurrency protection and multi-factor validation.
     */
    public function allocateBed(
        Student $student,
        Bed $bed,
        string $academicYear,
        string $term,
        ?string $notes = null
    ): BedAssignment {
        return DB::transaction(function () use ($student, $bed, $academicYear, $term, $notes) {
            // Lock bed and dormitory for update to avoid race conditions
            $bed = Bed::where('id', $bed->id)->lockForUpdate()->firstOrFail();
            $dormitory = Dormitory::where('id', $bed->dormitory_id)->lockForUpdate()->firstOrFail();

            // 1. Tenant ownership validation
            if ($student->school_id !== $dormitory->school_id) {
                throw new InvalidArgumentException('Cross-tenant bed allocation is strictly prohibited.');
            }

            // 2. Day Scholar Protection - Student must be an official BOARDER for this year/term
            if (! $this->residencyService->isBoarder($student, $academicYear, $term)) {
                throw new InvalidArgumentException("Cannot allocate bed: Student [{$student->full_name}] is registered as a Day Scholar for {$academicYear} {$term}. An official residency transition to Boarder is required.");
            }

            // 3. Gender matching validation
            if ($dormitory->gender && $student->gender) {
                $dormGender = strtolower($dormitory->gender);
                $studentGender = strtolower($student->gender);
                if ($dormGender !== 'mixed' && $dormGender !== $studentGender) {
                    throw new InvalidArgumentException("Gender mismatch: Cannot assign {$studentGender} student to {$dormGender} dormitory [{$dormitory->name}].");
                }
            }

            // Also check parent hostel gender if attached
            if ($dormitory->hostel && $dormitory->hostel->gender && $student->gender) {
                $hostelGender = strtolower($dormitory->hostel->gender);
                $studentGender = strtolower($student->gender);
                if ($hostelGender !== 'mixed' && $hostelGender !== $studentGender) {
                    throw new InvalidArgumentException("Hostel gender mismatch: Cannot assign {$studentGender} student to {$hostelGender} hostel [{$dormitory->hostel->name}].");
                }
            }

            // 4. Bed availability and concurrency check
            $activeBedOccupant = BedAssignment::where('bed_id', $bed->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            if ($activeBedOccupant && $activeBedOccupant->student_id !== $student->id) {
                throw new InvalidArgumentException("Bed [{$bed->bed_number}] is already occupied by student ID #{$activeBedOccupant->student_id}.");
            }

            // 5. Release any other current bed assignments for this student
            BedAssignment::where('student_id', $student->id)
                ->where('is_current', true)
                ->update([
                    'is_current' => false,
                    'released_date' => now()->toDateString(),
                    'notes' => 'Superseded by new bed assignment.',
                ]);

            // 6. Create new active bed assignment
            $assignment = BedAssignment::create([
                'school_id' => $student->school_id,
                'bed_id' => $bed->id,
                'student_id' => $student->id,
                'academic_year' => $academicYear,
                'term' => $term,
                'assigned_date' => now()->toDateString(),
                'released_date' => null,
                'is_current' => true,
                'notes' => $notes,
            ]);

            // Ensure bed is marked available in status
            $bed->update(['is_available' => true]);

            // Ensure student is marked as boarding
            $student->update(['is_boarding' => true]);

            return $assignment;
        });
    }

    /**
     * Release a student's active bed assignment.
     */
    public function releaseBed(Student $student, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($student, $notes) {
            $activeAssignment = BedAssignment::where('student_id', $student->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            if (! $activeAssignment) {
                return false;
            }

            $activeAssignment->update([
                'is_current' => false,
                'released_date' => now()->toDateString(),
                'notes' => $notes ?? 'Bed released by administrator.',
            ]);

            return true;
        });
    }

    /**
     * Get all available beds in a dormitory.
     */
    public function getAvailableBeds(Dormitory $dormitory): Collection
    {
        return $dormitory->beds()
            ->where('is_available', true)
            ->whereDoesntHave('assignments', fn ($q) => $q->where('is_current', true))
            ->get();
    }
}
