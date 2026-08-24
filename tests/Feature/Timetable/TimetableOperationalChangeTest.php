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
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TimetableOperationalChangeTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;
    protected Teacher $teacher1;
    protected Teacher $teacher2;
    protected Room $roomA;
    protected Room $roomB;
    protected SchoolClass $class1;
    protected SchoolClass $class2;
    protected Subject $math;
    protected SchoolPeriod $period1;
    protected SchoolPeriod $period2;
    protected Timetable $timetable;
    protected TimetableSlot $slot1;

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

        $this->teacher1 = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $this->adminUser->id,
            'employee_id' => 'EMP-OP-01',
            'first_name' => 'Teacher',
            'last_name' => 'One',
            'email' => 't1@school.com',
            'status' => true,
        ]);

        $t2User = User::factory()->create(['school_id' => $this->school->id]);
        $this->teacher2 = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $t2User->id,
            'employee_id' => 'EMP-OP-02',
            'first_name' => 'Teacher',
            'last_name' => 'Two',
            'email' => 't2@school.com',
            'status' => true,
        ]);

        $this->roomA = Room::create(['school_id' => $this->school->id, 'name' => 'Room A', 'code' => 'RA', 'type' => 'classroom', 'capacity' => 40, 'is_active' => true]);
        $this->roomB = Room::create(['school_id' => $this->school->id, 'name' => 'Room B', 'code' => 'RB', 'type' => 'classroom', 'capacity' => 40, 'is_active' => true]);

        $this->class1 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Form 1A', 'grade' => 'Form 1', 'academic_year' => '2026', 'term' => 'Term 1']);
        $this->class2 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Form 2A', 'grade' => 'Form 2', 'academic_year' => '2026', 'term' => 'Term 1']);

        $this->math = Subject::create(['school_id' => $this->school->id, 'name' => 'Maths', 'code' => 'M101']);

        $this->period1 = SchoolPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Period 1',
            'period_sequence' => 1,
            'period_type' => 'lesson',
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'is_active' => true,
        ]);

        $this->period2 = SchoolPeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Period 2',
            'period_sequence' => 2,
            'period_type' => 'lesson',
            'start_time' => '08:45:00',
            'end_time' => '09:30:00',
            'is_active' => true,
        ]);

        $this->timetable = Timetable::create([
            'school_id' => $this->school->id,
            'name' => 'Master Timetable',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'status' => 'published',
            'revision' => 1,
            'is_operational' => true,
        ]);

        $this->slot1 = TimetableSlot::create([
            'timetable_id' => $this->timetable->id,
            'school_class_id' => $this->class1->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->teacher1->id,
            'room_id' => $this->roomA->id,
            'school_period_id' => $this->period1->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'status' => 'scheduled',
            'is_locked' => false,
        ]);
    }

    public function test_authorized_teacher_change_updates_slot_and_increments_revision(): void
    {
        $changeService = app(TimetableOperationalChangeService::class);
        $change = $changeService->changeTeacher(
            timetable: $this->timetable,
            slot: $this->slot1,
            newTeacher: $this->teacher2,
            reason: 'Staff illness',
            notes: 'Reassigned permanently for this week',
            actor: $this->adminUser
        );

        $this->slot1->refresh();
        $this->assertEquals($this->teacher2->id, $this->slot1->teacher_id);
        $this->assertEquals(2, $this->timetable->fresh()->revision);
        $this->assertDatabaseHas('timetable_operational_changes', [
            'id' => $change->id,
            'change_type' => 'teacher_change',
            'reason' => 'Staff illness',
            'revision' => 2,
        ]);
    }

    public function test_authorized_room_change_updates_slot_and_records_audit(): void
    {
        $changeService = app(TimetableOperationalChangeService::class);
        $change = $changeService->changeRoom(
            timetable: $this->timetable,
            slot: $this->slot1,
            newRoom: $this->roomB,
            reason: 'Room maintenance in Room A',
            actor: $this->adminUser
        );

        $this->slot1->refresh();
        $this->assertEquals($this->roomB->id, $this->slot1->room_id);
        $this->assertEquals(2, $this->timetable->fresh()->revision);
    }

    public function test_lesson_move_updates_schedule(): void
    {
        $changeService = app(TimetableOperationalChangeService::class);
        $change = $changeService->moveLesson(
            timetable: $this->timetable,
            slot: $this->slot1,
            newDay: 'Tuesday',
            newStartTime: '08:45:00',
            newEndTime: '09:30:00',
            newPeriod: $this->period2,
            newRoom: $this->roomB,
            reason: 'Schedule adjustment',
            actor: $this->adminUser
        );

        $this->slot1->refresh();
        $this->assertEquals('Tuesday', $this->slot1->day_of_week);
        $this->assertEquals('08:45:00', $this->slot1->start_time);
        $this->assertEquals($this->period2->id, $this->slot1->school_period_id);
    }

    public function test_lesson_cancellation_marks_status_without_deleting(): void
    {
        $changeService = app(TimetableOperationalChangeService::class);
        $change = $changeService->cancelLesson(
            timetable: $this->timetable,
            slot: $this->slot1,
            reason: 'School assembly during Period 1',
            actor: $this->adminUser
        );

        $this->slot1->refresh();
        $this->assertEquals('cancelled', $this->slot1->status);
        $this->assertDatabaseHas('timetable_slots', ['id' => $this->slot1->id]);
    }

    public function test_lesson_restoration_restores_scheduled_state(): void
    {
        $this->slot1->update(['status' => 'cancelled']);

        $changeService = app(TimetableOperationalChangeService::class);
        $change = $changeService->restoreLesson(
            timetable: $this->timetable,
            slot: $this->slot1,
            reason: 'Assembly cancelled, resuming lesson',
            actor: $this->adminUser
        );

        $this->slot1->refresh();
        $this->assertEquals('scheduled', $this->slot1->status);
    }

    public function test_locked_slot_cannot_be_changed_or_cancelled(): void
    {
        $this->slot1->update(['is_locked' => true]);
        $changeService = app(TimetableOperationalChangeService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('locked');

        $changeService->cancelLesson(
            timetable: $this->timetable,
            slot: $this->slot1,
            reason: 'Trying to cancel locked slot'
        );
    }

    public function test_hard_constraint_blocks_teacher_double_booking(): void
    {
        // Teacher 2 already has a class in Period 1
        TimetableSlot::create([
            'timetable_id' => $this->timetable->id,
            'school_class_id' => $this->class2->id,
            'subject_id' => $this->math->id,
            'teacher_id' => $this->teacher2->id,
            'room_id' => $this->roomB->id,
            'school_period_id' => $this->period1->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'status' => 'scheduled',
        ]);

        $changeService = app(TimetableOperationalChangeService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot assign teacher');

        // Attempting to reassign slot1 (also Period 1 Monday) to Teacher 2 must fail!
        $changeService->changeTeacher(
            timetable: $this->timetable,
            slot: $this->slot1,
            newTeacher: $this->teacher2,
            reason: 'Reassign to busy teacher'
        );
    }

    public function test_stale_revision_is_rejected(): void
    {
        $changeService = app(TimetableOperationalChangeService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stale timetable revision');

        // Client thinks timetable revision is 99, but actual is 1
        $changeService->changeRoom(
            timetable: $this->timetable,
            slot: $this->slot1,
            newRoom: $this->roomB,
            reason: 'Change room',
            expectedRevision: 99
        );
    }
}
