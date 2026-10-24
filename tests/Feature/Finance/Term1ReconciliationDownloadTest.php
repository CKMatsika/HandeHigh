<?php

namespace Tests\Feature\Finance;

use App\Models\AuditLog;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Term1ReconciliationDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function actor(string $role = 'school-admin', ?School $school = null): User
    {
        $school ??= School::create(['name' => 'Synthetic school', 'code' => 'DOWNLOAD-'.bin2hex(random_bytes(3))]);
        $user = User::factory()->create(['school_id' => $school->id, 'is_active' => true]);
        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }

    private function url(): string
    {
        return route('admin.reports.student-register.reconciliation-export');
    }

    public function test_authenticated_admin_download_is_private_tenant_scoped_and_metadata_audited(): void
    {
        $admin = $this->actor();
        $other = School::create(['name' => 'Other synthetic school', 'code' => 'OTHER-DOWNLOAD']);
        $student = Student::create(['school_id' => $admin->school_id, 'first_name' => 'PRIVATE-LEARNER', 'last_name' => 'One', 'status' => 'inactive']);
        Student::create(['school_id' => $other->id, 'first_name' => 'OTHER-TENANT', 'last_name' => 'Two']);
        $filesBefore = glob(storage_path('app/private/reconciliation/*.json')) ?: [];
        $writes = [];
        DB::listen(function (QueryExecuted $query) use (&$writes) {
            if (preg_match('/^\s*(insert|update|delete|replace|create|alter|drop|truncate)\b/i', $query->sql)) {
                $writes[] = $query->sql;
            }
        });
        $response = $this->actingAs($admin)->post($this->url(), ['academic_year' => '2026']);
        $response->assertOk()->assertDownload()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $data = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($student->id, $data['data']['students'][0]['id']);
        $this->assertSame(1, $data['counts']['students']);
        $this->assertStringNotContainsString('OTHER-TENANT', $response->getContent());
        $this->assertSame(hash('sha256', $response->getContent()), $response->headers->get('X-Export-SHA256'));
        $audit = AuditLog::where('action', 'term1_reconciliation_export_generated')->sole();
        $this->assertSame($admin->id, $audit->user_id);
        $this->assertSame($admin->school_id, $audit->school_id);
        $this->assertSame($response->headers->get('X-Export-SHA256'), $audit->new_values['sha256']);
        $this->assertStringNotContainsString('PRIVATE-LEARNER', json_encode($audit->toArray()));
        $this->assertCount(1, $writes);
        $this->assertStringContainsString('audit_logs', $writes[0]);
        $this->assertSame($filesBefore, glob(storage_path('app/private/reconciliation/*.json')) ?: []);
    }

    public function test_guest_cannot_download(): void
    {
        $this->post($this->url(), ['academic_year' => '2026'])->assertRedirect(route('login'));
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_non_admin_finance_user_cannot_download(): void
    {
        $this->actingAs($this->actor('bursar'))->post($this->url(), ['academic_year' => '2026'])->assertForbidden();
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_disabled_and_schoolless_administrators_cannot_download(): void
    {
        $admin = $this->actor('super-admin');
        $admin->update(['is_active' => false]);
        $this->actingAs($admin)->post($this->url(), ['academic_year' => '2026'])->assertForbidden();
        $admin->update(['is_active' => true, 'school_id' => null]);
        $this->actingAs($admin->fresh())->post($this->url(), ['academic_year' => '2026'])->assertForbidden();
    }

    public function test_caller_cannot_select_another_school_or_file_path(): void
    {
        $this->actingAs($this->actor())->postJson($this->url(), ['academic_year' => '2026', 'school_id' => 999, 'path' => '../.env'])
            ->assertUnprocessable()->assertJsonValidationErrors(['school_id', 'path']);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_no_json_is_released_if_audit_recording_fails(): void
    {
        $admin = $this->actor();
        AuditLog::creating(function () {
            throw new \RuntimeException('Synthetic audit failure');
        });
        try {
            $response = $this->actingAs($admin)->postJson($this->url(), ['academic_year' => '2026']);
            $response->assertStatus(503);
            $this->assertStringNotContainsString('hande-term1-reconciliation-v1', $response->getContent());
            $this->assertFalse($response->headers->has('Content-Disposition'));
        } finally {
            AuditLog::flushEventListeners();
        }
    }

    public function test_download_is_post_only_and_rate_limited(): void
    {
        $this->actingAs($this->actor());
        $this->get($this->url())->assertStatus(405);
        $this->post($this->url(), ['academic_year' => '2026'])->assertOk();
        $this->post($this->url(), ['academic_year' => '2026'])->assertOk();
        $this->post($this->url(), ['academic_year' => '2026'])->assertStatus(429);
        $this->assertDatabaseCount('audit_logs', 2);
    }
}
