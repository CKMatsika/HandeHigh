<?php

namespace Tests\Feature\Portal;

use App\Models\Curriculum;
use App\Models\Project;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Enrollment;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $teacherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Hande High School',
            'code' => 'HHS001',
            'timezone' => 'Africa/Harare',
            'currency' => 'USD',
        ]);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Test Teacher',
            'email' => 'teacher@school.co.zw',
            'password' => bcrypt('password123'),
        ]);
        $this->teacherUser->assignRole('teacher');

        // Create a project in the database to verify the dashboard doesn't crash on projects table
        Project::create([
            'school_id' => $this->school->id,
            'code' => 'PRJ01',
            'name' => 'School Hall Project',
            'status' => 'active',
        ]);
    }

    public function test_newly_created_teacher_without_existing_teacher_record_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->teacherUser)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Teacher Dashboard');
        $response->assertSee('Test Teacher');

        // Confirms teacher profile record was automatically initialized
        $this->assertDatabaseHas('teachers', [
            'school_id' => $this->school->id,
            'user_id' => $this->teacherUser->id,
            'email' => 'teacher@school.co.zw',
        ]);
    }

    public function test_teacher_with_teacher_model_record_can_access_dashboard(): void
    {
        Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacherUser->id,
            'employee_id' => 'TCH-001',
            'first_name' => 'Test',
            'last_name' => 'Teacher',
            'email' => 'teacher@school.co.zw',
            'status' => true,
        ]);

        $response = $this->actingAs($this->teacherUser)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Teacher Dashboard');
    }

    public function test_teacher_is_redirected_from_root_dashboard_to_teacher_dashboard(): void
    {
        $response = $this->actingAs($this->teacherUser)->get('/dashboard');

        $response->assertRedirect(route('teacher.dashboard'));
    }

    public function test_teacher_dashboard_shows_managed_and_teaching_classes(): void
    {
        $managedClass = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Form 1 Blue',
            'grade' => 1,
            'academic_year' => 2026,
            'status' => true,
            'teacher_id' => $this->teacherUser->id,
        ]);

        $teachingClass = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Form 2 Gold',
            'grade' => 2,
            'academic_year' => 2026,
            'status' => true,
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Practicals',
            'code' => 'PRAC101',
        ]);

        Curriculum::create([
            'school_id' => $this->school->id,
            'class_id' => $teachingClass->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacherUser->id,
            'academic_year' => 2026,
            'weekly_periods' => 4,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->teacherUser)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Form 1 Blue');
        $response->assertSee('Form 2 Gold');
        $response->assertSee('Practicals');
    }

    public function test_sidebar_displays_teaching_menu_for_teacher_role(): void
    {
        $response = $this->actingAs($this->teacherUser)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('TEACHING');
        $response->assertSee('Teacher Dashboard');
        $response->assertSee('Schemes of Work');
        $response->assertSee('My Timetable');
    }

    public function test_teacher_with_form_two_classes_filters_students_correctly(): void
    {
        $teacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacherUser->id,
            'employee_id' => 'TCH-002',
            'first_name' => 'Sengedza',
            'last_name' => 'Teacher',
            'email' => $this->teacherUser->email,
            'status' => true,
        ]);

        $mathSubject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Mathematics',
            'code' => 'MATH101',
        ]);

        $form2Classes = [];
        $streamNames = ['Form 2A', 'Form 2B', 'Form 2C', 'Form 2D'];

        foreach ($streamNames as $stream) {
            $class = SchoolClass::create([
                'school_id' => $this->school->id,
                'name' => $stream,
                'grade' => 'Form 2',
                'academic_year' => '2026',
                'status' => true,
            ]);

            // Assign via curriculum using teacher's Teacher model ID
            Curriculum::create([
                'school_id' => $this->school->id,
                'class_id' => $class->id,
                'subject_id' => $mathSubject->id,
                'teacher_id' => $teacher->id,
                'academic_year' => '2026',
                'weekly_periods' => 5,
            ]);

            // Create student in that class using class_name directly (simulating bulk upload without enrollments row)
            Student::create([
                'school_id' => $this->school->id,
                'first_name' => 'Student',
                'last_name' => $stream,
                'class_name' => $stream,
                'grade' => 'Form 2',
                'status' => 'active',
                'admission_number' => 'ADM-' . $stream,
            ]);

            $form2Classes[] = $class;
        }

        $response = $this->actingAs($this->teacherUser)->get('/teacher/dashboard');

        $response->assertStatus(200);
        foreach ($streamNames as $stream) {
            $response->assertSee($stream);
            $response->assertSee('Student ' . $stream);
        }
        $response->assertSee('4 students');
    }

    public function test_clicking_class_filters_students_to_that_specific_class(): void
    {
        $teacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacherUser->id,
            'employee_id' => 'TCH-003',
            'first_name' => 'Sengedza',
            'last_name' => 'Teacher',
            'email' => $this->teacherUser->email,
            'status' => true,
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Mathematics',
            'code' => 'MTH',
        ]);

        $class2A = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Form 2A',
            'grade' => 'Form 2',
            'academic_year' => '2026',
        ]);
        Curriculum::create([
            'school_id' => $this->school->id,
            'class_id' => $class2A->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'academic_year' => '2026',
        ]);
        Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Farai',
            'last_name' => 'Moyo',
            'class_name' => 'Form 2A',
            'status' => 'active',
        ]);

        $class2B = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Form 2B',
            'grade' => 'Form 2',
            'academic_year' => '2026',
        ]);
        Curriculum::create([
            'school_id' => $this->school->id,
            'class_id' => $class2B->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'academic_year' => '2026',
        ]);
        Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Kudzi',
            'last_name' => 'Nyathi',
            'class_name' => 'Form 2B',
            'status' => 'active',
        ]);

        // Filter strictly to Form 2A
        $response = $this->actingAs($this->teacherUser)->get('/teacher/dashboard?class_id=' . $class2A->id);

        $response->assertStatus(200);
        $response->assertSee('Students in Form 2A');
        $response->assertSee('Farai Moyo');
        $response->assertDontSee('Kudzi Nyathi');
    }

    public function test_teacher_can_view_dedicated_class_roster_page(): void
    {
        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Form 2A',
            'grade' => 'Form 2',
            'academic_year' => '2026',
            'teacher_id' => $this->teacherUser->id,
        ]);

        Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Tendai',
            'last_name' => 'Sibanda',
            'class_name' => 'Form 2A',
            'status' => 'active',
            'admission_number' => 'ADM-2026-099',
        ]);

        $response = $this->actingAs($this->teacherUser)->get('/teacher/classes/' . $class->id);

        $response->assertStatus(200);
        $response->assertSee('Form 2A');
        $response->assertSee('Tendai Sibanda');
        $response->assertSee('ADM-2026-099');
        $response->assertSee('Print Roster');
    }

    public function test_teacher_dashboard_safely_loads_when_timetables_exist(): void
    {
        $timetable = Timetable::create([
            'school_id' => $this->school->id,
            'name' => 'Term 1 Main Timetable',
            'academic_year' => '2026',
            'term' => '1',
            'status' => 'published',
            'is_operational' => true,
        ]);

        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Form 2A',
            'grade' => 'Form 2',
            'academic_year' => '2026',
        ]);

        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Mathematics',
            'code' => 'MTH',
        ]);

        $teacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacherUser->id,
            'employee_id' => 'TCH-009',
            'first_name' => 'Test',
            'last_name' => 'Teacher',
            'email' => $this->teacherUser->email,
            'status' => true,
        ]);

        TimetableSlot::create([
            'timetable_id' => $timetable->id,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'day_of_week' => strtolower(now()->englishDayOfWeek),
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $response = $this->actingAs($this->teacherUser)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Teacher Dashboard');
        $response->assertSee('Mathematics');
        $response->assertSee('Form 2A');
    }

    public function test_teacher_dashboard_does_not_mix_streams_from_other_grades(): void
    {
        // Class 1: Form 1 North (managed or taught by another teacher)
        $form1North = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'North',
            'grade' => 'Form 1',
            'academic_year' => '2026',
        ]);

        // Class 2: Form 2 North (assigned to this teacher)
        $form2North = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'North',
            'grade' => 'Form 2',
            'academic_year' => '2026',
            'teacher_id' => $this->teacherUser->id,
        ]);

        // Student A: Form 1 North
        $studentForm1 = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'FormOne',
            'last_name' => 'StudentNorth',
            'grade' => 'Form 1',
            'class_name' => 'North',
            'status' => 'active',
        ]);
        Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $studentForm1->id,
            'class_id' => $form1North->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'class_name' => 'North',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        // Student B: Form 2 North
        $studentForm2 = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'FormTwo',
            'last_name' => 'StudentNorth',
            'grade' => 'Form 2',
            'class_name' => 'North',
            'status' => 'active',
        ]);
        Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $studentForm2->id,
            'class_id' => $form2North->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'class_name' => 'North',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        // Teacher dashboard should only show FormTwo StudentNorth and NOT FormOne StudentNorth!
        $response = $this->actingAs($this->teacherUser)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('FormTwo StudentNorth');
        $response->assertDontSee('FormOne StudentNorth');

        // Dedicated class roster for Form 2 North
        $rosterResponse = $this->actingAs($this->teacherUser)->get('/teacher/classes/' . $form2North->id);
        $rosterResponse->assertStatus(200);
        $rosterResponse->assertSee('FormTwo StudentNorth');
        $rosterResponse->assertDontSee('FormOne StudentNorth');
    }
}
