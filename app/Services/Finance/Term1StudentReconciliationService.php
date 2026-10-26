<?php

namespace App\Services\Finance;

use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\MigrationStagingRow;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/** Create only independently verified, absent students and a historical Term 1 enrolment. */
class Term1StudentReconciliationService
{
    public function __construct(private Term1StudentReconciliationPlanner $planner) {}

    public function execute(array $manifest, array $evidence, array $snapshot, User $maker, string $deployedCommit): array
    {
        $now = CarbonImmutable::now();
        $snapshotAt = CarbonImmutable::parse($snapshot['exported_at'] ?? '2000-01-01');
        $backupAt = CarbonImmutable::parse($evidence['backup_verified_at'] ?? '2000-01-01');
        $manifestHash = hash('sha256', json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $snapshotHash = hash('sha256', json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        if (($manifest['scope'] ?? null) !== 'term1_2026_students' || (int) ($manifest['academic_year'] ?? 0) !== 2026
            || ! hash_equals((string) ($evidence['manifest_sha256'] ?? ''), $manifestHash)
            || ($snapshot['format'] ?? null) !== 'hande-term1-reconciliation-v1'
            || (int) ($snapshot['school']['id'] ?? 0) !== (int) ($manifest['school_id'] ?? 0)
            || ($snapshot['database_driver'] ?? null) !== DB::getDriverName()
            || ($evidence['database_driver'] ?? null) !== DB::getDriverName()
            || ($evidence['snapshot_sha256'] ?? null) !== $snapshotHash
            || ($evidence['production_branch'] ?? null) !== 'main'
            || ($evidence['deployment_commit'] ?? null) !== $deployedCommit
            || ! preg_match('/^[a-f0-9]{40}$/D', $deployedCommit)
            || empty($evidence['deployment_reference'])
            || $snapshotAt->isFuture() || $snapshotAt->lt($now->subDay())
            || $backupAt->isFuture() || $backupAt->lt($now->subDay())) {
            throw new RuntimeException('Manifest scope or fingerprint is invalid.');
        }
        if (empty($evidence['approval_reference']) || empty($evidence['backup_restore_verified'])
            || empty($evidence['backup_reference']) || empty($evidence['snapshot_reference'])
            || ($evidence['maker_id'] ?? null) !== $maker->id) {
            throw new RuntimeException('Independent approval, verified backup, current snapshot and maker evidence are required.');
        }
        $checker = User::find($evidence['checker_id'] ?? 0);
        if (! $maker->is_active || (int) $maker->school_id !== (int) $manifest['school_id']
            || ! $maker->hasAnyRole(['school-admin', 'super-admin']) || ! $checker || ! $checker->is_active
            || (int) $checker->school_id !== (int) $manifest['school_id'] || $checker->id === $maker->id
            || ! $checker->hasAnyRole(['school-admin', 'super-admin'])) {
            throw new RuntimeException('Active same-school maker and independent checker with administrator roles are required.');
        }
        if (! is_array($manifest['students'] ?? null) || ! is_array($evidence['cleared_references'] ?? null) || empty($evidence['cleared_references'])) {
            throw new RuntimeException('Student rows and individually cleared source references are required.');
        }
        $preview = $this->planner->preview($manifest, $snapshot);
        $readyRefs = collect($preview['rows'])->where('status', 'READY_CREATE')->pluck('source_reference')->all();
        if (count($evidence['cleared_references']) !== count(array_unique($evidence['cleared_references']))
            || array_diff($evidence['cleared_references'], $readyRefs)) {
            throw new RuntimeException('Only individually cleared candidates with no current ERP identity match may be created.');
        }
        $rowsByRef = [];
        foreach ($manifest['students'] as $row) {
            $this->validateRow($row);
            if (isset($rowsByRef[$row['source_reference']])) {
                throw new RuntimeException('Duplicate source reference in student manifest.');
            }
            $rowsByRef[$row['source_reference']] = $row;
        }
        $references = $evidence['cleared_references'];
        if (count($references) !== count(array_unique($references))) {
            throw new RuntimeException('Cleared source references must be unique.');
        }
        foreach ($references as $reference) {
            if (! isset($rowsByRef[$reference])) {
                throw new RuntimeException('An approved source reference is not in the manifest.');
            }
        }
        $schoolId = (int) $manifest['school_id'];

        return DB::transaction(function () use ($schoolId, $references, $rowsByRef, $maker, $checker, $evidence) {
            School::whereKey($schoolId)->lockForUpdate()->firstOrFail();
            $already = [];
            foreach ($references as $reference) {
                $row = $rowsByRef[$reference];
                $key = hash('sha256', $schoolId.'|2026|Term 1|student|'.$reference);
                $prior = MigrationStagingRow::where('school_id', $schoolId)->where('idempotency_key', $key)->lockForUpdate()->first();
                if (! $prior) {
                    continue;
                }
                if ($prior->status !== 'posted' || ($prior->parsed_data['source_hash'] ?? null) !== hash('sha256', json_encode($row, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR))
                    || ! Student::where('school_id', $schoolId)->whereKey($prior->matched_entity_id)->exists()
                    || ! Enrollment::where('school_id', $schoolId)->where('student_id', $prior->matched_entity_id)->where('academic_year', '2026')->where('term', 'Term 1')->exists()) {
                    throw new RuntimeException('A prior student import conflicts with the approved source row.');
                }
                $already[] = (int) $prior->matched_entity_id;
            }
            if (count($already) === count($references)) {
                return ['batch_id' => null, 'created_or_previously_imported' => count($already), 'student_ids' => $already];
            }
            $batch = app(HistoricalMigrationService::class)->createBatch($schoolId, 'term1_student_master', 'approved-student-manifest.json', $maker->id,
                ['approval_reference' => $evidence['approval_reference'], 'snapshot_reference' => $evidence['snapshot_reference']]);
            $created = [];
            foreach ($references as $index => $reference) {
                $row = $rowsByRef[$reference];
                $key = hash('sha256', $schoolId.'|2026|Term 1|student|'.$reference);
                $prior = MigrationStagingRow::where('school_id', $schoolId)->where('idempotency_key', $key)->lockForUpdate()->first();
                if ($prior) {
                    if ($prior->status !== 'posted' || ($prior->parsed_data['source_hash'] ?? null) !== hash('sha256', json_encode($row, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR))) {
                        throw new RuntimeException('A prior student import conflicts with the approved source row.');
                    }
                    $created[] = (int) $prior->matched_entity_id;
                    continue;
                }
                $this->assertAbsent($schoolId, $row);
                $student = Student::create([
                    'school_id' => $schoolId, 'first_name' => trim($row['first_name']), 'last_name' => trim($row['last_name']),
                    'other_names' => $row['other_names'] ?? null, 'gender' => $row['gender'] ?? null,
                    'date_of_birth' => $row['date_of_birth'] ?? null, 'admission_number' => trim($row['admission_number']),
                    'registration_number' => $row['registration_number'] ?? null, 'grade' => $row['grade'] ?? null,
                    'class_name' => $row['class_name'] ?? null, 'is_boarding' => (bool) $row['is_boarding'],
                    'status' => 'active', 'funding_status' => 'regular',
                ]);
                $enrollmentValues = [
                    'school_id' => $schoolId, 'student_id' => $student->id, 'academic_year' => '2026', 'term' => 'Term 1',
                    'grade' => $row['grade'] ?? null, 'class_name' => $row['class_name'] ?? null,
                    'is_boarding' => (bool) $row['is_boarding'], 'has_transport' => false,
                    'enrollment_date' => '2026-01-01', 'status' => 'completed',
                ];
                if (Schema::hasColumn('enrollments', 'student_type')) {
                    $enrollmentValues['student_type'] = $row['is_boarding'] ? 'boarding' : 'day';
                }
                Enrollment::create($enrollmentValues);
                MigrationStagingRow::create([
                    'migration_batch_id' => $batch->id, 'school_id' => $schoolId, 'row_number' => $index + 1,
                    'accounting_date' => '2026-01-01', 'reference_number' => $reference,
                    'description' => 'Verified missing student master record; Term 1 2026 historical enrolment',
                    'amount' => 0, 'direction' => 'debit', 'classification' => 'student_master', 'raw_data' => $row,
                    'parsed_data' => ['source_hash' => hash('sha256', json_encode($row, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR))],
                    'matched_entity_type' => Student::class, 'matched_entity_id' => $student->id,
                    'status' => 'posted', 'idempotency_key' => $key,
                ]);
                AuditLog::create(['school_id' => $schoolId, 'user_id' => $maker->id, 'action' => 'term1_student_created',
                    'module' => 'students', 'model_type' => Student::class, 'model_id' => $student->id,
                    'new_values' => ['source_reference' => $reference, 'term' => 'Term 1', 'academic_year' => 2026,
                        'checker_id' => $checker->id, 'approval_reference' => $evidence['approval_reference']],
                    'description' => 'Verified absent student created from approved Term 1 source.']);
                $created[] = $student->id;
            }
            $batch->update(['status' => 'posted', 'total_rows' => count($references), 'valid_rows' => count($references),
                'posted_rows' => count($references), 'is_balanced' => true, 'posted_at' => now()]);

            return ['batch_id' => $batch->id, 'created_or_previously_imported' => count($created), 'student_ids' => $created];
        });
    }

    private function validateRow(array $row): void
    {
        Validator::make($row, [
            'source_reference' => 'required|string|max:100', 'confirmed_absent' => 'required|accepted',
            'identity_evidence' => 'required|string|min:10|max:1000', 'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255', 'admission_number' => 'required|string|max:100',
            'registration_number' => 'nullable|string|max:100', 'other_names' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:50', 'date_of_birth' => 'nullable|date', 'grade' => 'required|string|max:50',
            'class_name' => 'required|string|max:100', 'is_boarding' => 'required|boolean',
        ])->validate();
    }

    private function assertAbsent(int $schoolId, array $row): void
    {
        if (Student::where('school_id', $schoolId)->where(function ($query) use ($row) {
            $query->where('admission_number', trim($row['admission_number']));
            if (! empty($row['registration_number'])) {
                $query->orWhere('registration_number', trim($row['registration_number']));
            }
        })->exists()) {
            throw new RuntimeException('Student identity appeared in ERP since the snapshot; no duplicate was created.');
        }
    }
}
