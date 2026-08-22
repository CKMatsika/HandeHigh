<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        Role::create(['name' => 'school-admin', 'guard_name' => 'web']);
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
            ->assertRedirectToRoute('admin.enrollments.index');

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

    public function test_valid_xlsx_import_is_supported(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['student_name', 'email', 'class_name', 'academic_year', 'term', 'grade', 'is_boarding', 'has_transport', 'status'],
            ['Synthetic XLSX Learner', 'xlsx@example.test', 'Form 1A', '2026-2027', 'First Term', 'Form 1', 'no', 'no', 'active'],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'hande-xlsx-');
        (new Xlsx($spreadsheet))->save($path);

        try {
            $this->actingAs($this->admin)
                ->post(route('admin.enrollments.bulk-store'), [
                    'file' => new UploadedFile($path, 'enrollments.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
                ])
                ->assertRedirectToRoute('admin.enrollments.index');
        } finally {
            @unlink($path);
        }

        $this->assertDatabaseHas('students', [
            'school_id' => $this->school->id,
            'email' => 'xlsx@example.test',
        ]);
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

    public function test_failed_import_rolls_back_records(): void
    {
        $csv = implode("\n", [
            'student_name,email,class_name,academic_year,term,grade,is_boarding,has_transport,status',
            'First Learner,first@example.test,Form 1A,2026,First Term,Form 1,no,no,active',
            'Second Learner,second@example.test,Missing Class,2026,First Term,Form 1,no,no,active',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.bulk-store'), [
                'file' => UploadedFile::fake()->createWithContent('enrollments.csv', $csv),
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('students', ['email' => 'first@example.test']);
        $this->assertDatabaseMissing('students', ['email' => 'second@example.test']);
        $this->assertSame(0, Enrollment::count());
    }

    public function test_template_generation_returns_an_xlsx_download(): void
    {
        Storage::fake('local');
        $template = storage_path('app/templates/bulk-enrollment-template.xlsx');
        @unlink($template);

        try {
            $response = $this->actingAs($this->admin)
                ->get(route('admin.enrollments.template'));

            $response->assertOk();
            $this->assertSame(
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                $response->headers->get('content-type')
            );
        } finally {
            @unlink($template);
        }
    }
}
