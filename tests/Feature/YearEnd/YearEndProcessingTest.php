<?php

namespace Tests\Feature\YearEnd;

use App\Models\School;
use App\Models\Student;
use App\Models\StudentClearance;
use App\Models\User;
use App\Models\YearEndProcess;
use App\Models\Enrollment;
use App\Services\YearEnd\YearEndProcessingService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YearEndProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        $this->school = School::create([
            'name' => 'Hande High School',
            'code' => 'HHS',
        ]);

        $this->admin = User::create([
            'school_id' => $this->school->id,
            'name' => 'Admin User',
            'email' => 'admin@handehigh.test',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $this->admin->assignRole('school-admin');
    }

    public function test_user_can_access_year_end_processes_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.year-end.index'));
        $response->assertStatus(200);
        $response->assertSee('Year-End Processes');
    }

    public function test_generate_transition_draft_correctly_maps_promotions_and_clearance_candidates(): void
    {
        // Create active students in various streams
        $f1 = Student::create(['school_id' => $this->school->id, 'first_name' => 'John', 'last_name' => 'F1', 'admission_number' => 'ADM-F1', 'grade' => 'Form 1', 'class_name' => 'Form 1A', 'status' => 'active']);
        $f2 = Student::create(['school_id' => $this->school->id, 'first_name' => 'Mary', 'last_name' => 'F2', 'admission_number' => 'ADM-F2', 'grade' => 'Form 2', 'class_name' => 'Form 2A', 'status' => 'active']);
        $f3 = Student::create(['school_id' => $this->school->id, 'first_name' => 'Peter', 'last_name' => 'F3', 'admission_number' => 'ADM-F3', 'grade' => 'Form 3', 'class_name' => 'Form 3A', 'status' => 'active']);
        $f4 = Student::create(['school_id' => $this->school->id, 'first_name' => 'Sarah', 'last_name' => 'F4', 'admission_number' => 'ADM-F4', 'grade' => 'Form 4', 'class_name' => 'Form 4A', 'status' => 'active']); // Graduating
        $f5 = Student::create(['school_id' => $this->school->id, 'first_name' => 'David', 'last_name' => 'F5', 'admission_number' => 'ADM-F5', 'grade' => 'Form 5', 'class_name' => 'Form 5 Sciences', 'status' => 'active']);
        $f6 = Student::create(['school_id' => $this->school->id, 'first_name' => 'Grace', 'last_name' => 'F6', 'admission_number' => 'ADM-F6', 'grade' => 'Form 6', 'class_name' => 'Form 6 Arts', 'status' => 'active']); // Graduating

        $response = $this->actingAs($this->admin)->post(route('admin.year-end.draft.create'), [
            'source_academic_year' => '2025',
            'target_academic_year' => '2026',
        ]);

        $process = YearEndProcess::where('school_id', $this->school->id)->first();
        $this->assertNotNull($process);
        $this->assertEquals('draft', $process->status);
        $response->assertRedirect(route('admin.year-end.draft', $process));

        $payload = collect($process->draft_payload);
        $this->assertCount(6, $payload);

        // F1 should be promoted to Form 2
        $f1Item = $payload->firstWhere('student_id', $f1->id);
        $this->assertEquals('promote', $f1Item['proposed_action']);
        $this->assertEquals('Form 2', $f1Item['target_grade']);

        // F4 should be moved to clearance hub
        $f4Item = $payload->firstWhere('student_id', $f4->id);
        $this->assertEquals('move_to_clearance', $f4Item['proposed_action']);

        // F6 should be moved to clearance hub
        $f6Item = $payload->firstWhere('student_id', $f6->id);
        $this->assertEquals('move_to_clearance', $f6Item['proposed_action']);
    }

    public function test_admin_can_customize_individual_student_in_draft(): void
    {
        $student = Student::create(['school_id' => $this->school->id, 'first_name' => 'Repeat', 'last_name' => 'Student', 'admission_number' => 'ADM-RPT', 'grade' => 'Form 2', 'class_name' => 'Form 2B', 'status' => 'active']);

        $service = app(YearEndProcessingService::class);
        $draft = $service->generateTransitionDraft($this->school, '2025', '2026', $this->admin);

        // Modify student to repeat instead of promote
        $response = $this->actingAs($this->admin)->post(route('admin.year-end.draft.update-student', $draft), [
            'student_id' => $student->id,
            'proposed_action' => 'repeat',
            'target_grade' => 'Form 2',
            'target_class_name' => 'Form 2B',
        ]);

        $response->assertSessionHas('success');
        $draft->refresh();
        $item = collect($draft->draft_payload)->firstWhere('student_id', $student->id);
        $this->assertEquals('repeat', $item['proposed_action']);
    }

    public function test_approve_and_execute_promotes_students_and_creates_clearance_records(): void
    {
        $f1 = Student::create(['school_id' => $this->school->id, 'first_name' => 'Junior', 'last_name' => 'F1', 'admission_number' => 'ADM-J1', 'grade' => 'Form 1', 'class_name' => 'Form 1A', 'status' => 'active']);
        $f4 = Student::create(['school_id' => $this->school->id, 'first_name' => 'Senior', 'last_name' => 'F4', 'admission_number' => 'ADM-S4', 'grade' => 'Form 4', 'class_name' => 'Form 4A', 'status' => 'active']);

        $service = app(YearEndProcessingService::class);
        $draft = $service->generateTransitionDraft($this->school, '2025', '2026', $this->admin);

        $response = $this->actingAs($this->admin)->post(route('admin.year-end.draft.execute', $draft));
        $response->assertRedirect(route('admin.year-end.index'));

        $draft->refresh();
        $this->assertEquals('executed', $draft->status);

        // F1 is promoted
        $f1->refresh();
        $this->assertEquals('Form 2', $f1->grade);
        $this->assertTrue(Enrollment::where('student_id', $f1->id)->where('academic_year', '2026')->exists());

        // F4 is moved to pending clearance
        $f4->refresh();
        $this->assertEquals('pending_clearance', $f4->status);

        $clearance = StudentClearance::where('student_id', $f4->id)->first();
        $this->assertNotNull($clearance);
        $this->assertEquals('Form 4', $clearance->graduation_grade);
    }

    public function test_department_clearance_approval_and_permanent_exit_finalization(): void
    {
        $student = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Graduate',
            'last_name' => 'Student',
            'admission_number' => 'ADM-GRAD',
            'grade' => 'Form 4',
            'status' => 'pending_clearance',
        ]);

        $clearance = StudentClearance::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year' => '2025',
            'graduation_grade' => 'Form 4',
            'exit_type' => 'graduated',
            'finance_status' => 'pending',
            'library_status' => 'pending',
            'assets_status' => 'pending',
            'boarding_status' => 'not_applicable',
            'status' => 'pending_clearance',
        ]);

        // Approve Finance clearance
        $this->actingAs($this->admin)->post(route('admin.year-end.clearance.clear-department', $clearance), [
            'department' => 'finance',
            'action' => 'cleared',
        ]);

        // Approve Library clearance
        $this->actingAs($this->admin)->post(route('admin.year-end.clearance.clear-department', $clearance), [
            'department' => 'library',
            'action' => 'cleared',
        ]);

        // Approve Assets clearance
        $this->actingAs($this->admin)->post(route('admin.year-end.clearance.clear-department', $clearance), [
            'department' => 'assets',
            'action' => 'cleared',
        ]);

        $clearance->refresh();
        $this->assertEquals('fully_cleared', $clearance->status);

        // Finalize permanent exit
        $response = $this->actingAs($this->admin)->post(route('admin.year-end.clearance.finalize-exit', $clearance), [
            'remarks' => 'Completed Form 4 with honors.',
        ]);

        $response->assertRedirect(route('admin.year-end.clearance.show', $clearance));

        $clearance->refresh();
        $student->refresh();

        $this->assertEquals('permanently_exited', $clearance->status);
        $this->assertNotNull($clearance->certificate_number);
        $this->assertEquals('graduated', $student->status);
        $this->assertEquals('graduated', $student->exit_type);

        // Verify Certificate View loads
        $certResponse = $this->actingAs($this->admin)->get(route('admin.year-end.clearance.certificate', $clearance));
        $certResponse->assertStatus(200);
        $certResponse->assertSee($clearance->certificate_number);
    }

    public function test_foreign_school_cannot_view_or_modify_other_school_year_end_processes(): void
    {
        $otherSchool = School::create(['name' => 'Other School', 'code' => 'OTH']);
        $foreignAdmin = User::create([
            'school_id' => $otherSchool->id,
            'name' => 'Foreign Admin',
            'email' => 'foreign@other.test',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $foreignAdmin->assignRole('school-admin');

        $process = YearEndProcess::create([
            'school_id' => $this->school->id,
            'source_academic_year' => '2025',
            'target_academic_year' => '2026',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($foreignAdmin)->get(route('admin.year-end.draft', $process));
        $response->assertStatus(403);
    }
}
