<?php

namespace Tests\Feature\Timetable;

use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolPeriod;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\Timetable\Operations\TimetableOperationalChangeService;
use App\Services\Timetable\Substitution\SubstitutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TimetableNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;
    protected User $teacher1User;
    protected User $teacher2User;
    protected Teacher $teacher1;
    protected Teacher $teacher2;
    protected SchoolClass $class;
    protected Subject $subject;
    protected Room $room;
    protected SchoolPeriod $period;
    protected Timetable $timetable;
    protected TimetableSlot $slot;

    protected function setUp(): void
    {
        parent::setUp();

        (new \Database\Seeders\RoleSeeder)->run();
        (new \Database\Seeders\RolePermissionSeeder)->run();

        $this->school = School::create([
            'name' => 'Harare High School',
            'code' => 'HARARE',
            'email' => 'admin@hararehigh.ac.zw',
        ]);

        $this->adminUser = User::factory()->create(['school_id' => $this->school->id]);
        $this->adminUser->assignRole('school-admin');

        $this->teacher1User = User::factory()->create(['school_id' => $this->school->id]);
        $this->teacher1User->assignRole('teacher');
        $this->teacher1 = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacher1User->id,
            'employee_id' => 'EMP-NOT-01',
            'first_name' => 'Teacher',
            'last_name' => 'Alpha',
            'email' => 'alpha@school.com',
            'status' => true,
        ]);

        $this->teacher2User = User::factory()->create(['school_id' => $this->school->id]);
        $this->teacher2User->assignRole('teacher');
        $this->teacher2 = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $this->teacher2User->id,
            'employee_id' => 'EMP-NOT-02',
            'first_name' => 'Teacher',
            'last_name' => 'Beta',
            'email' => 'beta@school.com',
            'status' => true,
        ]);

        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Form 1A', 'grade' => 'Form 1', 'academic_year' => '2026', 'term' => 'Term 1']);
        $this->subject = Subject::create(['school_id' => $this->school->id, 'name' => 'Biology', 'code' => 'BIO']);
        $this->room = Room::create(['school_id' => $this->school->id, 'name' => 'Lab 1', 'code' => 'L1', 'type' => 'lab', 'capacity' => 40, 'is_active' => true]);

        $this->period = SchoolPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Period 1',
            'period_sequence' => 1,
            'period_type' => 'lesson',
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'is_active' => true,
        ]);

        $this->timetable = Timetable::create([
            'school_id' => $this->school->id,
            'name' => 'Timetable 2026',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'status' => 'published',
            'revision' => 1,
            'is_operational' => true,
        ]);

        $this->slot = TimetableSlot::create([
            'timetable_id' => $this->timetable->id,
            'school_class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher1->id,
            'room_id' => $this->room->id,
            'school_period_id' => $this->period->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'status' => 'scheduled',
        ]);
    }

    public function test_teacher_change_creates_in_app_notification(): void
    {
        $changeService = app(TimetableOperationalChangeService::class);
        $changeService->changeTeacher(
            timetable: $this->timetable,
            slot: $this->slot,
            newTeacher: $this->teacher2,
            reason: 'Staff illness',
            actor: $this->adminUser
        );

        // Teacher 2 should receive notification
        $this->assertEquals(1, $this->teacher2User->unreadNotifications()->count());
        $notification = $this->teacher2User->unreadNotifications()->first();
        $this->assertEquals('Teacher Changed', $notification->data['title']);
    }

    public function test_substitute_assignment_creates_targeted_notification(): void
    {
        $subService = app(SubstitutionService::class);
        $pending = $subService->createPendingSubstitution(
            slot: $this->slot,
            substituteTeacher: $this->teacher2,
            date: '2026-08-24',
            reason: 'Medical cover'
        );

        $subService->approveSubstitution($pending, $this->adminUser);

        // Substitute teacher should receive notification
        $this->assertEquals(1, $this->teacher2User->unreadNotifications()->count());
        $notification = $this->teacher2User->unreadNotifications()->first();
        $this->assertEquals('Substitute Teacher Assigned', $notification->data['title']);
        $this->assertStringContainsString('assigned as substitute', $notification->data['message']);
    }

    public function test_notification_read_state_management(): void
    {
        $changeService = app(TimetableOperationalChangeService::class);
        $changeService->changeTeacher(
            timetable: $this->timetable,
            slot: $this->slot,
            newTeacher: $this->teacher2,
            reason: 'Staff adjustment',
            actor: $this->adminUser
        );

        $notification = $this->teacher2User->unreadNotifications()->first();

        // Check unread count endpoint
        $response = $this->actingAs($this->teacher2User)
            ->getJson(route('notifications.unread-count'));
        $response->assertJson(['unread_count' => 1]);

        // Mark single notification read
        $readResponse = $this->actingAs($this->teacher2User)
            ->postJson(route('notifications.read', $notification->id));
        $readResponse->assertJson(['success' => true]);

        $this->assertEquals(0, $this->teacher2User->fresh()->unreadNotifications()->count());
    }
}
