<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnrollmentImportTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private User $admin;
    private SchoolClass $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Synthetic School',
            'code' => 'SYN',
            'timezone' => 'Africa/Harare',
            'currency' => 'USD',
        ]);

        $this->admin = User::factory()->create(['school_id' => $this->school->id]);
        Role::firstOrCreate(['name' => 'school-admin', 'guard_name' => 'web']);
        $this->admin->assignRole('school-admin');

        $this->class = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Form 1A',
            'grade' => 'Form 1',
            'academic_year' => '2026',
            'term' => 'First Term',
        ]);
    }

    public function test_guests_cannot_access_enrollment_import(): void
    {
        $this->get(route('admin.enrollments.bulk-create'))
            ->assertRedirectToRoute('login');
    }

    public function test_unauthorized_roles_cannot_import(): void
    {
        $teacher = User::factory()->create(['school_id' => $this->school->id]);
        Role::create(['name' => 'teacher', 'guard_name' => 'web']);
        $teacher->assignRole('teacher');

        $this->actingAs($teacher)
            ->get(route('admin.enrollments.bulk-create'))
            ->assertForbidden();
    }

    public function test_authorized_school_admin_can_access_import(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.bulk-create'))
            ->assertOk();
    }

    public function test_invalid_extensions_and_oversized_uploads_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.bulk-store'), [
                'file' => UploadedFile::fake()->create('enrollments.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');

        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.bulk-store'), [
                'file' => UploadedFile::fake()->create('enrollments.csv', 10241, 'text/csv'),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_renamed_html_and_malformed_workbooks_are_rejected_without_stack_traces(): void
    {
        foreach (['fake.xlsx', 'broken.xls'] as $filename) {
            $response = $this->actingAs($this->admin)
                ->post(route('admin.enrollments.bulk-store'), [
                    'file' => UploadedFile::fake()->createWithContent($filename, '<html>not a workbook</html>'),
                ]);

            $response->assertSessionHas('error');
            $this->assertStringNotContainsString('vendor/', $response->getContent());
        }
    }

    public function test_valid_csv_import_is_scoped_and_creates_expected_records(): void
    {
        $csv = implode("\n", [
            'student_name,email,phone,address,date_of_birth,gender,class_name,academic_year,term,grade,is_boarding,has_transport,status',
            'Synthetic Learner,learner@example.test,, ,2010-01-02,male,Form 1A,2026-2027,First Term,Form 1,no,no,active',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.bulk-store'), [
                'file' => UploadedFile::fake()->createWithContent('enrollments.csv', $csv),
            ])
            ->assertRedirectToRoute('admin.enrollments.index')
            ->assertSessionHas('import_summary.new_students_created', 1);

        $student = Student::where('email', 'learner@example.test')->firstOrFail();
        $this->assertSame($this->school->id, $student->school_id);
        $this->assertSame('Synthetic', $student->first_name);
        $this->assertSame('Learner', $student->last_name);
        $this->assertDatabaseHas('enrollments', [
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'class_id' => $this->class->id,
        ]);
    }

    public function test_exact_template_xlsx_handles_new_existing_duplicate_ambiguous_and_repeat_rows(): void
    {
        $existing = Student::create([
            'school_id' => $this->school->id, 'first_name' => 'Existing', 'last_name' => 'XLSX Learner',
            'email' => 'existing.xlsx@example.test', 'date_of_birth' => '2010-03-03',
            'grade' => 'Form 3', 'class_name' => 'Existing Class', 'is_boarding' => true,
            'has_transport' => false, 'status' => 'inactive',
        ]);
        Enrollment::create([
            'school_id' => $this->school->id, 'student_id' => $existing->id,
            'academic_year' => '2025-2026', 'term' => 'Third Term', 'grade' => 'Form 3',
            'class_name' => 'Existing Class', 'is_boarding' => true, 'has_transport' => false,
            'enrollment_date' => '2025-09-01', 'status' => 'inactive',
        ]);

        // Keep this heading row identical to EnrollmentController::downloadTemplate().
        $headers = [
            'student_name', 'email', 'phone', 'address', 'date_of_birth', 'gender',
            'class_name', 'academic_year', 'term', 'grade', 'is_boarding', 'has_transport', 'status',
            'student_category', 'residency', 'funding_status', 'scholarship_reference', 'scholarship_name',
            'scholarship_sponsor', 'scholarship_coverage_type', 'scholarship_coverage_basis',
            'scholarship_coverage_value', 'scholarship_covered_votes', 'scholarship_starts_on',
            'scholarship_ends_on', 'scholarship_accounting_treatment', 'scholarship_supporting_reference',
            'scholarship_academic_year', 'scholarship_funding_source', 'scholarship_sponsor_code', 'scholarship_recognition_evidence',
        ];
        $row = function (string $name, string $email, string $dateOfBirth) use ($headers): array {
            $data = array_fill_keys($headers, null);
            $data['student_name'] = $name;
            $data['email'] = $email;
            $data['date_of_birth'] = $dateOfBirth;
            $data['gender'] = 'other';
            $data['class_name'] = 'Form 1A';
            $data['academic_year'] = '2026-2027';
            $data['term'] = 'First Term';
            $data['grade'] = 'Form 1';
            $data['is_boarding'] = 'no';
            $data['has_transport'] = 'no';
            $data['status'] = 'active';
            $data['student_category'] = 'day';
            $data['residency'] = 'day';
            $data['funding_status'] = 'regular';

            return array_values($data);
        };
        $xlsxRows = [
            $row('Synthetic XLSX First', 'first.placeholder@example.test', '2010-01-01'),
            $row('Synthetic XLSX Second', 'second.placeholder@example.test', '2010-02-02'),
            $row('Existing XLSX Learner', 'existing.xlsx@example.test', '2010-03-03'),
            $row('Shared Email First', 'shared.placeholder@example.test', '2010-04-04'),
            $row('Shared Email Second', 'shared.placeholder@example.test', '2010-05-05'),
            $row('Repeated Name', 'name-one.placeholder@example.test', '2010-06-06'),
            $row('Repeated Name', 'name-two.placeholder@example.test', '2010-06-06'),
        ];
        $paths = [];
        $upload = function () use ($headers, $xlsxRows, &$paths): UploadedFile {
            $spreadsheet = new Spreadsheet();
            $spreadsheet->getActiveSheet()->fromArray([$headers, ...$xlsxRows]);
            $path = tempnam(sys_get_temp_dir(), 'hande-xlsx-');
            (new Xlsx($spreadsheet))->save($path);
            $paths[] = $path;

            return new UploadedFile($path, 'enrollments.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
        };

        try {
            $this->actingAs($this->admin)
                ->post(route('admin.enrollments.bulk-store'), [
                    'file' => $upload(),
                ])
                ->assertRedirectToRoute('admin.enrollments.index')
                ->assertSessionHas('import_summary.new_students_created', 2)
                ->assertSessionHas('import_summary.existing_students_skipped', 1)
                ->assertSessionHas('import_summary.ambiguous_students_skipped', 4)
                ->assertSessionHas('import_summary.invalid_rows', 0);

            $this->assertSame(3, Student::where('school_id', $this->school->id)->count());
            $this->assertSame(3, Enrollment::where('school_id', $this->school->id)->count());
            $this->assertSame('Form 3', $existing->fresh()->getRawOriginal('grade'));
            $this->assertSame('Existing Class', $existing->fresh()->getRawOriginal('class_name'));
            $this->assertSame(1, Enrollment::where('student_id', $existing->id)->count());

            $this->actingAs($this->admin)
                ->post(route('admin.enrollments.bulk-store'), [
                    'file' => $upload(),
                ])
                ->assertRedirectToRoute('admin.enrollments.index')
                ->assertSessionHas('import_summary.new_students_created', 0)
                ->assertSessionHas('import_summary.existing_students_skipped', 3)
                ->assertSessionHas('import_summary.ambiguous_students_skipped', 4);
        } finally {
            foreach ($paths as $path) {
                @unlink($path);
            }
        }

        $this->assertSame(3, Student::where('school_id', $this->school->id)->count());
        $this->assertSame(3, Enrollment::where('school_id', $this->school->id)->count());
        $this->assertDatabaseHas('students', ['school_id' => $this->school->id, 'email' => 'first.placeholder@example.test']);
        $this->assertDatabaseHas('students', ['school_id' => $this->school->id, 'email' => 'second.placeholder@example.test']);
    }

    public function test_cross_school_email_does_not_reuse_another_school_student(): void
    {
        $otherSchool = School::create(['name' => 'Other Synthetic School', 'code' => 'OTH']);
        Student::create([
            'school_id' => $otherSchool->id,
            'first_name' => 'Other',
            'last_name' => 'Learner',
            'email' => 'shared@example.test',
        ]);

        $csv = implode("\n", [
            'student_name,email,class_name,academic_year,term,grade,is_boarding,has_transport,status',
            'Local Learner,shared@example.test,Form 1A,2026-2027,First Term,Form 1,no,no,active',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.bulk-store'), [
                'file' => UploadedFile::fake()->createWithContent('enrollments.csv', $csv),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('students', [
            'school_id' => $this->school->id,
            'email' => 'shared@example.test',
        ]);
        $this->assertSame(2, Student::where('email', 'shared@example.test')->count());
    }

    public function test_invalid_rows_are_reported_with_excel_row_numbers_while_valid_rows_import(): void
    {
        $csv = implode("\n", [
            'student_name,email,class_name,academic_year,term,grade,is_boarding,has_transport,status',
            'First Learner,first@example.test,Form 1A,2026,First Term,Form 1,no,no,active',
            'Second Learner,second@example.test,Form 1A,2026,Fourth Term,Form 1,no,no,active',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.bulk-store'), [
                'file' => UploadedFile::fake()->createWithContent('enrollments.csv', $csv),
            ])
            ->assertRedirectToRoute('admin.enrollments.index')
            ->assertSessionHas('import_summary.invalid_rows', 1)
            ->assertSessionHas('import_summary.rows.1.row_number', 3);

        $this->assertDatabaseHas('students', ['email' => 'first@example.test']);
        $this->assertDatabaseMissing('students', ['email' => 'second@example.test']);
        $this->assertSame(1, Enrollment::count());
    }

    public function test_existing_student_and_enrollment_are_completely_unchanged(): void
    {
        $student = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Existing', 'last_name' => 'Learner',
            'email' => 'existing@example.test', 'admission_number' => 'HH-100',
            'date_of_birth' => '2010-01-02', 'grade' => 'Form 2', 'class_name' => 'Old Class',
            'is_boarding' => true, 'has_transport' => false, 'funding_status' => 'scholarship', 'status' => 'inactive',
        ]);
        $enrollment = Enrollment::create([
            'school_id' => $this->school->id, 'student_id' => $student->id,
            'academic_year' => '2025', 'term' => 'Third Term', 'grade' => 'Form 2', 'class_name' => 'Old Class',
            'is_boarding' => true, 'has_transport' => false, 'enrollment_date' => '2025-09-01', 'status' => 'inactive',
        ]);

        $csv = implode("\n", [
            'student_name,email,admission_number,date_of_birth,class_name,academic_year,term,grade,is_boarding,has_transport,funding_status,status',
            'Existing Learner,existing@example.test,HH-100,2010-01-02,Form 1A,2026,First Term,Form 1,no,yes,regular,active',
        ]);

        $this->actingAs($this->admin)->post(route('admin.enrollments.bulk-store'), [
            'file' => UploadedFile::fake()->createWithContent('existing.csv', $csv),
        ])->assertSessionHas('import_summary.existing_students_skipped', 1);

        $student->refresh();
        $enrollment->refresh();
        $this->assertSame('Form 2', $student->getRawOriginal('grade'));
        $this->assertSame('Old Class', $student->getRawOriginal('class_name'));
        $this->assertTrue((bool) $student->is_boarding);
        $this->assertFalse((bool) $student->has_transport);
        $this->assertSame('scholarship', $student->funding_status);
        $this->assertSame('inactive', $student->status);
        $this->assertSame('Old Class', $enrollment->class_name);
        $this->assertSame(1, Enrollment::where('student_id', $student->id)->count());
    }

    public function test_duplicate_rows_with_different_emails_are_both_skipped(): void
    {
        $csv = implode("\n", [
            'student_name,email,date_of_birth,class_name,academic_year,term,grade',
            'Same Person,one@example.test,2010-01-02,Form 1A,2026,First Term,Form 1',
            'Same Person,two@example.test,2010-01-02,Form 1A,2026,First Term,Form 1',
        ]);

        $this->actingAs($this->admin)->post(route('admin.enrollments.bulk-store'), [
            'file' => UploadedFile::fake()->createWithContent('duplicates.csv', $csv),
        ])->assertSessionHas('import_summary.ambiguous_students_skipped', 2);

        $this->assertSame(0, Student::where('school_id', $this->school->id)->count());
        $this->assertSame(0, Enrollment::count());
    }

    public function test_reupload_is_idempotent_and_blank_or_shared_email_rows_are_held(): void
    {
        $csv = implode("\n", [
            'student_name,email,admission_number,date_of_birth,class_name,academic_year,term,grade',
            'New Learner,new@example.test,HH-NEW-1,2010-02-03,Form 1A,2026,First Term,Form 1',
            'No Identity,, ,2010-04-05,Form 1A,2026,First Term,Form 1',
            'Shared One,shared@example.test,,2010-06-01,Form 1A,2026,First Term,Form 1',
            'Shared Two,shared@example.test,,2010-07-01,Form 1A,2026,First Term,Form 1',
        ]);
        $file = fn () => UploadedFile::fake()->createWithContent('safe.csv', $csv);

        $this->actingAs($this->admin)->post(route('admin.enrollments.bulk-store'), ['file' => $file()])
            ->assertSessionHas('import_summary.new_students_created', 1)
            ->assertSessionHas('import_summary.ambiguous_students_skipped', 2)
            ->assertSessionHas('import_summary.invalid_rows', 1);
        $this->assertSame(1, Student::where('school_id', $this->school->id)->count());
        $this->assertSame(1, Enrollment::count());

        $this->actingAs($this->admin)->post(route('admin.enrollments.bulk-store'), ['file' => $file()])
            ->assertSessionHas('import_summary.new_students_created', 0)
            ->assertSessionHas('import_summary.existing_students_skipped', 1)
            ->assertSessionHas('import_summary.ambiguous_students_skipped', 2)
            ->assertSessionHas('import_summary.invalid_rows', 1);
        $this->assertSame(1, Student::where('school_id', $this->school->id)->count());
        $this->assertSame(1, Enrollment::count());
    }

    public function test_email_shared_by_existing_students_is_ambiguous_and_never_creates_or_updates(): void
    {
        $first = Student::create([
            'school_id' => $this->school->id, 'first_name' => 'First', 'last_name' => 'Sibling',
            'email' => 'family@example.test', 'grade' => 'Form 2', 'class_name' => 'Old A', 'status' => 'inactive',
        ]);
        $second = Student::create([
            'school_id' => $this->school->id, 'first_name' => 'Second', 'last_name' => 'Sibling',
            'email' => 'family@example.test', 'grade' => 'Form 3', 'class_name' => 'Old B', 'status' => 'active',
        ]);
        $csv = implode("\n", [
            'student_name,email,class_name,academic_year,term,grade,status',
            'New Possible Match,family@example.test,Form 1A,2026,First Term,Form 1,active',
        ]);

        $this->actingAs($this->admin)->post(route('admin.enrollments.bulk-store'), [
            'file' => UploadedFile::fake()->createWithContent('shared-existing-email.csv', $csv),
        ])->assertSessionHas('import_summary.ambiguous_students_skipped', 1);

        $this->assertSame(2, Student::where('school_id', $this->school->id)->count());
        $this->assertSame('Form 2', $first->fresh()->getRawOriginal('grade'));
        $this->assertSame('Form 3', $second->fresh()->getRawOriginal('grade'));
        $this->assertSame(0, Enrollment::count());
    }

    public function test_another_schools_student_is_not_loaded_or_modified(): void
    {
        $otherSchool = School::create(['name' => 'Other Synthetic School', 'code' => 'OTH']);
        $otherStudent = Student::create([
            'school_id' => $otherSchool->id, 'first_name' => 'Other', 'last_name' => 'Learner',
            'email' => 'other@example.test', 'admission_number' => 'SHARED-ID', 'grade' => 'Form 4',
            'class_name' => 'Other Class', 'is_boarding' => true, 'status' => 'inactive',
        ]);

        $csv = implode("\n", [
            'student_name,email,admission_number,date_of_birth,class_name,academic_year,term,grade,is_boarding,status',
            'Local Learner,local@example.test,SHARED-ID,2010-08-01,Form 1A,2026,First Term,Form 1,no,active',
        ]);

        $this->actingAs($this->admin)->post(route('admin.enrollments.bulk-store'), [
            'file' => UploadedFile::fake()->createWithContent('tenant.csv', $csv),
        ])->assertSessionHas('import_summary.new_students_created', 1);

        $otherStudent->refresh();
        $this->assertSame('Form 4', $otherStudent->getRawOriginal('grade'));
        $this->assertSame('Other Class', $otherStudent->getRawOriginal('class_name'));
        $this->assertTrue((bool) $otherStudent->is_boarding);
        $this->assertSame(1, Student::where('school_id', $otherSchool->id)->count());
        $this->assertSame(1, Student::where('school_id', $this->school->id)->count());
    }

    public function test_template_generation_returns_an_xlsx_download(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.enrollments.template'));

        $response->assertOk();
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type')
        );

        $path = $response->baseResponse->getFile()->getPathname();
        @unlink($path);
    }
}
