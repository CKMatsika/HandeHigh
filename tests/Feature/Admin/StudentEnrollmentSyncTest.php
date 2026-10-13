<?php

namespace Tests\Feature\Admin;

use App\Imports\EnrollmentsImport;
use App\Models\Enrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentEnrollmentSyncTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;

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
        $this->adminUser->assignRole('super-admin');
    }

    public function test_student_profile_displays_grade_and_class_from_active_enrollment_when_student_columns_are_null(): void
    {
        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'North',
            'grade' => 'Form 2',
            'academic_year' => '2026',
        ]);

        // Student created without grade and class_name (simulating historical bulk import)
        $student = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Wendy',
            'last_name' => 'Zekiya',
            'email' => 'wendy@example.com',
            'gender' => 'female',
            'status' => 'active',
        ]);

        Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 2',
            'class_name' => 'North',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->get('/admin/students/' . $student->id);

        $response->assertStatus(200);
        $response->assertSee('Wendy Zekiya');
        $response->assertSee('Form 2');
        $response->assertSee('North');
        $response->assertDontSee('Ungraded');
    }

    public function test_enrollment_show_page_displays_grade_and_class_on_student_card(): void
    {
        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'North',
            'grade' => 'Form 2',
            'academic_year' => '2026',
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Letween',
            'last_name' => 'Ngwasha',
            'email' => 'letween@example.com',
            'gender' => 'female',
            'status' => 'active',
        ]);

        $enrollment = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'academic_year' => '2026',
            'term' => 'First Term',
            'grade' => '2',
            'class_name' => 'North',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->get('/admin/enrollments/' . $enrollment->id);

        $response->assertStatus(200);
        $response->assertSee('Letween Ngwasha');
        // Confirms student card has the grade/class rendered from enrollment fallback
        $response->assertSee('2 North');
    }

    public function test_moving_class_syncs_to_active_enrollment(): void
    {
        $classOld = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'South',
            'grade' => 'Form 1',
            'academic_year' => '2026',
        ]);

        $classNew = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'North',
            'grade' => 'Form 2',
            'academic_year' => '2026',
        ]);

        $student = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Tendai',
            'last_name' => 'Moyo',
            'grade' => 'Form 1',
            'class_name' => 'South',
            'status' => 'active',
        ]);

        $enrollment = Enrollment::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'class_id' => $classOld->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'grade' => 'Form 1',
            'class_name' => 'South',
            'enrollment_date' => now(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)->post('/admin/students/' . $student->id . '/move-class', [
            'grade' => 'Form 2',
            'class_name' => 'North',
            'reason' => 'Academic progression',
        ]);

        $response->assertRedirect('/admin/students/' . $student->id);

        // Verify student record updated
        $student->refresh();
        $this->assertEquals('Form 2', $student->getRawOriginal('grade'));
        $this->assertEquals('North', $student->getRawOriginal('class_name'));

        // Verify enrollment record updated and linked to new class
        $enrollment->refresh();
        $this->assertEquals('Form 2', $enrollment->grade);
        $this->assertEquals('North', $enrollment->class_name);
        $this->assertEquals($classNew->id, $enrollment->class_id);
    }

    public function test_enrollments_import_syncs_grade_and_class_to_student(): void
    {
        $class = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'North',
            'grade' => 'Form 2',
            'academic_year' => '2026',
        ]);

        $importer = new EnrollmentsImport($this->school->id);
        $enrollment = $importer->model([
            'student_name' => 'Wendy Zekiya',
            'email' => 'wendy.import@example.com',
            'phone' => '0771234567',
            'address' => 'Harare',
            'date_of_birth' => '2010-05-30',
            'gender' => 'female',
            'class_name' => 'North',
            'grade' => 'Form 2',
            'academic_year' => '2026',
            'term' => 'First Term',
            'is_boarding' => 'no',
            'has_transport' => 'no',
            'status' => 'active',
        ]);
        $enrollment->save();

        $this->assertDatabaseHas('students', [
            'school_id' => $this->school->id,
            'email' => 'wendy.import@example.com',
            'first_name' => 'Wendy',
            'last_name' => 'Zekiya',
            'grade' => 'Form 2',
            'class_name' => 'North',
        ]);

        $this->assertDatabaseHas('enrollments', [
            'school_id' => $this->school->id,
            'class_id' => $class->id,
            'grade' => 'Form 2',
            'class_name' => 'North',
        ]);
    }
}
