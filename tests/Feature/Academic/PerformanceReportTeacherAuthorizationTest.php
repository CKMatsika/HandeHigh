<?php

namespace Tests\Feature\Academic;

use App\Models\Curriculum;
use App\Models\PerformanceReport;
use App\Models\PerformanceReportSubject;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\GradeSchemeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceReportTeacherAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $mathTeacherUser;
    protected User $englishTeacherUser;
    protected SchoolClass $class2A;
    protected SchoolClass $class2B;
    protected Subject $mathSubject;
    protected Subject $englishSubject;
    protected Student $studentJohn;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(GradeSchemeSeeder::class);

        $this->school = School::create([
            'name' => 'Hande High School',
            'code' => 'HHS001',
            'timezone' => 'Africa/Harare',
            'currency' => 'USD',
        ]);

        // Mathematics Teacher
        $this->mathTeacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Mr. Math Moyo',
            'email' => 'math@school.co.zw',
            'password' => bcrypt('password123'),
        ]);
        $this->mathTeacherUser->assignRole('teacher');

        // English Teacher
        $this->englishTeacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Mrs. English Dube',
            'email' => 'english@school.co.zw',
            'password' => bcrypt('password123'),
        ]);
        $this->englishTeacherUser->assignRole('teacher');

        // Classes
        $this->class2A = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Form 2A',
            'grade' => 'Form 2',
            'academic_year' => '2026',
            'term' => 'Term 1',
        ]);

        $this->class2B = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Form 2B',
            'grade' => 'Form 2',
            'academic_year' => '2026',
            'term' => 'Term 1',
        ]);

        // Subjects
        $this->mathSubject = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'MATH',
            'name' => 'Mathematics',
            'is_core' => true,
        ]);

        $this->englishSubject = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'ENG',
            'name' => 'English Language',
            'is_core' => true,
        ]);

        // Student
        $this->studentJohn = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'John',
            'last_name' => 'Moyo',
            'admission_number' => 'HH2026001',
            'class_name' => 'Form 2A',
            'grade' => 'Form 2',
        ]);

        // Authoritative Teaching Assignments via Curriculum:
        // Math Teacher teaches Math to Form 2A
        Curriculum::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class2A->id,
            'subject_id' => $this->mathSubject->id,
            'teacher_id' => $this->mathTeacherUser->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
        ]);

        // English Teacher teaches English to Form 2A
        Curriculum::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class2A->id,
            'subject_id' => $this->englishSubject->id,
            'teacher_id' => $this->englishTeacherUser->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
        ]);
    }

    public function test_math_teacher_can_access_assigned_math_entry_screen(): void
    {
        $response = $this->actingAs($this->mathTeacherUser)
            ->get(route('admin.exams.performance-reports.teacher-entry', [
                $this->class2A->id,
                $this->mathSubject->id,
                'academic_year' => '2026',
                'term' => 'Term 1',
            ]));

        $response->assertOk()
            ->assertSee('Mathematics — Form 2A')
            ->assertSee('John Moyo');
    }

    public function test_math_teacher_cannot_access_english_entry_screen(): void
    {
        // Math teacher attempts to access English entry screen
        $response = $this->actingAs($this->mathTeacherUser)
            ->get(route('admin.exams.performance-reports.teacher-entry', [
                $this->class2A->id,
                $this->englishSubject->id,
                'academic_year' => '2026',
                'term' => 'Term 1',
            ]));

        $response->assertForbidden();
    }

    public function test_math_teacher_cannot_access_unassigned_class_entry_screen(): void
    {
        // Math teacher attempts to access Form 2B Math where they have no allocation
        $response = $this->actingAs($this->mathTeacherUser)
            ->get(route('admin.exams.performance-reports.teacher-entry', [
                $this->class2B->id,
                $this->mathSubject->id,
                'academic_year' => '2026',
                'term' => 'Term 1',
            ]));

        $response->assertForbidden();
    }

    public function test_math_teacher_cannot_save_or_modify_english_subject_item(): void
    {
        $report = PerformanceReport::create([
            'school_id' => $this->school->id,
            'student_id' => $this->studentJohn->id,
            'school_class_id' => $this->class2A->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'status' => 'AVAILABLE_FOR_TEACHERS',
            'version' => '1.0',
        ]);

        $englishSubjectItem = PerformanceReportSubject::create([
            'performance_report_id' => $report->id,
            'school_id' => $this->school->id,
            'student_id' => $this->studentJohn->id,
            'subject_id' => $this->englishSubject->id,
            'assigned_teacher_id' => $this->englishTeacherUser->id,
            'status' => 'not_started',
        ]);

        // Math teacher attempts to post an update to English subject item
        $response = $this->actingAs($this->mathTeacherUser)
            ->post(route('admin.exams.performance-reports.save-subject', $englishSubjectItem->id), [
                'mark_obtained' => 88,
                'max_mark' => 100,
                'result_status' => 'present',
                'comment' => 'Malicious overwrite attempt',
                'action' => 'mark_complete',
            ]);

        $response->assertForbidden();
        $this->assertNull($englishSubjectItem->fresh()->mark_obtained);
    }
}
