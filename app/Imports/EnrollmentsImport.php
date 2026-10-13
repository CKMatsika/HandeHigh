<?php

namespace App\Imports;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\SchoolClass;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class EnrollmentsImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $schoolId;

    public function __construct($schoolId)
    {
        $this->schoolId = $schoolId;
    }

    public function model(array $row)
    {
        $name = preg_split('/\s+/', trim($row['student_name']), 2);
        $firstName = $name[0];
        $lastName = $name[1] ?? $name[0];

        $rawClassName = trim($row['class_name'] ?? '');
        $rawGrade = trim($row['grade'] ?? '');

        // Resolve class with grade awareness
        $class = null;
        if ($rawGrade !== '') {
            $class = SchoolClass::where('school_id', $this->schoolId)
                ->where('name', $rawClassName)
                ->where(function ($q) use ($rawGrade) {
                    $q->where('grade', $rawGrade)
                      ->orWhere('grade', 'like', "%{$rawGrade}%");
                })
                ->first();
        }

        if (! $class) {
            $class = SchoolClass::where('school_id', $this->schoolId)
                ->where('name', $rawClassName)
                ->first();
        }

        if (! $class && $rawGrade !== '') {
            $combinedNames = [
                "Form {$rawGrade} {$rawClassName}",
                "Form{$rawGrade} {$rawClassName}",
                "{$rawGrade} {$rawClassName}",
                "Form {$rawGrade}{$rawClassName}",
            ];
            $class = SchoolClass::where('school_id', $this->schoolId)
                ->whereIn('name', $combinedNames)
                ->first();
        }

        $effectiveGrade = ! empty($rawGrade) ? $rawGrade : ($class?->grade ?? 'Form 1');
        $effectiveClassName = ! empty($rawClassName) ? $rawClassName : ($class?->name ?? 'General');
        $isBoarding = strtolower($row['is_boarding'] ?? 'no') === 'yes';
        $hasTransport = strtolower($row['has_transport'] ?? 'no') === 'yes';
        $status = $row['status'] ?? 'active';

        // Match students by email within the authenticated school's tenant.
        $student = Student::firstOrCreate(
            [
                'school_id' => $this->schoolId,
                'email' => $row['email'],
            ],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $row['email'],
                'phone' => $row['phone'] ?? null,
                'address' => $row['address'] ?? null,
                'date_of_birth' => isset($row['date_of_birth']) ? Carbon::parse($row['date_of_birth']) : null,
                'gender' => $row['gender'] ?? 'other',
                'grade' => $effectiveGrade,
                'class_name' => $effectiveClassName,
                'is_boarding' => $isBoarding,
                'has_transport' => $hasTransport,
                'status' => $status,
                'school_id' => $this->schoolId,
            ]
        );

        // Always update student academic details so they are synced even if student existed
        $student->update([
            'grade' => $effectiveGrade,
            'class_name' => $effectiveClassName,
            'is_boarding' => $isBoarding,
            'has_transport' => $hasTransport,
            'status' => $status,
        ]);

        return new Enrollment([
            'school_id' => $this->schoolId,
            'student_id' => $student->id,
            'class_id' => $class?->id,
            'academic_year' => $row['academic_year'],
            'term' => $row['term'],
            'grade' => $effectiveGrade,
            'class_name' => $effectiveClassName,
            'is_boarding' => $isBoarding,
            'has_transport' => $hasTransport,
            'enrollment_date' => isset($row['enrollment_date']) 
                ? Carbon::parse($row['enrollment_date']) 
                : now(),
            'status' => $status,
        ]);
    }

    public function rules(): array
    {
        return [
            'student_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'class_name' => ['required', 'string'],
            'grade' => ['nullable', 'string'],
            'academic_year' => 'required|string',
            'term' => 'required|string|in:First Term,Second Term,Third Term,Term 1,Term 2,Term 3',
            'enrollment_date' => 'nullable|date',
            'status' => 'nullable|in:active,inactive,graduated,transferred',
            'is_boarding' => 'nullable|in:yes,no',
            'has_transport' => 'nullable|in:yes,no',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'class_name.exists' => 'The selected class does not exist in your school.',
            'email.required' => 'Email is required for each student.',
            'student_name.required' => 'Student name is required for each record.',
        ];
    }
}
