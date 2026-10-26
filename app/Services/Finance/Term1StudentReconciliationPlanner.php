<?php

namespace App\Services\Finance;

use InvalidArgumentException;

/** Read-only identity review against a current Term 1 reconciliation snapshot. */
class Term1StudentReconciliationPlanner
{
    public function preview(array $manifest, array $snapshot): array
    {
        if (($manifest['scope'] ?? null) !== 'term1_2026_students' || (int) ($manifest['academic_year'] ?? 0) !== 2026
            || ($snapshot['format'] ?? null) !== 'hande-term1-reconciliation-v1'
            || (int) ($snapshot['school']['id'] ?? 0) !== (int) ($manifest['school_id'] ?? 0)) {
            throw new InvalidArgumentException('Student plan and snapshot must identify the same tenant and Term 1 2026 scope.');
        }
        $students = $snapshot['data']['students'] ?? null;
        if (! is_array($students) || ! is_array($manifest['students'] ?? null)) {
            throw new InvalidArgumentException('Current student snapshot and candidate rows are required.');
        }
        $references = [];
        $admissions = [];
        $rows = [];
        foreach ($manifest['students'] as $row) {
            $errors = [];
            $reference = trim((string) ($row['source_reference'] ?? ''));
            $admission = mb_strtoupper(trim((string) ($row['admission_number'] ?? '')));
            $registration = mb_strtoupper(trim((string) ($row['registration_number'] ?? '')));
            if ($reference === '' || isset($references[$reference])) {
                $errors[] = 'Missing or repeated source reference.';
            }
            $references[$reference] = true;
            if ($admission === '' || empty($row['identity_evidence']) || empty($row['confirmed_absent'])) {
                $errors[] = 'Admission identity, absence confirmation and identity evidence are required.';
            }
            if ($admission !== '' && isset($admissions[$admission])) {
                $errors[] = 'Admission number is repeated in source candidates.';
            }
            if ($admission !== '') {
                $admissions[$admission] = true;
            }
            $admissionMatch = null;
            $registrationMatch = null;
            $nameMatches = [];
            foreach ($students as $existing) {
                if ($admission !== '' && mb_strtoupper(trim((string) ($existing['admission_number'] ?? ''))) === $admission) {
                    $admissionMatch = $existing;
                }
                if ($registration !== '' && mb_strtoupper(trim((string) ($existing['registration_number'] ?? ''))) === $registration) {
                    $registrationMatch = $existing;
                }
                if ($this->nameKey($row) !== '' && $this->nameKey($existing) === $this->nameKey($row)) {
                    $nameMatches[] = $existing;
                }
            }
            if ($admissionMatch) {
                $errors[] = 'Admission number already exists; match and preserve the existing ERP student.';
            }
            if ($registrationMatch) {
                $errors[] = 'Registration number already exists; manually resolve identity before creation.';
            }
            if ($nameMatches) {
                $errors[] = 'Same-name ERP student candidate exists; confirm identity manually before creation.';
            }
            if (empty($row['grade']) || empty($row['class_name']) || ! array_key_exists('is_boarding', $row)) {
                $errors[] = 'Term 1 grade, class and residency evidence are required.';
            }
            $rows[] = ['source_reference' => $reference, 'status' => $errors ? 'HOLD' : 'READY_CREATE',
                'matched_student_id' => $admissionMatch['id'] ?? null, 'potential_name_match_count' => count($nameMatches),
                'errors' => array_values(array_unique($errors))];
        }

        return ['scope' => $manifest['scope'], 'rows' => $rows,
            'ready_to_create' => count(array_filter($rows, fn ($row) => $row['status'] === 'READY_CREATE')),
            'held' => count(array_filter($rows, fn ($row) => $row['status'] === 'HOLD')),
            'matched_existing' => count(array_filter($rows, fn ($row) => $row['matched_student_id'] !== null))];
    }

    private function nameKey(array $row): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim(implode(' ', array_filter([
            $row['first_name'] ?? '', $row['other_names'] ?? '', $row['last_name'] ?? '',
        ])))));
    }
}
