<?php

namespace Tests\Feature\Authorization;

use App\Models\CareerPath;
use App\Models\Department;
use App\Models\Employee;
use App\Models\School;
use App\Models\SdaCommittee;
use App\Models\SdaMeeting;
use App\Models\SdaResolution;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GeneralTenantIsolationTest extends TestCase
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

        $this->schoolA = School::create(['name' => 'General School A', 'code' => 'GSA']);
        $this->schoolB = School::create(['name' => 'General School B', 'code' => 'GSB']);
    }

    private function userWithRole(string $role, ?School $school): User
    {
        $user = User::factory()->create(['school_id' => $school?->id]);
        $user->assignRole(Role::findByName($role, 'web'));
        return $user;
    }

    public function test_employee_creation_and_update_rejects_foreign_department(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);

        $deptB = Department::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Foreign Department B',
            'code' => 'DEPTB',
        ]);

        $deptA = Department::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Department A',
            'code' => 'DEPTA',
        ]);

        $this->actingAs($adminA)
            ->post(route('admin.employees.store'), [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@a.test',
                'phone' => '1234567890',
                'employee_id' => 'EMP-001',
                'department_id' => $deptB->id,
                'position' => 'Clerk',
                'employment_type' => 'full_time',
                'employment_status' => 'active',
                'date_of_birth' => '1990-01-01',
                'hire_date' => '2025-01-01',
                'salary' => 2000,
            ])
            ->assertSessionHasErrors('department_id');

        $empA = Employee::create([
            'school_id' => $this->schoolA->id,
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'email' => 'alice.smith@a.test',
            'phone' => '1234567891',
            'employee_id' => 'EMP-002',
            'department_id' => $deptA->id,
            'position' => 'Officer',
            'employment_type' => 'full_time',
            'employment_status' => 'active',
            'date_of_birth' => '1992-02-02',
            'hire_date' => '2025-01-01',
            'salary' => 2500,
        ]);

        $this->actingAs($adminA)
            ->put(route('admin.employees.update', $empA), [
                'first_name' => 'Alice',
                'last_name' => 'Smith',
                'email' => 'alice.smith@a.test',
                'phone' => '1234567891',
                'employee_id' => 'EMP-002',
                'department_id' => $deptB->id,
                'position' => 'Officer',
                'employment_type' => 'full_time',
                'employment_status' => 'active',
                'date_of_birth' => '1992-02-02',
                'hire_date' => '2025-01-01',
                'salary' => 2500,
            ])
            ->assertSessionHasErrors('department_id');
    }

    public function test_leave_creation_rejects_foreign_employee(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);

        $deptB = Department::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Department B',
            'code' => 'DEPTB',
        ]);

        $empB = Employee::create([
            'school_id' => $this->schoolB->id,
            'first_name' => 'Bob',
            'last_name' => 'Brown',
            'email' => 'bob.brown@b.test',
            'phone' => '1234567892',
            'employee_id' => 'EMP-B01',
            'department_id' => $deptB->id,
            'position' => 'Staff',
            'employment_type' => 'full_time',
            'employment_status' => 'active',
            'date_of_birth' => '1988-03-03',
            'hire_date' => '2024-01-01',
            'salary' => 1800,
        ]);

        $this->actingAs($adminA)
            ->post(route('admin.leaves.store'), [
                'employee_id' => $empB->id,
                'leave_type' => 'annual',
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-05',
                'reason' => 'Holiday',
            ])
            ->assertSessionHasErrors('employee_id');
    }

    public function test_career_guidance_rejects_foreign_student_and_subjects(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);

        $studentB = Student::create([
            'school_id' => $this->schoolB->id,
            'first_name' => 'Foreign',
            'last_name' => 'Student',
        ]);

        $subjectB = Subject::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Foreign Subject',
            'code' => 'FSUB',
        ]);

        $this->actingAs($adminA)
            ->post(route('admin.career-guidance.assess'), [
                'student_id' => $studentB->id,
            ])
            ->assertSessionHasErrors('student_id');

        $this->actingAs($adminA)
            ->post(route('admin.career-guidance.paths.store'), [
                'name' => 'Engineering Path',
                'subject_requirements' => [
                    [
                        'subject_id' => $subjectB->id,
                        'subject_name' => 'Foreign Subject',
                        'min_score' => 75,
                    ],
                ],
            ])
            ->assertSessionHasErrors('subject_requirements.0.subject_id');
    }

    public function test_sda_rejects_foreign_users(): void
    {
        $adminA = $this->userWithRole('school-admin', $this->schoolA);
        $userB = $this->userWithRole('teacher', $this->schoolB);

        $committee = SdaCommittee::first();

        $this->actingAs($adminA)
            ->post(route('admin.sda.tasks.store'), [
                'sda_committee_id' => $committee->id,
                'assigned_to' => $userB->id,
                'title' => 'Cross tenant task',
                'description' => 'Should fail',
                'priority' => 'high',
                'due_date' => now()->addDays(5)->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('assigned_to');
    }
}
