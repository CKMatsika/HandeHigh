<?php

namespace Tests\Feature\Ict;

use App\Models\IctLabItem;
use App\Models\IctTicket;
use App\Models\Project;
use App\Models\School;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IctCoordinatorTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $coordinatorUser;
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

        $this->coordinatorUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Mr. Sengedza',
            'email' => 'sengedza@gmail.com',
            'password' => bcrypt('password123'),
        ]);
        $this->coordinatorUser->assignRole(['teacher', 'ict-coordinator']);

        $this->teacherUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Ms. Moyo',
            'email' => 'moyo@school.co.zw',
            'password' => bcrypt('password123'),
        ]);
        $this->teacherUser->assignRole('teacher');
    }

    public function test_ict_coordinator_can_access_dashboard_and_see_kpis(): void
    {
        $response = $this->actingAs($this->coordinatorUser)->get('/ict/dashboard');

        $response->assertStatus(200);
        $response->assertSee('ICT Coordination');
        $response->assertSee('Mr. Sengedza');
        $response->assertSee('Open Tickets');
        $response->assertSee('Computer Lab Readiness');
    }

    public function test_non_coordinator_teacher_cannot_access_ict_dashboard(): void
    {
        $response = $this->actingAs($this->teacherUser)->get('/ict/dashboard');

        $response->assertStatus(403);
    }

    public function test_teacher_can_submit_ict_support_ticket(): void
    {
        $response = $this->actingAs($this->teacherUser)->post('/ict/tickets', [
            'title' => 'Cannot log into Teacher Portal on Lab PC 4',
            'description' => 'The screen says credentials incorrect even though password is verified.',
            'category' => 'login_account',
            'priority' => 'high',
        ]);

        $response->assertRedirect('/ict/tickets/my-tickets');
        $this->assertDatabaseHas('ict_tickets', [
            'school_id' => $this->school->id,
            'user_id' => $this->teacherUser->id,
            'title' => 'Cannot log into Teacher Portal on Lab PC 4',
            'category' => 'login_account',
            'priority' => 'high',
            'status' => 'open',
        ]);
    }

    public function test_teacher_can_view_their_submitted_tickets(): void
    {
        $ticket = IctTicket::create([
            'school_id' => $this->school->id,
            'ticket_number' => 'ICT-2026-0001',
            'user_id' => $this->teacherUser->id,
            'title' => 'Printer jammed in staff room',
            'description' => 'Tray 2 paper jam error.',
            'category' => 'printer_scanner',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->teacherUser)->get('/ict/tickets/my-tickets');

        $response->assertStatus(200);
        $response->assertSee('ICT-2026-0001');
        $response->assertSee('Printer jammed in staff room');
    }

    public function test_ict_coordinator_can_update_ticket_status_and_assign_technician(): void
    {
        $ticket = IctTicket::create([
            'school_id' => $this->school->id,
            'ticket_number' => 'ICT-2026-0002',
            'user_id' => $this->teacherUser->id,
            'title' => 'Network drop in Computer Lab 1',
            'description' => 'Switch port 8 disconnected.',
            'category' => 'network_wifi',
            'priority' => 'urgent',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->coordinatorUser)->put("/ict/tickets/{$ticket->id}/status", [
            'status' => 'resolved',
            'assigned_to' => $this->coordinatorUser->id,
            'resolution_notes' => 'Replaced patch cable and restarted switch port. Network fully restored.',
        ]);

        $response->assertSessionHas('status');
        $ticket->refresh();

        $this->assertEquals('resolved', $ticket->status);
        $this->assertEquals($this->coordinatorUser->id, $ticket->assigned_to);
        $this->assertNotNull($ticket->resolved_at);
        $this->assertStringContainsString('Replaced patch cable', $ticket->resolution_notes);
    }

    public function test_ict_coordinator_can_view_and_register_lab_devices(): void
    {
        $response = $this->actingAs($this->coordinatorUser)->post('/ict/devices', [
            'device_name' => 'Student Workstation PC #10',
            'device_code' => 'LAB1-PC10',
            'location' => 'Computer Lab 1',
            'condition_status' => 'working',
            'notes' => 'Core i5, 8GB RAM',
        ]);

        $response->assertRedirect('/ict/devices');
        $this->assertDatabaseHas('ict_lab_items', [
            'school_id' => $this->school->id,
            'device_name' => 'Student Workstation PC #10',
            'device_code' => 'LAB1-PC10',
            'condition_status' => 'working',
        ]);
    }

    public function test_ict_coordinator_can_update_device_condition(): void
    {
        $device = IctLabItem::create([
            'school_id' => $this->school->id,
            'device_name' => 'Projector Room 4',
            'location' => 'Room 4',
            'condition_status' => 'working',
        ]);

        $response = $this->actingAs($this->coordinatorUser)->put("/ict/devices/{$device->id}/condition", [
            'condition_status' => 'faulty',
        ]);

        $response->assertSessionHas('status');
        $device->refresh();

        $this->assertEquals('faulty', $device->condition_status);
        $this->assertEquals($this->coordinatorUser->id, $device->last_checked_by);
    }

    public function test_teacher_with_ict_coordinator_role_sees_banner_on_teacher_dashboard(): void
    {
        $response = $this->actingAs($this->coordinatorUser)->get('/teacher/dashboard');

        $response->assertStatus(200);
        $response->assertSee('ICT Leadership Role');
        $response->assertSee('School ERP Team Leader');
        $response->assertSee('Open Coordinator Desk');
    }
}
