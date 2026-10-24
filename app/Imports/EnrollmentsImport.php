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
        return \Illuminate\Support\Facades\DB::transaction(fn () => $this->importRow($row));
    }

    private function importRow(array $row)
    {
        // Validate direct callers as well as spreadsheet imports before writing anything.
        \Illuminate\Support\Facades\Validator::make($row, $this->rules())->validate();
        $category = ($row['student_category'] ?? null) ?: null;
        $funding = ($row['funding_status'] ?? null) ?: ($category ? ($category === 'scholarship' ? 'scholarship' : 'regular') : null);
        $residency = ($row['residency'] ?? null) ?: (in_array($category, ['boarding', 'day'], true) ? $category : null);
        if ($funding === 'scholarship' && !$residency && !in_array($row['is_boarding'] ?? null, ['yes', 'no'], true)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['residency' => 'Scholarship imports require boarding/day residency or an explicit is_boarding yes/no.']);
        }
        if ($residency && isset($row['is_boarding']) && $row['is_boarding'] !== '' && ($row['is_boarding'] === 'yes') !== ($residency === 'boarding')) {
            throw \Illuminate\Validation\ValidationException::withMessages(['residency' => 'Residency conflicts with is_boarding.']);
        }
        if (in_array($category, ['boarding','day'], true) && $residency && $category !== $residency) {
            throw \Illuminate\Validation\ValidationException::withMessages(['residency' => 'Residency conflicts with student category.']);
        }
        if ($category && $funding && (($category === 'scholarship') !== ($funding === 'scholarship'))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['funding_status' => 'Funding status conflicts with student category.']);
        }
        $name = preg_split('/\s+/', trim($row['student_name']), 2);
        $firstName = $name[0];
        $lastName = $name[1] ?? $name[0];

        $rawClassName = trim($row['class_name'] ?? '');
        $rawGrade = trim($row['grade'] ?? '');

        // Standardize grade representation
        $canonicalGrade = match(strtolower($rawGrade)) {
            '1', 'form 1', 'form1', 'grade 1' => 'Form 1',
            '2', 'form 2', 'form2', 'grade 2' => 'Form 2',
            '3', 'form 3', 'form3', 'grade 3' => 'Form 3',
            '4', 'form 4', 'form4', 'grade 4' => 'Form 4',
            '5', 'form 5', 'form5', 'grade 5' => 'Form 5',
            '6', 'form 6', 'form6', 'grade 6' => 'Form 6',
            default => $rawGrade,
        };

        // Resolve class with strict grade and stream awareness
        $class = null;
        if ($canonicalGrade !== '' && $rawClassName !== '') {
            $class = SchoolClass::where('school_id', $this->schoolId)
                ->where('name', $rawClassName)
                ->where(function ($q) use ($canonicalGrade, $rawGrade) {
                    $q->where('grade', $canonicalGrade)
                      ->orWhere('grade', $rawGrade)
                      ->orWhere('grade', 'like', "%{$canonicalGrade}%");
                })
                ->first();

            if (! $class) {
                $class = SchoolClass::firstOrCreate(
                    [
                        'school_id' => $this->schoolId,
                        'name' => $rawClassName,
                        'grade' => $canonicalGrade,
                    ],
                    [
                        'academic_year' => $row['academic_year'] ?? '2026',
                    ]
                );
            }
        }

        $effectiveGrade = ! empty($canonicalGrade) ? $canonicalGrade : ($class?->grade ?? 'Form 1');
        $effectiveClassName = ! empty($rawClassName) ? $rawClassName : ($class?->name ?? 'General');
        $isBoarding = $residency ? $residency === 'boarding' : strtolower($row['is_boarding'] ?? 'no') === 'yes';
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
                'funding_status' => $funding ?? 'regular',
                'has_transport' => $hasTransport,
                'status' => $status,
                'school_id' => $this->schoolId,
            ]
        );

        // Always update student academic details so they are synced even if student existed
        if (!$isBoarding && $student->currentBedAssignment()->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['residency' => 'Release the current bed before importing a Day residency change.']);
        }
        if ($funding === 'regular' && $student->scholarships()->where('status', 'approved')->whereDate('ends_on', '>=', now()->toDateString())->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['funding_status' => 'Close current approved awards before importing regular funding status.']);
        }
        $student->update([
            'grade' => $effectiveGrade,
            'class_name' => $effectiveClassName,
            'is_boarding' => $isBoarding,
            'has_transport' => $hasTransport,
            'status' => $status,
            'funding_status' => $funding ?? $student->funding_status ?? 'regular',
        ]);

        if (!empty($row['scholarship_reference'])) {
            if ($funding !== 'scholarship') {
                throw \Illuminate\Validation\ValidationException::withMessages(['funding_status' => 'Award details require scholarship funding status.']);
            }
            $awardData = [
                'reference' => $row['scholarship_reference'], 'name' => $row['scholarship_name'] ?? '',
                'sponsor' => $row['scholarship_sponsor'] ?? null,
                'coverage_type' => $row['scholarship_coverage_type'] ?? 'partial',
                'coverage_basis' => $row['scholarship_coverage_basis'] ?? 'percentage',
                'coverage_value' => $row['scholarship_coverage_value'] ?? null,
                'covered_votes' => array_filter(array_map('trim', explode('|', $row['scholarship_covered_votes'] ?? ''))),
                'starts_on' => $row['scholarship_starts_on'] ?? null, 'ends_on' => $row['scholarship_ends_on'] ?? null,
                'accounting_treatment' => $row['scholarship_accounting_treatment'] ?? 'unconfigured',
                'supporting_reference' => $row['scholarship_supporting_reference'] ?? null,
                'academic_year'=>$row['scholarship_academic_year']??substr($row['scholarship_starts_on']??'',0,4),
                'funding_source'=>$row['scholarship_funding_source']??null,
                'recognition_evidence'=>$row['scholarship_recognition_evidence']??null,
            ];
            if(!empty($row['scholarship_sponsor_code'])){$awardData['funding_sponsor_id']=\App\Models\FundingSponsor::where('school_id',$this->schoolId)->where('code',$row['scholarship_sponsor_code'])->where('is_active',true)->firstOrFail()->id;}
            // Never change or auto-approve an existing award through an enrollment file.
            app(\App\Services\Finance\ScholarshipService::class)->saveAward($student, $awardData);
        }

        return new Enrollment([
            'school_id' => $this->schoolId,
            'student_id' => $student->id,
            'class_id' => $class?->id,
            'academic_year' => $row['academic_year'],
            'term' => $row['term'],
            'grade' => $effectiveGrade,
            'class_name' => $effectiveClassName,
            'is_boarding' => $isBoarding,
            'student_type' => $isBoarding ? 'boarding' : 'day',
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
            'student_category' => 'nullable|in:boarding,day,scholarship',
            'funding_status' => 'nullable|in:regular,scholarship',
            'residency' => 'nullable|in:boarding,day',
            'scholarship_reference' => 'nullable|string|max:100',
            'scholarship_name' => 'required_with:scholarship_reference|nullable|string|max:255',
            'scholarship_coverage_value' => 'required_with:scholarship_reference|nullable|numeric|gt:0',
            'scholarship_covered_votes' => 'required_with:scholarship_reference|nullable|string',
            'scholarship_starts_on' => 'required_with:scholarship_reference|nullable|date',
            'scholarship_ends_on' => 'required_with:scholarship_reference|nullable|date|after_or_equal:scholarship_starts_on',
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
