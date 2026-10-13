<?php

namespace Tests\Feature\Portal;

use App\Models\Curriculum;
use App\Models\Project;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
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
}
