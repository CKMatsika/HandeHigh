<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectImplementationTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;
    protected User $teacherLead;
    protected Project $project;

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
            'name' => 'Headmaster Admin',
            'email' => 'admin@school.co.zw',
            'password' => bcrypt('password123'),
        ]);
        $this->adminUser->assignRole('school-admin');

        $this->teacherLead = User::create([
            'school_id' => $this->school->id,
            'name' => 'Mr. Sengedza',
            'email' => 'sengedza@gmail.com',
            'password' => bcrypt('password123'),
        ]);
        $this->teacherLead->assignRole(['teacher', 'ict-coordinator']);

        $this->project = Project::create([
            'school_id' => $this->school->id,
            'code' => 'PRJ-ERP-2026',
            'name' => 'School ERP Implementation Project',
            'description' => 'Comprehensive rollout of School ERP system.',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'budget_amount' => 5000.00,
            'project_type' => 'technology',
            'status' => 'active',
        ]);
    }

    public function test_can_view_project_show_page_with_roadmap_and_tasks_tab(): void
    {
        $task = ProjectTask::create([
            'school_id' => $this->school->id,
            'project_id' => $this->project->id,
            'title' => 'Form 2 Math Class Teacher Allocations',
            'phase_milestone' => 'Phase 1: Academic & Subject Allocations',
            'priority' => 'urgent',
            'status' => 'pending',
            'assigned_to' => $this->teacherLead->id,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get("/admin/projects/{$this->project->id}?tab=tasks");

        $response->assertStatus(200);
        $response->assertSee('School ERP Implementation Project');
        $response->assertSee('Implementation Roadmap');
        $response->assertSee('Form 2 Math Class Teacher Allocations');
        $response->assertSee('Academic & Subject Allocations');
        $response->assertSee('Mr. Sengedza');
    }

    public function test_can_create_project_implementation_task(): void
    {
        $response = $this->actingAs($this->adminUser)->post("/admin/projects/{$this->project->id}/tasks", [
            'title' => 'Staff Training on Teacher Portal Attendance',
            'phase_milestone' => 'Phase 4: Staff Training & Go-Live',
            'priority' => 'high',
            'assigned_to' => $this->teacherLead->id,
            'due_date' => now()->addWeeks(2)->format('Y-m-d'),
            'description' => 'Conduct lab workshop for all Form 1 and Form 2 class teachers.',
        ]);

        $response->assertRedirect("/admin/projects/{$this->project->id}?tab=tasks");
        $this->assertDatabaseHas('project_tasks', [
            'school_id' => $this->school->id,
            'project_id' => $this->project->id,
            'title' => 'Staff Training on Teacher Portal Attendance',
            'phase_milestone' => 'Phase 4: Staff Training & Go-Live',
            'priority' => 'high',
            'assigned_to' => $this->teacherLead->id,
            'status' => 'pending',
        ]);
    }

    public function test_can_toggle_task_completion_status(): void
    {
        $task = ProjectTask::create([
            'school_id' => $this->school->id,
            'project_id' => $this->project->id,
            'title' => 'Configure Fee Structures and Currency Settings',
            'phase_milestone' => 'Phase 3: Finance & Clearances',
            'priority' => 'high',
            'status' => 'pending',
            'assigned_to' => $this->teacherLead->id,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->patch("/admin/projects/{$this->project->id}/tasks/{$task->id}/toggle");

        $response->assertSessionHas('status');
        $task->refresh();

        $this->assertEquals('completed', $task->status);
        $this->assertEquals(100, $task->progress_percent);
        $this->assertNotNull($task->completed_at);
    }

    public function test_can_delete_project_task(): void
    {
        $task = ProjectTask::create([
            'school_id' => $this->school->id,
            'project_id' => $this->project->id,
            'title' => 'Temporary Task To Be Deleted',
            'phase_milestone' => 'Phase 1: Academic & Subject Allocations',
            'priority' => 'low',
            'status' => 'pending',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->delete("/admin/projects/{$this->project->id}/tasks/{$task->id}");

        $response->assertRedirect("/admin/projects/{$this->project->id}?tab=tasks");
        $this->assertDatabaseMissing('project_tasks', [
            'id' => $task->id,
        ]);
    }
}
