<?php

namespace Tests\Feature\Academic;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Guardian;
use App\Models\Result;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academic\AcademicReportingService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AcademicReportingTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;
    private User $adminA;
    private User $headmasterA;
    private User $deputyA;
    private User $teacherA;
    private User $adminB;
    private User $parentA;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('academic_years')) {
            Schema::create('academic_years', function ($table) {
                $table->id();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('school_classes')) {
            Schema::create('school_classes', function ($table) {
                $table->id();
                $table->timestamps();
            });
        }

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        $this->schoolA = School::create(['name' => 'Hande High School A', 'code' => 'HHSA']);
        $this->schoolB = School::create(['name' => 'Foreign School B', 'code' => 'FSB']);

        $this->adminA = $this->createUserWithRole('school-admin', $this->schoolA);
        $this->headmasterA = $this->createUserWithRole('headmaster', $this->schoolA);
        $this->deputyA = $this->createUserWithRole('deputy-headmaster', $this->schoolA);
        $this->teacherA = $this->createUserWithRole('teacher', $this->schoolA);
        $this->adminB = $this->createUserWithRole('school-admin', $this->schoolB);
        $this->parentA = $this->createUserWithRole('parent', $this->schoolA);
    }

    private function createUserWithRole(string $role, School $school): User
    {
        $user = User::create([
            'school_id' => $school->id,
            'name' => "{$role} {$school->code}",
            'email' => "{$role}.{$school->code}." . uniqid() . "@example.com",
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->assignRole($role);
        return $user;
    }

    public function test_enrollment_summary_calculations_and_demographics(): void
    {
        // Create 3 students in School A: 2 boys, 1 girl, various forms and ages
        Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Kudakwashe',
            'last_name' => 'Chiweshe',
            'admission_number' => 'ADM-001',
            'gender' => 'male',
            'grade' => 'Form 1',
            'class_name' => '1A',
            'date_of_birth' => Carbon::now()->subYears(13)->toDateString(),
            'is_boarding' => true,
            'status' => 'active',
        ]);

        Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Tatenda',
            'last_name' => 'Gumbo',
            'admission_number' => 'ADM-002',
            'gender' => 'male',
            'grade' => 'Form 1',
            'class_name' => '1B',
            'date_of_birth' => Carbon::now()->subYears(14)->toDateString(),
            'is_boarding' => false,
            'status' => 'active',
        ]);

        Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Ruvimbo',
            'last_name' => 'Moyo',
            'admission_number' => 'ADM-003',
            'gender' => 'female',
            'grade' => 'Form 2',
            'class_name' => '2A',
            'date_of_birth' => Carbon::now()->subYears(15)->toDateString(),
            'is_boarding' => true,
            'status' => 'active',
        ]);

        $service = new AcademicReportingService();
        $report = $service->getEnrollmentSummary($this->schoolA);

        $this->assertEquals(3, $report['total_enrollment']);
        $this->assertEquals(2, $report['male_total']);
        $this->assertEquals(1, $report['female_total']);
        $this->assertEquals(66.7, $report['male_percentage']);
        $this->assertEquals(33.3, $report['female_percentage']);
        $this->assertEquals(2, $report['boarding_total']);
        $this->assertEquals(1, $report['day_total']);

        // Check Form 1 aggregate
        $this->assertEquals(2, $report['by_grade']['Form 1']['total']);
        $this->assertEquals(1, $report['by_grade']['Form 2']['total']);

        // Check age demographic brackets (2 students aged 13-14, 1 aged 15-16)
        $this->assertEquals(2, $report['age_brackets']['13 - 14']);
        $this->assertEquals(1, $report['age_brackets']['15 - 16']);
    }

    public function test_enrollment_by_class_matrix(): void
    {
        $teacher = User::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Mr. Ndlovu',
            'email' => 'ndlovu@example.com',
            'password' => bcrypt('password'),
        ]);

        SchoolClass::create([
            'school_id' => $this->schoolA->id,
            'name' => '1Science',
            'grade' => 'Form 1',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'teacher_id' => $teacher->id,
        ]);

        Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Simba',
            'last_name' => 'Mutasa',
            'admission_number' => 'ADM-M1',
            'gender' => 'male',
            'grade' => 'Form 1',
            'class_name' => '1Science',
            'status' => 'active',
        ]);

        Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Nyasha',
            'last_name' => 'Marufu',
            'admission_number' => 'ADM-F1',
            'gender' => 'female',
            'grade' => 'Form 1',
            'class_name' => '1Science',
            'status' => 'active',
        ]);

        $service = new AcademicReportingService();
        $report = $service->getEnrollmentByClass($this->schoolA);

        $this->assertEquals(2, $report['grand_total']);
        $this->assertEquals(1, $report['grand_male']);
        $this->assertEquals(1, $report['grand_female']);

        $matrixRow = $report['matrix']->first();
        $this->assertEquals('Form 1', $matrixRow['grade']);
        $this->assertEquals('1Science', $matrixRow['class_name']);
        $this->assertEquals('Mr. Ndlovu', $matrixRow['class_teacher']);
        $this->assertEquals(2, $matrixRow['total']);
    }

    public function test_student_register_with_guardian_details(): void
    {
        $student = Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Chengetai',
            'last_name' => 'Mathe',
            'admission_number' => 'ADM-REG-01',
            'gender' => 'female',
            'grade' => 'Form 3',
            'class_name' => '3A',
            'date_of_birth' => '2010-05-15',
            'status' => 'active',
            'is_boarding' => true,
        ]);

        $guardian = Guardian::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Grace',
            'last_name' => 'Mathe',
            'email' => 'grace.mathe@example.com',
            'phone' => '+263771234567',
        ]);

        $student->guardians()->attach($guardian->id, ['relationship' => 'Mother', 'is_primary' => true]);

        $service = new AcademicReportingService();
        $report = $service->getStudentRegister($this->schoolA, ['grade' => 'Form 3'], 0);

        $this->assertEquals(1, $report['total_students']);
        $row = $report['rows']->first();
        $this->assertEquals('ADM-REG-01', $row['admission_number']);
        $this->assertEquals('Chengetai Mathe', $row['full_name']);
        $this->assertEquals('Grace Mathe', $row['guardian_name']);
        $this->assertEquals('+263771234567', $row['guardian_phone']);
    }

    public function test_academic_performance_summary_and_grade_distribution(): void
    {
        $ayId = DB::table('academic_years')->insertGetId(['created_at' => now(), 'updated_at' => now()]);

        $student1 = Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Munyaradzi',
            'last_name' => 'Shumba',
            'admission_number' => 'ADM-EX-1',
            'grade' => 'Form 4',
            'class_name' => '4Science',
        ]);

        $student2 = Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Fadzai',
            'last_name' => 'Dube',
            'admission_number' => 'ADM-EX-2',
            'grade' => 'Form 4',
            'class_name' => '4Science',
        ]);

        $subjectMath = Subject::create([
            'school_id' => $this->schoolA->id,
            'code' => 'MATH',
            'name' => 'Mathematics',
            'is_core' => true,
        ]);

        $class = SchoolClass::create([
            'school_id' => $this->schoolA->id,
            'name' => '4Science',
            'grade' => 'Form 4',
            'academic_year' => '2026',
            'term' => 'Term 1',
        ]);

        // Student 1: 85% (Grade A)
        Result::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $student1->id,
            'class_id' => $class->id,
            'subject_id' => $subjectMath->id,
            'academic_year' => $ayId,
            'term' => 'Term 1',
            'total_score' => 85,
            'max_total_score' => 100,
            'average' => 85,
            'grade' => 'A',
            'remarks' => 'Distinction',
        ]);

        // Student 2: 45% (Grade D - Fail)
        Result::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $student2->id,
            'class_id' => $class->id,
            'subject_id' => $subjectMath->id,
            'academic_year' => $ayId,
            'term' => 'Term 1',
            'total_score' => 45,
            'max_total_score' => 100,
            'average' => 45,
            'grade' => 'D',
            'remarks' => 'Supplementary recommended',
        ]);

        $service = new AcademicReportingService();
        $report = $service->getAcademicPerformanceSummary($this->schoolA, ['academic_year' => $ayId, 'term' => 'Term 1']);

        $this->assertEquals(2, $report['total_entries']);
        $this->assertEquals(65.0, $report['average_mark']);
        $this->assertEquals(1, $report['pass_count']);
        $this->assertEquals(1, $report['fail_count']);
        $this->assertEquals(50.0, $report['pass_rate']);

        $this->assertEquals(1, $report['grade_distribution']['A']);
        $this->assertEquals(1, $report['grade_distribution']['D']);

        $this->assertEquals(2, $report['top_performers']->count());
        $this->assertEquals('Munyaradzi Shumba', $report['top_performers']->first()['student_name']);
        $this->assertEquals(85.0, $report['top_performers']->first()['average']);
    }

    public function test_subject_performance_aggregates(): void
    {
        $ayId = DB::table('academic_years')->insertGetId(['created_at' => now(), 'updated_at' => now()]);

        $student = Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Tariro',
            'last_name' => 'Hove',
            'admission_number' => 'ADM-SUB-1',
        ]);

        $class = SchoolClass::create([
            'school_id' => $this->schoolA->id,
            'name' => '3Arts',
            'grade' => 'Form 3',
            'academic_year' => '2026',
            'term' => 'Term 1',
        ]);

        $subjectEnglish = Subject::create([
            'school_id' => $this->schoolA->id,
            'code' => 'ENG',
            'name' => 'English Language',
        ]);

        Result::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'subject_id' => $subjectEnglish->id,
            'academic_year' => $ayId,
            'term' => 'Term 1',
            'total_score' => 78,
            'max_total_score' => 100,
            'average' => 78,
            'grade' => 'A',
        ]);

        $service = new AcademicReportingService();
        $report = $service->getSubjectPerformance($this->schoolA);

        $this->assertEquals(1, $report['total_subjects']);
        $row = $report['rows']->first();
        $this->assertEquals('ENG', $row['subject_code']);
        $this->assertEquals('English Language', $row['subject_name']);
        $this->assertEquals(78.0, $row['average']);
        $this->assertEquals(100.0, $row['pass_rate']);
    }

    public function test_student_academic_profile(): void
    {
        $ayId = DB::table('academic_years')->insertGetId(['created_at' => now(), 'updated_at' => now()]);

        $student = Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Bongani',
            'last_name' => 'Mpofu',
            'admission_number' => 'ADM-PROF-01',
            'gender' => 'male',
            'grade' => 'Form 2',
            'class_name' => '2Science',
            'status' => 'active',
        ]);

        $class = SchoolClass::create([
            'school_id' => $this->schoolA->id,
            'name' => '2Science',
            'grade' => 'Form 2',
            'academic_year' => '2026',
            'term' => 'Term 1',
        ]);

        $subject = Subject::create([
            'school_id' => $this->schoolA->id,
            'code' => 'SCI',
            'name' => 'Integrated Science',
        ]);

        Result::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'academic_year' => $ayId,
            'term' => 'Term 1',
            'total_score' => 92,
            'max_total_score' => 100,
            'average' => 92,
            'grade' => 'A',
            'remarks' => 'Excellent work',
        ]);

        Attendance::create([
            'school_id' => $this->schoolA->id,
            'attendable_type' => Student::class,
            'attendable_id' => $student->id,
            'attendance_date' => '2026-08-01',
            'status' => 'present',
        ]);

        Attendance::create([
            'school_id' => $this->schoolA->id,
            'attendable_type' => Student::class,
            'attendable_id' => $student->id,
            'attendance_date' => '2026-08-02',
            'status' => 'absent',
        ]);

        $service = new AcademicReportingService();
        $profile = $service->getStudentAcademicProfile($this->schoolA, $student);

        $this->assertEquals('Bongani Mpofu', "{$profile['student']->first_name} {$profile['student']->last_name}");
        $this->assertEquals(92.0, $profile['cumulative_average']);
        $this->assertEquals(2, $profile['attendance']['total_days']);
        $this->assertEquals(1, $profile['attendance']['present']);
        $this->assertEquals(1, $profile['attendance']['absent']);
        $this->assertEquals(50.0, $profile['attendance']['attendance_rate']);
    }

    public function test_attendance_summary_and_register(): void
    {
        $student1 = Student::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Tinashe',
            'last_name' => 'Sibanda',
            'admission_number' => 'ADM-ATT-1',
            'grade' => 'Form 1',
            'class_name' => '1A',
            'gender' => 'male',
        ]);

        Attendance::create([
            'school_id' => $this->schoolA->id,
            'attendable_type' => Student::class,
            'attendable_id' => $student1->id,
            'attendance_date' => '2026-08-10',
            'status' => 'present',
            'notes' => 'On time',
        ]);

        Attendance::create([
            'school_id' => $this->schoolA->id,
            'attendable_type' => Student::class,
            'attendable_id' => $student1->id,
            'attendance_date' => '2026-08-11',
            'status' => 'late',
            'notes' => 'Transport issue',
        ]);

        Attendance::create([
            'school_id' => $this->schoolA->id,
            'attendable_type' => Student::class,
            'attendable_id' => $student1->id,
            'attendance_date' => '2026-08-12',
            'status' => 'absent',
            'notes' => 'Unexcused',
        ]);

        $service = new AcademicReportingService();

        // Summary
        $summary = $service->getAttendanceSummary($this->schoolA);
        $this->assertEquals(3, $summary['total_records']);
        $this->assertEquals(1, $summary['present_count']);
        $this->assertEquals(1, $summary['late_count']);
        $this->assertEquals(1, $summary['absent_count']);
        $this->assertEquals(66.7, $summary['overall_rate']);

        // Register
        $register = $service->getAttendanceRegister($this->schoolA, ['status' => 'present'], 0);
        $this->assertEquals(1, $register['total_records']);
        $this->assertEquals('Present', $register['rows']->first()['status']);
    }

    public function test_tenant_isolation_cross_school_shielding(): void
    {
        $ayIdB = DB::table('academic_years')->insertGetId(['created_at' => now(), 'updated_at' => now()]);

        // Create student & attendance & result in School B
        $studentB = Student::create([
            'school_id' => $this->schoolB->id,
            'first_name' => 'ForeignStudent',
            'last_name' => 'SchoolB',
            'admission_number' => 'ADM-SB-888',
            'grade' => 'Form 6',
            'class_name' => '6Upper',
        ]);

        $classB = SchoolClass::create([
            'school_id' => $this->schoolB->id,
            'name' => '6Upper',
            'grade' => 'Form 6',
            'academic_year' => '2026',
            'term' => 'Term 1',
        ]);

        $subjectB = Subject::create([
            'school_id' => $this->schoolB->id,
            'code' => 'PHYS-B',
            'name' => 'Advanced Physics',
        ]);

        Result::create([
            'school_id' => $this->schoolB->id,
            'student_id' => $studentB->id,
            'class_id' => $classB->id,
            'subject_id' => $subjectB->id,
            'academic_year' => $ayIdB,
            'term' => 'Term 1',
            'total_score' => 99,
            'max_total_score' => 100,
            'average' => 99,
            'grade' => 'A',
        ]);

        Attendance::create([
            'school_id' => $this->schoolB->id,
            'attendable_type' => Student::class,
            'attendable_id' => $studentB->id,
            'attendance_date' => '2026-08-15',
            'status' => 'present',
        ]);

        // School A Admin views Enrollment Summary
        $response = $this->actingAs($this->adminA)->get(route('admin.reports.enrollment-summary'));
        $response->assertOk();
        $response->assertDontSee('ForeignStudent');
        $response->assertDontSee('ADM-SB-888');

        // School A Admin views Academic Performance
        $perfResponse = $this->actingAs($this->adminA)->get(route('admin.reports.academic-performance'));
        $perfResponse->assertOk();
        $perfResponse->assertDontSee('PHYS-B');
        $perfResponse->assertDontSee('Advanced Physics');

        // School A Admin attempts to view School B student's academic profile -> 404
        $profileResponse = $this->actingAs($this->adminA)->get(route('admin.reports.student-academic-profile', ['student_id' => $studentB->id]));
        $profileResponse->assertNotFound();
    }

    public function test_spoofed_school_id_in_request_is_ignored(): void
    {
        $response = $this->actingAs($this->adminA)->get(route('admin.reports.enrollment-summary', [
            'school_id' => $this->schoolB->id,
        ]));

        $response->assertOk();
        $response->assertViewHas('total_enrollment');
    }

    public function test_authorized_academic_roles_can_access_reports(): void
    {
        $this->actingAs($this->adminA)->get(route('admin.reports.academic-dashboard'))->assertOk();
        $this->actingAs($this->headmasterA)->get(route('admin.reports.enrollment-summary'))->assertOk();
        $this->actingAs($this->deputyA)->get(route('admin.reports.academic-performance'))->assertOk();
    }

    public function test_unauthorized_role_is_denied(): void
    {
        // Parent role cannot access administrative academic reports
        $response = $this->actingAs($this->parentA)->get(route('admin.reports.academic-dashboard'));
        $response->assertForbidden();

        // Teacher role cannot access administrative report routes
        $teacherResponse = $this->actingAs($this->teacherA)->get(route('admin.reports.academic-dashboard'));
        $teacherResponse->assertForbidden();
    }

    public function test_excel_exports_generate_file_downloads(): void
    {
        $this->actingAs($this->adminA)->get(route('admin.reports.enrollment-summary.export'))->assertOk()->assertHeader('content-disposition');
        $this->actingAs($this->adminA)->get(route('admin.reports.student-register.export'))->assertOk()->assertHeader('content-disposition');
        $this->actingAs($this->adminA)->get(route('admin.reports.subject-performance.export'))->assertOk()->assertHeader('content-disposition');
        $this->actingAs($this->adminA)->get(route('admin.reports.attendance-register.export'))->assertOk()->assertHeader('content-disposition');
        $this->actingAs($this->adminA)->get(route('admin.reports.exam-results.export'))->assertOk()->assertHeader('content-disposition');
    }
}
