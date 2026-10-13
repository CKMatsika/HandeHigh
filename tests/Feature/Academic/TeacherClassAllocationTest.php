<?php

namespace Tests\Feature\Academic;

use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherClassAllocationTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;
    protected User $teacherUser;
    protected Teacher $teacher;
    protected Subject $practicalsSubject;

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

        $this->adminUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin User',
            'email' => 'admin@school.co.zw',
            'password' => bcrypt('password123'),
        ]);
        $this->adminUser->assignRole('school-admin');

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'J ZARIRO',
            'email' => 'jzariro@school.co.zw',
            'password' => bcrypt('password123'),
        ]);
        $this->teacherUser->assignRole('teacher');

        $this->teacher = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacherUser->id,
            'employee_id' => 'TCH-001',
            'first_name' => 'J',
            'last_name' => 'ZARIRO',
            'email' => 'jzariro@school.co.zw',
            'status' => true,
        ]);

        $this->practicalsSubject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Practicals',
            'code' => 'PRAC',
        ]);
    }

    public function test_admin_can_assign_and_remove_class_teacher_administrative_role(): void
    {
        $class1A = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Form 1A',
            'grade' => '1',
            'academic_year' => '2026',
        ]);

        // Assign Class Teacher
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.teachers.class-teacher.assign', $this->teacher), [
                'class_id' => $class1A->id,
            ]);

        $response->assertRedirect(route('admin.teachers.show', ['teacher' => $this->teacher, 'tab' => 'classes']));
        $this->assertDatabaseHas('classes', [
            'id' => $class1A->id,
            'teacher_id' => $this->teacherUser->id,
        ]);

        // Remove Class Teacher
        $removeResponse = $this->actingAs($this->adminUser)
            ->post(route('admin.teachers.class-teacher.remove', [$this->teacher, $class1A]));

        $removeResponse->assertRedirect(route('admin.teachers.show', ['teacher' => $this->teacher, 'tab' => 'classes']));
        $this->assertDatabaseHas('classes', [
            'id' => $class1A->id,
            'teacher_id' => null,
        ]);
    }

    public function test_admin_can_batch_allocate_teaching_classes_to_teacher(): void
    {
        // Create 8 classes: 4 Form 1s and 4 Form 2s
        $classes = [];
        foreach (['1A', '1B', '1C', '1D'] as $name) {
            $classes[] = SchoolClass::create([
                'school_id' => $this->school->id,
                'name' => 'Form ' . $name,
                'grade' => '1',
                'academic_year' => '2026',
            ]);
        }
        foreach (['2A', '2B', '2C', '2D'] as $name) {
            $classes[] = SchoolClass::create([
                'school_id' => $this->school->id,
                'name' => 'Form ' . $name,
                'grade' => '2',
                'academic_year' => '2026',
            ]);
        }

        $classIds = collect($classes)->pluck('id')->all();

        // Assign Practicals to all 8 classes
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.teachers.teaching-assignments.store', $this->teacher), [
                'class_ids' => $classIds,
                'subject_id' => $this->practicalsSubject->id,
                'weekly_periods' => 4,
                'term' => 'Term 1',
                'academic_year' => '2026',
            ]);

        $response->assertRedirect(route('admin.teachers.show', ['teacher' => $this->teacher, 'tab' => 'classes']));

        // Verify all 8 classes have a curriculum row assigned to this teacher
        $this->assertEquals(8, Curriculum::where('school_id', $this->school->id)
            ->where('subject_id', $this->practicalsSubject->id)
            ->where('teacher_id', $this->teacherUser->id)
            ->count());

        // Verify teacher's subjects relation is synchronized
        $this->assertTrue($this->teacher->subjects()->where('subject_id', $this->practicalsSubject->id)->exists());
    }

    public function test_admin_removing_teaching_assignment_unassigns_teacher_without_destroying_curriculum(): void
    {
        $class1A = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Form 1A',
            'grade' => '1',
            'academic_year' => '2026',
        ]);

        $curriculum = Curriculum::create([
            'school_id' => $this->school->id,
            'class_id' => $class1A->id,
            'subject_id' => $this->practicalsSubject->id,
            'teacher_id' => $this->teacherUser->id,
            'academic_year' => '2026',
            'weekly_periods' => 4,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('admin.teachers.teaching-assignments.destroy', [$this->teacher, $curriculum]));

        $response->assertRedirect(route('admin.teachers.show', ['teacher' => $this->teacher, 'tab' => 'classes']));

        // The curriculum row must still exist in the database, but with teacher_id set to null
        $this->assertDatabaseHas('curricula', [
            'id' => $curriculum->id,
            'teacher_id' => null,
        ]);
    }

    public function test_teacher_portal_dashboard_displays_managed_class_and_taught_classes_separately(): void
    {
        // 1. Managed Class: Form 1A
        $class1A = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Form 1A',
            'grade' => '1',
            'academic_year' => '2026',
            'teacher_id' => $this->teacherUser->id,
        ]);

        // 2. Teaching Class only: Form 2B
        $class2B = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Form 2B',
            'grade' => '2',
            'academic_year' => '2026',
            'teacher_id' => null, // different class teacher
        ]);

        // Add curriculum for Form 2B
        Curriculum::create([
            'school_id' => $this->school->id,
            'class_id' => $class2B->id,
            'subject_id' => $this->practicalsSubject->id,
            'teacher_id' => $this->teacherUser->id,
            'academic_year' => '2026',
            'weekly_periods' => 4,
        ]);

        // Enroll a student in Form 1A
        $student1 = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Tinashe',
            'last_name' => 'Sibanda',
            'admission_number' => 'ADM-001',
            'status' => 'active',
        ]);
        Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student1->id,
            'class_id' => $class1A->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'enrollment_date' => now()->toDateString(),
        ]);

        // Enroll a student in Form 2B
        $student2 = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Chipo',
            'last_name' => 'Gumbo',
            'admission_number' => 'ADM-002',
            'status' => 'active',
        ]);
        Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student2->id,
            'class_id' => $class2B->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'enrollment_date' => now()->toDateString(),
        ]);

        // Acting as teacher on portal
        $response = $this->actingAs($this->teacherUser)
            ->get(route('teacher.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Class Managed (Administrative)');
        $response->assertSee('Form 1A');
        $response->assertSee('Classes & Subjects Taught', false);
        $response->assertSee('Form 2B');
        $response->assertSee('Practicals');
        $response->assertSee('Tinashe Sibanda');
        $response->assertSee('Chipo Gumbo');
    }
}
