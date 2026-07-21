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
        // Find or create student
        $student = Student::firstOrCreate(
            ['email' => $row['email']],
            [
                'name' => $row['student_name'],
                'email' => $row['email'],
                'phone' => $row['phone'] ?? null,
                'address' => $row['address'] ?? null,
                'date_of_birth' => isset($row['date_of_birth']) ? Carbon::parse($row['date_of_birth']) : null,
                'gender' => $row['gender'] ?? 'other',
                'school_id' => $this->schoolId,
            ]
        );

        // Find class
        $class = SchoolClass::where('name', $row['class_name'])
            ->where('school_id', $this->schoolId)
            ->firstOrFail();

        return new Enrollment([
            'school_id' => $this->schoolId,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'academic_year' => $row['academic_year'],
            'term' => $row['term'],
            'grade' => $row['grade'] ?? $row['class_name'],
            'class_name' => $row['class_name'],
            'is_boarding' => strtolower($row['is_boarding'] ?? 'no') === 'yes',
            'has_transport' => strtolower($row['has_transport'] ?? 'no') === 'yes',
            'enrollment_date' => isset($row['enrollment_date']) 
                ? Carbon::parse($row['enrollment_date']) 
                : now(),
            'status' => $row['status'] ?? 'active',
        ]);
    }

    public function rules(): array
    {
        return [
            'student_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'class_name' => [
                'required',
                'string',
                Rule::exists('classes', 'name')->where(function ($query) {
                    $query->where('school_id', $this->schoolId);
                })
            ],
            'academic_year' => 'required|string',
            'term' => 'required|string|in:First Term,Second Term,Third Term',
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
