<?php

namespace App\Imports;

use App\Models\Enrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Create-only bulk enrolment import. Rows that could refer to an existing or
 * another uploaded student are held for manual review and never written.
 */
class EnrollmentsImport implements ToCollection, WithHeadingRow
{
    protected int $schoolId;

    private array $summary = [
        'new_students_created' => 0,
        'existing_students_skipped' => 0,
        'ambiguous_students_skipped' => 0,
        'invalid_rows' => 0,
        'rows' => [],
    ];

    public function __construct($schoolId)
    {
        $this->schoolId = (int) $schoolId;
    }

    /** Process all rows together so duplicates anywhere in the workbook are seen before writes. */
    public function collection(Collection $rows): void
    {
        $indexedRows = [];
        foreach ($rows as $index => $row) {
            $row = is_array($row) ? $row : $row->toArray();
            if ($this->isBlankRow($row)) {
                continue;
            }
            $row = $this->normalizeRowValues($row);
            $indexedRows[] = ['row_number' => ((int) $index) + 2, 'data' => $row];
        }

        DB::transaction(function () use ($indexedRows): void {
            // Serialize this import against other imports using the same school.
            // The lock is tenant scoped and no other school's student rows are loaded.
            School::whereKey($this->schoolId)->lockForUpdate()->firstOrFail();

            $existingStudents = Student::query()
                ->where('school_id', $this->schoolId)
                ->lockForUpdate()
                ->get(['id', 'school_id', 'admission_number', 'registration_number', 'email', 'first_name', 'last_name', 'date_of_birth']);

            $prepared = [];
            foreach ($indexedRows as $item) {
                $validator = Validator::make($item['data'], $this->rules());
                if ($validator->fails()) {
                    $this->record('invalid_rows', $item['row_number'], 'Invalid row: ' . implode('; ', $validator->errors()->all()));
                    continue;
                }

                $data = $item['data'];
                $prepared[] = $item + [
                    'data' => $data,
                    'stable_ids' => $this->stableIds($data),
                    'duplicate_keys' => $this->duplicateKeys($data),
                ];
            }

            $duplicateRows = $this->findWorkbookDuplicates($prepared);
            foreach ($prepared as $position => $item) {
                if (isset($duplicateRows[$position])) {
                    $this->record('ambiguous_students_skipped', $item['row_number'], $duplicateRows[$position]);
                    continue;
                }

                try {
                    DB::transaction(fn () => $this->createOnlyRow($item['data'], $item['row_number'], $existingStudents, true));
                } catch (ValidationException $exception) {
                    $this->record('invalid_rows', $item['row_number'], 'Invalid row: ' . implode('; ', $exception->validator->errors()->all()));
                }
            }
        });
    }

    /**
     * Kept for existing callers/tests that invoke the importer directly. The
     * spreadsheet workflow uses collection() so it can detect workbook duplicates.
     */
    public function model(array $row): ?Enrollment
    {
        $row = $this->normalizeRowValues($row);
        $validator = Validator::make($row, $this->rules());
        $validator->validate();

        return DB::transaction(function () use ($row): ?Enrollment {
            School::whereKey($this->schoolId)->lockForUpdate()->firstOrFail();
            $existingStudents = Student::query()
                ->where('school_id', $this->schoolId)
                ->lockForUpdate()
                ->get(['id', 'school_id', 'admission_number', 'registration_number', 'email', 'first_name', 'last_name', 'date_of_birth']);

            return $this->createOnlyRow($row, null, $existingStudents, false);
        });
    }

    public function summary(): array
    {
        $summary = $this->summary;
        usort($summary['rows'], fn (array $left, array $right) => ($left['row_number'] ?? PHP_INT_MAX) <=> ($right['row_number'] ?? PHP_INT_MAX));

        return $summary;
    }

    private function createOnlyRow(array $row, ?int $rowNumber, Collection $existingStudents, bool $persistEnrollment): ?Enrollment
    {
        $validator = Validator::make($row, $this->rules());
        $validator->validate();

        $stableIds = $this->stableIds($row);
        $matches = $this->findExistingMatches($row, $stableIds, $existingStudents);
        if ($matches['existing']) {
            if ($rowNumber !== null) {
                $this->record('existing_students_skipped', $rowNumber, $matches['reason']);
            }
            return null;
        }
        if ($matches['ambiguous']) {
            if ($rowNumber !== null) {
                $this->record('ambiguous_students_skipped', $rowNumber, $matches['reason']);
            }
            return null;
        }

        $this->validateClassification($row);

        $nameParts = preg_split('/\s+/', trim($row['student_name']), 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? $nameParts[0];
        $rawClassName = trim($row['class_name']);
        $rawGrade = trim($row['grade'] ?? '');
        $canonicalGrade = $this->canonicalGrade($rawGrade);

        $class = null;
        if ($canonicalGrade !== '' && $rawClassName !== '') {
            $class = SchoolClass::where('school_id', $this->schoolId)
                ->where('name', $rawClassName)
                ->where(function ($query) use ($canonicalGrade, $rawGrade): void {
                    $query->where('grade', $canonicalGrade)
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
                    ['academic_year' => $row['academic_year'] ?? '2026']
                );
            }
        }

        $effectiveGrade = $canonicalGrade !== '' ? $canonicalGrade : ($class?->grade ?? 'Form 1');
        $effectiveClassName = $rawClassName !== '' ? $rawClassName : ($class?->name ?? 'General');
        $category = ($row['student_category'] ?? null) ?: null;
        $funding = ($row['funding_status'] ?? null) ?: ($category ? ($category === 'scholarship' ? 'scholarship' : 'regular') : 'regular');
        $residency = ($row['residency'] ?? null) ?: (in_array($category, ['boarding', 'day'], true) ? $category : null);
        $isBoarding = $residency ? $residency === 'boarding' : strtolower($row['is_boarding'] ?? 'no') === 'yes';
        $hasTransport = strtolower($row['has_transport'] ?? 'no') === 'yes';
        $status = $row['status'] ?? 'active';

        $student = Student::create([
            'school_id' => $this->schoolId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => filled($row['email'] ?? null) ? trim($row['email']) : null,
            'admission_number' => $this->optionalIdentifier($row['admission_number'] ?? null),
            'registration_number' => $this->optionalIdentifier($row['registration_number'] ?? null),
            'phone' => $row['phone'] ?? null,
            'address' => $row['address'] ?? null,
            'date_of_birth' => filled($row['date_of_birth'] ?? null) ? Carbon::parse($row['date_of_birth']) : null,
            'gender' => $row['gender'] ?? 'other',
            'grade' => $effectiveGrade,
            'class_name' => $effectiveClassName,
            'is_boarding' => $isBoarding,
            'funding_status' => $funding,
            'has_transport' => $hasTransport,
            'status' => $status,
        ]);

        if (! empty($row['scholarship_reference'])) {
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
                'academic_year' => $row['scholarship_academic_year'] ?? substr($row['scholarship_starts_on'] ?? '', 0, 4),
                'funding_source' => $row['scholarship_funding_source'] ?? null,
                'recognition_evidence' => $row['scholarship_recognition_evidence'] ?? null,
            ];
            if (! empty($row['scholarship_sponsor_code'])) {
                $awardData['funding_sponsor_id'] = \App\Models\FundingSponsor::where('school_id', $this->schoolId)
                    ->where('code', $row['scholarship_sponsor_code'])
                    ->where('is_active', true)
                    ->firstOrFail()->id;
            }
            app(\App\Services\Finance\ScholarshipService::class)->saveAward($student, $awardData);
        }

        $academicYear = (string) $row['academic_year'];
        $term = (string) $row['term'];
        if (Enrollment::where('school_id', $this->schoolId)
            ->where('student_id', $student->id)
            ->where('academic_year', $academicYear)
            ->where('term', $term)
            ->exists()) {
            // Defensive check: a newly-created student should have no prior enrolment.
            // Throwing rolls back this row (and the encompassing workbook transaction).
            throw ValidationException::withMessages(['enrollment' => 'An enrolment already exists for this student, academic year and term.']);
        }

        $enrollment = new Enrollment([
            'school_id' => $this->schoolId,
            'student_id' => $student->id,
            'class_id' => $class?->id,
            'academic_year' => $academicYear,
            'term' => $term,
            'grade' => $effectiveGrade,
            'class_name' => $effectiveClassName,
            'is_boarding' => $isBoarding,
            'student_type' => $isBoarding ? 'boarding' : 'day',
            'has_transport' => $hasTransport,
            'enrollment_date' => filled($row['enrollment_date'] ?? null) ? Carbon::parse($row['enrollment_date']) : now(),
            'status' => $status,
        ]);
        if ($persistEnrollment) {
            $enrollment->save();
            $existingStudents->push($student->only(['id', 'school_id', 'admission_number', 'registration_number', 'email', 'first_name', 'last_name', 'date_of_birth']));
            $this->record('new_students_created', $rowNumber, 'Student and enrolment created.');
        }

        return $enrollment;
    }

    private function findExistingMatches(array $row, array $stableIds, Collection $students): array
    {
        $idMatches = collect();
        foreach ($students as $student) {
            foreach ($stableIds as $field => $value) {
                if ($this->normalizeIdentifier($student->{$field} ?? null) === $value) {
                    $idMatches->put($student->id, $student);
                }
            }
        }

        if ($idMatches->count() > 1) {
            return ['existing' => false, 'ambiguous' => true, 'reason' => 'Admission and registration identifiers point to different existing students.'];
        }
        if ($idMatches->isNotEmpty()) {
            return ['existing' => true, 'ambiguous' => false, 'reason' => 'Admission number or registration number matches an existing student in this school.'];
        }

        $normalizedEmail = $this->normalizeEmail($row['email'] ?? null);
        $emailMatches = $normalizedEmail === null ? collect() : $students->filter(fn ($student) => $this->normalizeEmail($student->email) === $normalizedEmail)->values();
        if ($emailMatches->count() > 1) {
            return ['existing' => false, 'ambiguous' => true, 'reason' => 'Email is shared by multiple existing students in this school.'];
        }

        $normalizedName = $this->normalizeName($row['student_name'] ?? '');
        $normalizedDob = $this->normalizeDate($row['date_of_birth'] ?? null);
        $nameMatches = $students->filter(fn ($student) => $this->normalizeName(trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''))) === $normalizedName)->values();

        if ($emailMatches->isNotEmpty()) {
            $student = $emailMatches->first();
            if ($this->normalizeName(trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''))) === $normalizedName
                && $normalizedDob !== null && $this->normalizeDate($student->date_of_birth) === $normalizedDob) {
                return ['existing' => true, 'ambiguous' => false, 'reason' => 'Email, name and date of birth match an existing student.'];
            }
            return ['existing' => false, 'ambiguous' => true, 'reason' => 'Email matches an existing student but the other identity details do not confirm the match.'];
        }

        if ($nameMatches->isNotEmpty()) {
            if ($normalizedDob !== null && $nameMatches->contains(fn ($student) => $this->normalizeDate($student->date_of_birth) === $normalizedDob)) {
                return ['existing' => true, 'ambiguous' => false, 'reason' => 'Name and date of birth match an existing student.'];
            }

            // Same names without a confirmed stable-ID match are held, including
            // differing or missing dates of birth, for human review.
            if ($stableIds === []) {
                return ['existing' => false, 'ambiguous' => true, 'reason' => 'Name matches an existing student but no supplied stable identifier confirms a distinct identity.'];
            }
        }

        return ['existing' => false, 'ambiguous' => false, 'reason' => ''];
    }

    private function findWorkbookDuplicates(array $rows): array
    {
        $owners = [];
        $duplicates = [];
        foreach ($rows as $position => $item) {
            foreach ($item['duplicate_keys'] as $key) {
                if (isset($owners[$key])) {
                    $duplicates[$position] = 'Possible duplicate of Excel row ' . $rows[$owners[$key]]['row_number'] . '; all matching rows were skipped for manual review.';
                    $duplicates[$owners[$key]] = 'Possible duplicate of Excel row ' . $item['row_number'] . '; all matching rows were skipped for manual review.';
                } else {
                    $owners[$key] = $position;
                }
            }
        }

        return $duplicates;
    }

    private function duplicateKeys(array $row): array
    {
        $keys = [];
        foreach ($this->stableIds($row) as $field => $value) {
            $keys[] = $field . ':' . $value;
        }
        if (($email = $this->normalizeEmail($row['email'] ?? null)) !== null) {
            $keys[] = 'email:' . $email;
        }
        if (($name = $this->normalizeName($row['student_name'] ?? '')) !== '') {
            // A repeated name is intentionally conservative: email differences
            // do not make two rows safe to treat as separate people.
            $keys[] = 'name:' . $name;
        }

        return array_values(array_unique($keys));
    }

    private function stableIds(array $row): array
    {
        $ids = [];
        foreach (['admission_number', 'registration_number'] as $field) {
            $value = $this->normalizeIdentifier($row[$field] ?? null);
            if ($value !== null) {
                $ids[$field] = $value;
            }
        }

        return $ids;
    }

    private function validateClassification(array $row): void
    {
        $category = ($row['student_category'] ?? null) ?: null;
        $funding = ($row['funding_status'] ?? null) ?: ($category ? ($category === 'scholarship' ? 'scholarship' : 'regular') : null);
        $residency = ($row['residency'] ?? null) ?: (in_array($category, ['boarding', 'day'], true) ? $category : null);

        if ($funding === 'scholarship' && ! $residency && ! in_array($row['is_boarding'] ?? null, ['yes', 'no'], true)) {
            throw ValidationException::withMessages(['residency' => 'Scholarship imports require boarding/day residency or an explicit is_boarding yes/no.']);
        }
        if ($residency && isset($row['is_boarding']) && $row['is_boarding'] !== '' && (($row['is_boarding'] === 'yes') !== ($residency === 'boarding'))) {
            throw ValidationException::withMessages(['residency' => 'Residency conflicts with is_boarding.']);
        }
        if (in_array($category, ['boarding', 'day'], true) && $residency && $category !== $residency) {
            throw ValidationException::withMessages(['residency' => 'Residency conflicts with student category.']);
        }
        if ($category && $funding && (($category === 'scholarship') !== ($funding === 'scholarship'))) {
            throw ValidationException::withMessages(['funding_status' => 'Funding status conflicts with student category.']);
        }
    }

    private function canonicalGrade(string $grade): string
    {
        return match (strtolower($grade)) {
            '1', 'form 1', 'form1', 'grade 1' => 'Form 1',
            '2', 'form 2', 'form2', 'grade 2' => 'Form 2',
            '3', 'form 3', 'form3', 'grade 3' => 'Form 3',
            '4', 'form 4', 'form4', 'grade 4' => 'Form 4',
            '5', 'form 5', 'form5', 'grade 5' => 'Form 5',
            '6', 'form 6', 'form6', 'grade 6' => 'Form 6',
            default => $grade,
        };
    }

    private function normalizeIdentifier(mixed $value): ?string
    {
        if (! is_scalar($value) || trim((string) $value) === '') {
            return null;
        }
        $value = trim((string) $value);
        if (preg_match('/^\d+\.0+$/', $value)) {
            $value = explode('.', $value)[0];
        }

        return mb_strtolower($value);
    }

    private function optionalIdentifier(mixed $value): ?string
    {
        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
    }

    private function normalizeEmail(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? mb_strtolower(trim($value)) : null;
    }

    private function normalizeName(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^\pL\pN]+/u', ' ', $value) ?? $value;
        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeRowValues(array $row): array
    {
        // Excel stores numeric-looking years/grades as numbers; normalize these
        // back to strings before applying the unchanged string validation rules.
        foreach (['student_name', 'email', 'admission_number', 'registration_number', 'class_name', 'grade', 'academic_year', 'term', 'status', 'gender', 'phone', 'address'] as $field) {
            if (isset($row[$field]) && is_scalar($row[$field])) {
                $row[$field] = trim((string) $row[$field]);
            }
        }

        return $row;
    }

    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function record(string $counter, ?int $rowNumber, string $reason): void
    {
        $this->summary[$counter]++;
        $this->summary['rows'][] = [
            'row_number' => $rowNumber,
            'outcome' => $counter,
            'reason' => $reason,
        ];
    }

    public function rules(): array
    {
        return [
            'student_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'admission_number' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
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

    public function customValidationMessages(): array
    {
        return [
            'class_name.exists' => 'The selected class does not exist in your school.',
            'student_name.required' => 'Student name is required for each record.',
        ];
    }
}
