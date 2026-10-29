<?php

namespace Tests\Feature\Academic;

use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\JournalBatch;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentReconciliationBatch;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StudentReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        (new RoleSeeder())->run();
        (new RolePermissionSeeder())->run();
        $this->school = School::create(['name' => 'Reconciliation School', 'code' => 'RCS', 'is_active' => true]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id]);
        $this->admin->assignRole('school-admin');
    }

    public function test_multiple_registers_are_compared_to_students_enrolments_and_invoices_without_mutating_erp_data(): void
    {
        $matched = $this->student('ADM-100', 'Tariro', 'Moyo', 'Lower 6', 'Lower 6 Commercials');
        $unbilled = $this->student('ADM-101', 'Tendai', 'Ncube', 'Form 2', 'East');
        $invoice = Invoice::create([
            'school_id' => $this->school->id, 'student_id' => $matched->id, 'number' => 'INV-2026-000100',
            'type' => 'fees', 'academic_year' => '2026', 'term' => 'Term 1', 'total_amount' => 470,
            'balance' => 470, 'issued_at' => '2026-01-01', 'due_date' => '2026-01-31', 'status' => 'unpaid',
        ]);

        $before = [
            'students' => Student::where('school_id', $this->school->id)->count(),
            'enrollments' => Enrollment::where('school_id', $this->school->id)->count(),
            'invoices' => Invoice::where('school_id', $this->school->id)->count(),
            'payments' => Payment::where('school_id', $this->school->id)->count(),
            'journals' => JournalBatch::where('school_id', $this->school->id)->count(),
            'matched_grade' => $matched->fresh()->getRawOriginal('grade'),
            'matched_class' => $matched->fresh()->getRawOriginal('class_name'),
            'invoice_total' => $invoice->fresh()->total_amount,
        ];

        $studentRegister = $this->xlsx('class-lower-six.xlsx', [
            ['Hande High March Register'],
            ['ERP Student ID', 'Student Name', 'Grade/Form', 'Class/Stream', 'Residency', 'Invoice Status', 'Invoice Amount', 'Academic Year', 'Term'],
            [$matched->id, 'Tariro Moyo', 'Lower 6', 'Lower 6 Arts', 'Boarding', 'Invoiced', 500, '2026', 'Term 1'],
            [$unbilled->id, 'Tendai Ncube', 'Form 2', 'East', 'Day', 'Invoiced', 120, '2026', 'Term 1'],
        ]);
        $invoiceRegister = $this->xlsx('term-one-invoices.xlsx', [
            ['Admission Number', 'Student Name', 'Invoice Number', 'Amount', 'Academic Year', 'Term'],
            ['ADM-100', 'Tariro Moyo', 'INV-2026-000100', 450, '2026', 'Term 1'],
            ['ADM-101', 'Tendai Ncube', 'INV-MISSING-101', 120, '2026', 'Term 1'],
        ]);

        $this->actingAs($this->admin)->post(route('admin.student-reconciliation.store'), [
            'academic_year' => '2026', 'term' => 'Term 1',
            'files' => [$studentRegister, $invoiceRegister],
            'source_types' => ['class_register', 'invoice_register'],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $batch = StudentReconciliationBatch::where('school_id', $this->school->id)->latest('id')->firstOrFail();
        $this->assertSame(2, $batch->source_count);
        $this->assertSame(4, $batch->source_rows_count);
        $this->assertSame('completed', $batch->status);
        $this->assertDatabaseHas('student_reconciliation_findings', ['batch_id' => $batch->id, 'code' => 'class_mismatch', 'student_id' => $matched->id]);
        $this->assertDatabaseHas('student_reconciliation_findings', ['batch_id' => $batch->id, 'code' => 'invoice_amount_mismatch', 'student_id' => $matched->id]);
        $this->assertDatabaseHas('student_reconciliation_findings', ['batch_id' => $batch->id, 'code' => 'invoice_missing_in_erp', 'student_id' => $unbilled->id]);
        $this->assertDatabaseHas('student_reconciliation_findings', ['batch_id' => $batch->id, 'code' => 'invoice_reference_not_found', 'student_id' => $unbilled->id]);

        $this->assertSame($before['students'], Student::where('school_id', $this->school->id)->count());
        $this->assertSame($before['enrollments'], Enrollment::where('school_id', $this->school->id)->count());
        $this->assertSame($before['invoices'], Invoice::where('school_id', $this->school->id)->count());
        $this->assertSame($before['payments'], Payment::where('school_id', $this->school->id)->count());
        $this->assertSame($before['journals'], JournalBatch::where('school_id', $this->school->id)->count());
        $this->assertSame($before['matched_grade'], $matched->fresh()->getRawOriginal('grade'));
        $this->assertSame($before['matched_class'], $matched->fresh()->getRawOriginal('class_name'));
        $this->assertSame($before['invoice_total'], $invoice->fresh()->total_amount);

        $this->actingAs($this->admin)->get(route('admin.student-reconciliation.show', $batch))->assertOk()->assertSee('Lower 6 Arts')->assertSee('INV-MISSING-101');
        $this->actingAs($this->admin)->get(route('admin.student-reconciliation.export', $batch))->assertOk()->assertDownload('student-reconciliation-2026-term-1-batch-'.$batch->id.'.csv');
    }

    public function test_complete_register_checks_report_erp_students_missing_from_upload_without_creating_students(): void
    {
        $listed = $this->student('ADM-200', 'Listed', 'Student', 'Form 1', 'East');
        $absent = $this->student('ADM-201', 'Notlisted', 'Student', 'Form 1', 'West');
        $file = $this->xlsx('complete-register.xlsx', [
            ['ERP Student ID', 'Student Name', 'Grade', 'Class'],
            [$listed->id, 'Listed Student', 'Form 1', 'East'],
        ]);

        $this->actingAs($this->admin)->post(route('admin.student-reconciliation.store'), [
            'academic_year' => '2026', 'term' => 'Term 1', 'complete_register' => '1',
            'files' => [$file], 'source_types' => ['student_register'],
        ])->assertSessionHasNoErrors();
        $batch = StudentReconciliationBatch::where('school_id', $this->school->id)->latest('id')->firstOrFail();
        $this->assertDatabaseHas('student_reconciliation_findings', ['batch_id' => $batch->id, 'code' => 'erp_student_not_in_uploaded_register', 'student_id' => $absent->id]);
        $this->assertSame(2, Student::where('school_id', $this->school->id)->count());
    }

    public function test_expected_total_is_invoice_evidence_even_when_source_is_labelled_student_register(): void
    {
        $student = $this->student('ADM-450', 'Tariro', 'Moyo', 'Lower 6', 'Lower 6 Arts');
        Invoice::create([
            'school_id' => $this->school->id, 'student_id' => $student->id, 'number' => 'INV-EXPECTED-450',
            'type' => 'fees', 'academic_year' => '2026', 'term' => 'Term 1', 'total_amount' => 470,
            'balance' => 470, 'issued_at' => '2026-01-01', 'due_date' => '2026-01-31', 'status' => 'unpaid',
        ]);
        $file = $this->xlsx('fees-register.xlsx', [
            ['FEES PAYMENT SCHEDULE - TERM 1 2026'],
            ['', '', '', '', '', 'EXPECTED'],
            ['ERP Student ID', 'Student Name', 'Grade', 'Class', 'Category', 'TOTAL'],
            [$student->id, 'Tariro Moyo', 'Lower 6', 'Lower 6 Arts', 'Boarding', 500],
        ]);

        $this->actingAs($this->admin)->post(route('admin.student-reconciliation.store'), [
            'academic_year' => '2026', 'term' => 'Term 1', 'complete_invoice_register' => '1',
            'files' => [$file], 'source_types' => ['student_register'],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $batch = StudentReconciliationBatch::where('school_id', $this->school->id)->latest('id')->firstOrFail();
        $row = $batch->rows()->firstOrFail();
        $this->assertSame(500.0, (float) $row->normalized_data['invoice_amount']);
        $this->assertSame('expected total', $row->normalized_data['invoice_amount_basis']);
        $this->assertTrue($row->normalized_data['invoice_claimed']);
        $this->assertDatabaseHas('student_reconciliation_findings', ['batch_id' => $batch->id, 'code' => 'invoice_amount_mismatch', 'student_id' => $student->id]);
        $this->assertDatabaseMissing('student_reconciliation_findings', ['batch_id' => $batch->id, 'code' => 'invoice_completeness_not_evaluable']);
    }

    public function test_invoice_completeness_checkbox_does_not_block_a_register_with_unstable_names(): void
    {
        $student = $this->student('ADM-470', 'Tariro', 'Moyo', 'Lower 6', 'Lower 6 Arts');
        $file = $this->xlsx('two-row-fees-heading.xlsx', [
            ['FEES PAYMENT SCHEDULE - TERM 1 2026'],
            ['', '', '', '', '', 'EXPECTED'],
            ['STUDENT', 'SURNAME', 'NAME', 'CLASS', 'CATEGORY', 'TOTAL'],
            ['Tariro Moyo', 'Moyo', 'Tariro', 'Lower 6 Arts', 'Boarding', 470],
            ['', 'TOTAL', '', '', '', 470],
        ]);

        $this->actingAs($this->admin)->post(route('admin.student-reconciliation.store'), [
            'academic_year' => '2026', 'term' => 'Term 1', 'complete_invoice_register' => '1',
            'files' => [$file], 'source_types' => ['class_register'],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $batch = StudentReconciliationBatch::where('school_id', $this->school->id)->latest('id')->firstOrFail();
        $this->assertSame('completed', $batch->status);
        $this->assertSame(['last_name', 'first_name', 'class_name', 'invoice_amount'], $batch->sources()->first()->recognized_columns);
        $this->assertSame(1, $batch->rows()->count(), 'A grand total row must not be reported as another student.');
        $this->assertSame(470.0, (float) $batch->rows()->first()->normalized_data['invoice_amount']);
        $this->assertDatabaseHas('student_reconciliation_findings', ['batch_id' => $batch->id, 'code' => 'invoice_completeness_identity_limited', 'severity' => 'info']);
        $this->assertDatabaseHas('student_reconciliation_findings', ['batch_id' => $batch->id, 'code' => 'identity_needs_verification', 'student_id' => $student->id]);
        $this->assertDatabaseHas('student_reconciliation_findings', ['batch_id' => $batch->id, 'code' => 'possible_invoice_missing_for_name_match', 'student_id' => $student->id, 'severity' => 'warning']);
    }

    public function test_finding_review_is_audited_and_does_not_change_the_linked_student_or_tenant_records(): void
    {
        $student = $this->student('ADM-300', 'Review', 'Only', 'Form 3', 'North');
        $batch = StudentReconciliationBatch::create(['school_id' => $this->school->id, 'created_by' => $this->admin->id, 'academic_year' => '2026', 'term' => 'Term 1', 'status' => 'completed']);
        $finding = $batch->findings()->create(['student_id' => $student->id, 'code' => 'class_mismatch', 'severity' => 'warning', 'field' => 'class', 'message' => 'Review this difference.']);
        $enrolmentCount = Enrollment::where('student_id', $student->id)->count();
        $classBefore = $student->fresh()->getRawOriginal('class_name');

        $this->actingAs($this->admin)->post(route('admin.student-reconciliation.review', [$batch, $finding]), [
            'status' => 'reviewed', 'review_note' => 'Checked against the March signed register.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('reviewed', $finding->fresh()->status);
        $this->assertSame($classBefore, $student->fresh()->getRawOriginal('class_name'));
        $this->assertSame($enrolmentCount, Enrollment::where('student_id', $student->id)->count());
        $this->assertDatabaseHas('audit_logs', ['school_id' => $this->school->id, 'action' => 'student_reconciliation_finding_reviewed', 'model_id' => $finding->id]);
    }

    public function test_reconciliation_report_cannot_be_accessed_across_school_tenants(): void
    {
        $otherSchool = School::create(['name' => 'Other Reconciliation School', 'code' => 'ORCS', 'is_active' => true]);
        $batch = StudentReconciliationBatch::create(['school_id' => $otherSchool->id, 'academic_year' => '2026', 'term' => 'Term 1', 'status' => 'completed']);

        $this->actingAs($this->admin)->get(route('admin.student-reconciliation.show', $batch))->assertNotFound();
    }

    private function student(string $admission, string $first, string $last, string $grade, string $class): Student
    {
        $student = Student::create([
            'school_id' => $this->school->id, 'first_name' => $first, 'last_name' => $last,
            'gender' => 'female', 'date_of_birth' => '2010-05-30', 'admission_number' => $admission,
            'registration_number' => 'REG-'.$admission, 'grade' => $grade, 'class_name' => $class,
            'is_boarding' => false, 'status' => 'active',
        ]);
        Enrollment::create([
            'school_id' => $this->school->id, 'student_id' => $student->id, 'academic_year' => '2026',
            'term' => 'Term 1', 'grade' => $grade, 'class_name' => $class, 'is_boarding' => false,
            'has_transport' => false, 'enrollment_date' => '2026-01-01', 'status' => 'active',
        ]);
        return $student;
    }

    private function xlsx(string $name, array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');
        $path = tempnam(sys_get_temp_dir(), 'student-recon-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        return new UploadedFile($path, $name, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
