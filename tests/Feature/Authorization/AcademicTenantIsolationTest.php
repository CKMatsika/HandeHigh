<?php

namespace Tests\Feature\Authorization;

use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Guardian;
use App\Models\SchoolHouse;
use App\Models\Subject;
use App\Models\SchoolClass;
use App\Models\Curriculum;
use App\Models\StaffPosition;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AcademicTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;
    private School $schoolB;

    protected function setUp(): void
    {
        parent::setUp();

        if (! \Illuminate\Support\Facades\Schema::hasTable('academic_years')) {
            \Illuminate\Support\Facades\Schema::create('academic_years', function ($table) {
                $table->id();
                $table->timestamps();
            });
        }

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        $this->schoolA = School::create(['name' => 'Academic School A', 'code' => 'ACA']);
        $this->schoolB = School::create(['name' => 'Academic School B', 'code' => 'ACB']);
    }

    private function userWithRole(string $role, ?School $school): User
    {
        $user = User::factory()->create(['school_id' => $school?->id]);
        $user->assignRole(Role::findByName($role, 'web'));
        return $user;
    }

    public function test_student_creation_rejects_foreign_guardians_and_rooms(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);

        $guardianB = Guardian::create([
            'school_id' => $this->schoolB->id,
            'first_name' => 'Foreign',
            'last_name' => 'Guardian',
            'email' => 'guardian@b.test',
        ]);

        $houseB = SchoolHouse::create([
            'school_id' => $this->schoolB->id,
            'name' => 'House B',
            'color' => 'Red',
            'is_active' => true,
        ]);

        $this->actingAs($adminA)
            ->post(route('admin.students.store'), [
                'first_name' => 'New',
                'last_name' => 'Student',
                'gender' => 'male',
                'date_of_birth' => '2010-01-01',
                'guardian_ids' => [$guardianB->id],
            ])
            ->assertSessionHasErrors('guardian_ids.0');

        $this->actingAs($adminA)
            ->post(route('admin.students.store'), [
                'first_name' => 'New',
                'last_name' => 'Student',
                'gender' => 'male',
                'date_of_birth' => '2010-01-01',
                'is_boarding' => true,
                'house_id' => $houseB->id,
            ])
            ->assertSessionHasErrors('house_id');
    }

    public function test_teacher_assignment_rejects_foreign_subjects_and_positions(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);

        $subjectB = Subject::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Math B',
            'code' => 'MTHB',
        ]);

        $positionB = StaffPosition::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Head of Math B',
            'slug' => 'head-of-math-b',
        ]);

        $classB = SchoolClass::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Class B',
            'grade' => '10',
            'academic_year' => '2026-2027',
            'term' => 'First Term',
        ]);

        $teacherA = Teacher::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Teacher',
            'last_name' => 'A',
            'email' => 'teacherA@test.com',
            'employee_id' => 'EMP-1234',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($adminA)
            ->post(route('admin.teachers.subjects.assign', $teacherA), [
                'subjects' => [$subjectB->id],
            ])
            ->assertSessionHasErrors('subjects.0');

        $this->actingAs($adminA)
            ->post(route('admin.teachers.roles.store', $teacherA), [
                'staff_position_id' => $positionB->id,
            ])
            ->assertSessionHasErrors('staff_position_id');

        $this->actingAs($adminA)
            ->post(route('admin.teachers.class-teacher.assign', $teacherA), [
                'class_id' => $classB->id,
            ])
            ->assertSessionHasErrors('class_id');
    }

    public function test_curriculum_creation_rejects_foreign_classes_and_subjects(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);
        
        $classB = SchoolClass::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Class B',
            'grade' => '10',
            'academic_year' => '2026-2027',
            'term' => 'First Term',
        ]);

        $subjectB = Subject::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Math B',
            'code' => 'MTHB',
        ]);

        $this->actingAs($adminA)
            ->post(route('admin.curricula.store'), [
                'class_id' => $classB->id,
                'subject_id' => $subjectB->id,
                'academic_year' => '2026-2027',
                'weekly_periods' => 5,
            ])
            ->assertSessionHasErrors(['class_id', 'subject_id']);
    }

    public function test_attendance_marking_rejects_foreign_students(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);

        $studentB = Student::create([
            'school_id' => $this->schoolB->id,
            'first_name' => 'Student',
            'last_name' => 'B',
        ]);

        $classA = SchoolClass::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Class A',
            'grade' => '10',
            'academic_year' => '2026-2027',
            'term' => 'First Term',
        ]);

        $this->actingAs($adminA)
            ->post(route('admin.attendance.student.store'), [
                'student_id' => $studentB->id,
                'attendance_date' => '2026-08-01',
                'status' => 'present',
            ])
            ->assertSessionHasErrors('student_id');
    }
}
