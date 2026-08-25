<?php

namespace App\Services\Residency;

use App\Models\Dormitory;
use App\Models\Employee;
use App\Models\Hostel;
use InvalidArgumentException;

class BoardingStaffService
{
    /**
     * Assign a supervisor (Matron / Boarding Master) to a Hostel.
     */
    public function assignHostelSupervisor(Hostel $hostel, Employee $employee): void
    {
        $this->validateBoardingStaff($employee, $hostel->school_id, $hostel->gender, 'hostel');

        $hostel->update([
            'supervisor_id' => $employee->id,
        ]);
    }

    /**
     * Assign a supervisor (Matron / Boarding Master) to a Dormitory.
     */
    public function assignDormitorySupervisor(Dormitory $dormitory, Employee $employee): void
    {
        $this->validateBoardingStaff($employee, $dormitory->school_id, $dormitory->gender, 'dormitory');

        $dormitory->update([
            'supervisor_id' => $employee->id,
        ]);
    }

    /**
     * Multi-factor validation for Boarding Staff assignment.
     */
    protected function validateBoardingStaff(
        Employee $employee,
        int $targetSchoolId,
        ?string $facilityGender,
        string $facilityType
    ): void {
        // 1. Tenant ownership validation
        if ($employee->school_id !== $targetSchoolId) {
            throw new InvalidArgumentException('Cross-tenant employee assignment to boarding facility is prohibited.');
        }

        // 2. Active status validation
        if (! $employee->isActive()) {
            throw new InvalidArgumentException("Employee [{$employee->full_name}] is not active ({$employee->employment_status}).");
        }

        // 3. Non-Teaching Staff requirement (Matrons & Boarding Masters are non-teaching)
        if (! $employee->isNonTeaching()) {
            throw new InvalidArgumentException("Employee [{$employee->full_name}] is categorized as teaching staff. Only non-teaching staff (Matrons, Boarding Masters) can be assigned as residential boarding supervisors.");
        }

        // 4. Gender-Specific Responsibility validation
        if ($facilityGender) {
            $gender = strtolower($facilityGender);
            $position = strtolower($employee->position ?? '');

            if ($gender === 'female') {
                if (str_contains($position, 'boarding master') || str_contains($position, 'hostel master') || str_contains($position, 'dorm master')) {
                    throw new InvalidArgumentException("Role mismatch: A Boarding Master cannot be assigned to a girls' {$facilityType}. Girls' facilities must be supervised by a Matron.");
                }
            } elseif ($gender === 'male') {
                if (str_contains($position, 'matron')) {
                    throw new InvalidArgumentException("Role mismatch: A Matron cannot be assigned to a boys' {$facilityType}. Boys' facilities must be supervised by a Boarding Master.");
                }
            }
        }
    }
}
