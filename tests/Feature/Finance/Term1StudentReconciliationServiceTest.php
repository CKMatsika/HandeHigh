<?php

namespace Tests\Feature\Finance;

use App\Models\Enrollment;
use App\Models\Student;
use App\Services\Finance\Term1StudentReconciliationService;
use App\Services\Finance\Term1ReconciliationExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Support\BeamRehearsal;
use Tests\TestCase;

class Term1StudentReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function authorizedPayload(array $students, array $fixture): array
    {
        $manifest = ['scope' => 'term1_2026_students', 'school_id' => $fixture['school']->id, 'academic_year' => 2026, 'students' => $students];
        $snapshot = app(Term1ReconciliationExportService::class)->snapshot($fixture['school']->id, '2026');
        $evidence = ['approval_reference' => 'SYNTHETIC-MANAGEMENT-APPROVAL', 'backup_restore_verified' => true,
            'backup_reference' => 'SYNTHETIC-RESTORE-TEST', 'backup_verified_at' => now()->toIso8601String(),
            'snapshot_reference' => 'SYNTHETIC-CURRENT-SNAPSHOT', 'snapshot_sha256' => hash('sha256', json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
            'production_branch' => 'main', 'deployment_commit' => str_repeat('b', 40), 'deployment_reference' => 'SYNTHETIC-DEPLOYMENT',
            'database_driver' => 'sqlite', 'maker_id' => $fixture['maker']->id, 'checker_id' => $fixture['checker']->id,
            'cleared_references' => array_column($students, 'source_reference')];

        return compact('manifest', 'evidence', 'snapshot');
    }

    private function studentRow(string $reference, string $admission): array
    {
        return ['source_reference' => $reference, 'confirmed_absent' => true,
            'identity_evidence' => 'Synthetic admission register and class register verified',
            'first_name' => 'Synthetic', 'last_name' => 'Missing', 'admission_number' => $admission,
            'grade' => 'Form 1', 'class_name' => '1A', 'is_boarding' => true];
    }

    protected function setupFixture(): array
    {
        $rehearsal = new BeamRehearsal;
        $fixture = $rehearsal->fixture();
        Role::findOrCreate('school-admin', 'web');
        $fixture['maker']->forceFill(['is_active' => true])->save();
        $fixture['checker']->forceFill(['is_active' => true])->save();
        $fixture['maker']->assignRole('school-admin');
        $fixture['checker']->assignRole('school-admin');

        return $fixture;
    }

    public function test_creates_only_confirmed_missing_student_and_historical_enrollment(): void
    {
        $fixture = $this->setupFixture();
        ['manifest' => $manifest, 'evidence' => $evidence, 'snapshot' => $snapshot] = $this->authorizedPayload([$this->studentRow('SRC-001', 'SYN-NEW-001')], $fixture);
        $manifestHash = hash('sha256', json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $evidence['manifest_sha256'] = $manifestHash;

        $result = app(Term1StudentReconciliationService::class)->execute($manifest, $evidence, $snapshot, $fixture['maker'], str_repeat('b', 40));
        $this->assertSame(1, $result['created_or_previously_imported']);
        $student = Student::where('school_id', $fixture['school']->id)->where('admission_number', 'SYN-NEW-001')->firstOrFail();
        $enrollment = Enrollment::where('student_id', $student->id)->firstOrFail();
        $this->assertSame('2026', $enrollment->academic_year);
        $this->assertSame('Term 1', $enrollment->term);
        $this->assertSame('completed', $enrollment->status);
        $this->assertTrue((bool) $enrollment->is_boarding);
        $this->assertSame(1, Student::where('school_id', $fixture['school']->id)->where('admission_number', 'SYN-NEW-001')->count());
        $batchCount = \App\Models\MigrationBatch::where('school_id', $fixture['school']->id)->where('import_type', 'term1_student_master')->count();
        $retry = app(Term1StudentReconciliationService::class)->execute($manifest, $evidence, $snapshot, $fixture['maker'], str_repeat('b', 40));
        $this->assertSame([$student->id], $retry['student_ids']);
        $this->assertSame($batchCount, \App\Models\MigrationBatch::where('school_id', $fixture['school']->id)->where('import_type', 'term1_student_master')->count());
    }

    public function test_refuses_if_identity_is_present_in_erp_at_execution_time(): void
    {
        $fixture = $this->setupFixture();
        Student::create(['school_id' => $fixture['school']->id, 'first_name' => 'Synthetic', 'last_name' => 'Existing',
            'admission_number' => 'SYN-EXISTS-001', 'is_boarding' => false, 'status' => 'active']);
        ['manifest' => $manifest, 'evidence' => $evidence, 'snapshot' => $snapshot] = $this->authorizedPayload([$this->studentRow('SRC-002', 'SYN-EXISTS-001')], $fixture);
        $evidence['manifest_sha256'] = hash('sha256', json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        $this->expectException(\RuntimeException::class);
        app(Term1StudentReconciliationService::class)->execute($manifest, $evidence, $snapshot, $fixture['maker'], str_repeat('b', 40));
    }
}
