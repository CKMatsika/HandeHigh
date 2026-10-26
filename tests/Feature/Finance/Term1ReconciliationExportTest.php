<?php

namespace Tests\Feature\Finance;

use App\Models\School;
use App\Models\Student;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Term1ReconciliationExportTest extends TestCase
{
    use RefreshDatabase;

    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }

    private function filename(): string
    {
        $name = 'synthetic-export-'.bin2hex(random_bytes(6)).'.json';
        $this->files[] = storage_path('app/private/reconciliation/'.$name);

        return $name;
    }

    public function test_export_is_read_only_tenant_scoped_and_preserves_inactive_students_and_history(): void
    {
        $school = School::create(['name' => 'Synthetic school', 'code' => 'EXPORT-TEST', 'currency' => 'USD', 'bank_account_number' => 'DO-NOT-EXPORT']);
        $other = School::create(['name' => 'Other school', 'code' => 'EXPORT-OTHER']);
        $student = Student::create(['school_id' => $school->id, 'first_name' => 'Synthetic', 'last_name' => 'Learner', 'status' => 'inactive', 'admission_number' => 'SYN-001']);
        Student::create(['school_id' => $other->id, 'first_name' => 'PRIVATE-OTHER', 'last_name' => 'Learner']);
        $invoice = DB::table('invoices')->insertGetId(['school_id' => $school->id, 'student_id' => $student->id, 'number' => 'SYN-PRIOR',
            'academic_year' => '2025', 'term' => 'First Term', 'issued_at' => '2025-01-01', 'total_amount' => 100, 'balance' => 75, 'status' => 'unpaid']);
        DB::table('enrollments')->insert(['school_id' => $school->id, 'student_id' => $student->id, 'academic_year' => '2026', 'term' => 'Term 1', 'grade' => 'Form 2', 'enrollment_date' => '2026-01-01', 'status' => 'active']);
        $subject = DB::table('subjects')->insertGetId(['school_id' => $school->id, 'name' => 'Synthetic science', 'code' => 'SYN-SCI']);
        DB::table('student_subjects')->insert(['student_id' => $student->id, 'subject_id' => $subject, 'academic_year' => '2026', 'term' => 'Term 1', 'is_active' => true]);
        DB::table('fee_structures')->insert(['school_id' => $school->id, 'academic_year' => '2026', 'term' => 'Term 1', 'code' => 'SYN-TUITION', 'category' => 'tuition', 'label' => 'Synthetic tuition', 'amount' => 100]);
        $batch = DB::table('journal_batches')->insertGetId(['school_id' => $school->id, 'batch_number' => 'SYN-OPENING', 'transaction_date' => '2026-01-01', 'description' => 'Synthetic opening', 'source_type' => 'opening_balance', 'status' => 'posted']);
        $account = DB::table('accounts')->insertGetId(['school_id' => $school->id, 'code' => 'SYN-AR', 'name' => 'Synthetic debtors', 'type' => 'asset', 'category' => 'receivable']);
        DB::table('journal_entries')->insert(['journal_batch_id' => $batch, 'account_id' => $account, 'entry_type' => 'debit', 'amount' => 100]);
        $cashbook = DB::table('cashbook')->insertGetId(['school_id' => $school->id, 'account_id' => $account, 'transaction_type' => 'income',
            'category' => 'fees', 'description' => 'Synthetic receipt line', 'amount' => 10, 'balance_after' => 10,
            'transaction_date' => '2026-01-03', 'reference_number' => 'SYN-CASH-01']);
        DB::table('cashbook_transactions')->insert(['school_id' => $school->id, 'account_id' => $account, 'transaction_date' => '2026-01-03',
            'reference_number' => 'SYN-CASH-01', 'description' => 'Synthetic bank reconciliation line', 'amount' => 10,
            'transaction_type' => 'debit', 'category' => 'income', 'status' => 'unmatched']);
        $writes = [];
        DB::listen(function (QueryExecuted $query) use (&$writes) {
            if (preg_match('/^\s*(insert|update|delete|replace|create|alter|drop|truncate)\b/i', $query->sql)) {
                $writes[] = $query->sql;
            }
        });
        $name = $this->filename();
        $this->artisan('finance:export-term1-reconciliation', ['--school' => 'EXPORT-TEST', '--year' => '2026', '--filename' => $name])->assertSuccessful();
        $raw = file_get_contents(end($this->files));
        $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame([], $writes);
        $this->assertSame('hande-term1-reconciliation-v1', $data['format']);
        $this->assertSame($student->id, $data['data']['students'][0]['id']);
        $this->assertSame('inactive', $data['data']['students'][0]['status']);
        $this->assertSame(1, $data['counts']['students']);
        $this->assertSame($invoice, $data['data']['invoices'][0]['id']);
        $this->assertSame('2025', $data['data']['invoices'][0]['academic_year']);
        $this->assertSame(1, $data['counts']['student_subjects']);
        $this->assertSame(1, $data['counts']['fee_structures']);
        $this->assertSame(1, $data['counts']['enrollments']);
        $this->assertSame($batch, $data['data']['journal_entries'][0]['journal_batch_id']);
        $this->assertSame(1, $data['counts']['journal_entries']);
        $this->assertSame(1, $data['counts']['cashbook']);
        $this->assertSame(1, $data['counts']['cashbook_transactions']);
        $this->assertSame($cashbook, $data['data']['cashbook'][0]['id']);
        $this->assertStringNotContainsString('PRIVATE-OTHER', $raw);
        $this->assertStringNotContainsString('DO-NOT-EXPORT', $raw);
        $this->assertStringNotContainsString('password', $raw);
        $this->assertStringNotContainsString('bank_account_number', $raw);
    }

    public function test_explicit_existing_tenant_is_required(): void
    {
        $this->artisan('finance:export-term1-reconciliation')->assertFailed();
        $this->artisan('finance:export-term1-reconciliation', ['--school' => 'MISSING'])->assertFailed();
    }

    public function test_export_refuses_unsafe_filename_and_invalid_year(): void
    {
        $this->artisan('finance:export-term1-reconciliation', ['--school' => 'TEST', '--filename' => '../public.json'])->assertFailed();
        $this->artisan('finance:export-term1-reconciliation', ['--school' => 'TEST', '--year' => 'not-a-year'])->assertFailed();
    }

    public function test_existing_export_is_never_overwritten(): void
    {
        School::create(['name' => 'Synthetic school', 'code' => 'EXPORT-TEST']);
        $name = $this->filename();
        $path = end($this->files);
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0700, true);
        }
        file_put_contents($path, 'preserve-existing-export');
        $this->artisan('finance:export-term1-reconciliation', ['--school' => 'EXPORT-TEST', '--filename' => $name])->assertFailed();
        $this->assertSame('preserve-existing-export', file_get_contents($path));
    }
}
