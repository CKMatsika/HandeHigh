<?php

namespace Tests\Feature\Timetable;

use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolPeriod;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAbsence;
use App\Models\Timetable;
use App\Models\TimetableSlot;
use App\Models\TimetableSubstitution;
use App\Models\User;
use App\Services\Timetable\Operations\TimetableOperationalChangeService;
use App\Services\Timetable\Substitution\SubstitutionService;
use App\Services\Timetable\Substitution\TeacherAbsenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TimetableSyncAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected School $schoolA;
    protected School $schoolB;
    protected User $adminA;
    protected User $adminB;
    protected Teacher $teacherA1;
    protected Teacher $teacherA2;
    protected Teacher $teacherB;
    protected SchoolClass $classA;
    protected SchoolClass $classB;
    protected Subject $subjectA;
    protected Room $roomA;
    protected SchoolPeriod $period1;
    protected Timetable $timetableA;
    protected TimetableSlot $slotA;

    protected function setUp(): void
    {
        parent::setUp();

        (new \Database\Seeders\RoleSeeder)->run();
        (new \Database\Seeders\RolePermissionSeeder)->run();

        $this->schoolA = School::create([
            'name' => 'Harare High School',
            'code' => 'HARARE',
            'email' => 'admin@hararehigh.ac.zw',
        ]);
        $this->schoolB = School::create([
            'name' => 'Bulawayo Academy',
            'code' => 'BULAWAYO',
            'email' => 'admin@bulawayo.ac.zw',
        ]);

        $this->adminA = User::factory()->create(['school_id' => $this->schoolA->id]);
        $this->adminA->assignRole('school-admin');

        $this->adminB = User::factory()->create(['school_id' => $this->schoolB->id]);
        $this->adminB->assignRole('school-admin');

        $u1 = User::factory()->create(['school_id' => $this->schoolA->id]);
        $this->teacherA1 = Teacher::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $u1->id,
            'employee_id' => 'EMP-SEC-01',
            'first_name' => 'Alice',
            'last_name' => 'A',
            'email' => 'alice.a@schoola.ac.zw',
            'status' => true,
        ]);

        $u2 = User::factory()->create(['school_id' => $this->schoolA->id]);
        $this->teacherA2 = Teacher::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $u2->id,
            'employee_id' => 'EMP-SEC-02',
            'first_name' => 'Arthur',
            'last_name' => 'A2',
            'email' => 'arthur.a2@schoola.ac.zw',
            'status' => true,
        ]);

        $uB = User::factory()->create(['school_id' => $this->schoolB->id]);
        $this->teacherB = Teacher::create([
            'school_id' => $this->schoolB->id,
            'user_id' => $uB->id,
            'employee_id' => 'EMP-SEC-03',
            'first_name' => 'Bob',
            'last_name' => 'B',
            'email' => 'bob.b@schoolb.ac.zw',
            'status' => true,
        ]);

        $this->classA = SchoolClass::create(['school_id' => $this->schoolA->id, 'name' => 'Class 1A', 'grade' => 'Form 1', 'academic_year' => '2026', 'term' => 'Term 1']);
        $this->classB = SchoolClass::create(['school_id' => $this->schoolB->id, 'name' => 'Class 1B', 'grade' => 'Form 1', 'academic_year' => '2026', 'term' => 'Term 1']);

        $this->subjectA = Subject::create(['school_id' => $this->schoolA->id, 'name' => 'Math', 'code' => 'M']);
        $this->roomA = Room::create(['school_id' => $this->schoolA->id, 'name' => 'Room A', 'code' => 'RA', 'type' => 'classroom', 'capacity' => 40, 'is_active' => true]);

        $this->period1 = SchoolPeriod::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Period 1',
            'period_sequence' => 1,
            'period_type' => 'lesson',
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'is_active' => true,
        ]);

        $this->timetableA = Timetable::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Timetable A',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'status' => 'published',
            'revision' => 1,
            'is_operational' => true,
        ]);

        $this->slotA = TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->subjectA->id,
            'teacher_id' => $this->teacherA1->id,
            'room_id' => $this->roomA->id,
            'school_period_id' => $this->period1->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'status' => 'scheduled',
        ]);
    }

    public function test_api_version_endpoint(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->adminA, ['*']);
        $response = $this->getJson("/api/timetable/{$this->timetableA->id}/version");

        $response->assertStatus(200);
        $response->assertJson([
            'timetable_id' => $this->timetableA->id,
            'school_id' => $this->schoolA->id,
            'revision' => 1,
        ]);
    }

    public function test_delta_sync_endpoint_returns_newer_changes(): void
    {
        $changeService = app(TimetableOperationalChangeService::class);
        $changeService->changeTeacher(
            timetable: $this->timetableA,
            slot: $this->slotA,
            newTeacher: $this->teacherA2,
            reason: 'Test change 1',
            actor: $this->adminA
        );

        $changeService->cancelLesson(
            timetable: $this->timetableA,
            slot: $this->slotA,
            reason: 'Test change 2',
            actor: $this->adminA
        );

        // Client at revision 1 requests changes
        \Laravel\Sanctum\Sanctum::actingAs($this->adminA, ['*']);
        $response = $this->getJson("/api/timetable/{$this->timetableA->id}/changes?since_revision=1");

        $response->assertStatus(200);
        $response->assertJsonPath('current_revision', 3);
        $response->assertJsonCount(2, 'changes');
    }

    public function test_idor_cross_tenant_access_is_blocked(): void
    {
        // Admin B from School B cannot access School A's changes (route model binding returns 404)
        \Laravel\Sanctum\Sanctum::actingAs($this->adminB, ['*']);
        $response = $this->getJson("/api/timetable/{$this->timetableA->id}/changes");

        $response->assertStatus(404);
    }

    public function test_cross_tenant_teacher_assignment_is_blocked(): void
    {
        // Admin A trying to assign Bob (from School B) to a slot in School A must fail with 404 (model not found within tenant)
        \Laravel\Sanctum\Sanctum::actingAs($this->adminA, ['*']);
        $response = $this->postJson("/api/timetable/{$this->timetableA->id}/slots/{$this->slotA->id}/teacher", [
            'new_teacher_id' => $this->teacherB->id,
            'reason' => 'Assigning cross-tenant teacher',
        ]);

        $response->assertStatus(404);
    }

    public function test_complete_end_to_end_absence_substitution_notification_flow(): void
    {
        // 1. Record Teacher Absence
        $absenceService = app(TeacherAbsenceService::class);
        $absenceResult = $absenceService->recordAbsence(
            schoolId: $this->schoolA->id,
            teacherId: $this->teacherA1->id,
            startDate: '2026-08-24', // Monday
            endDate: '2026-08-24',
            reason: 'Medical examination',
            recorder: $this->adminA
        );

        $this->assertCount(1, $absenceResult['affected_slots']);

        // 2. Recommend substitute candidates
        $subService = app(SubstitutionService::class);
        $recommendations = $subService->recommendSubstitutes($this->slotA, '2026-08-24');
        $this->assertTrue($recommendations->isNotEmpty());

        // 3. Create pending substitution for top candidate
        $pending = $subService->createPendingSubstitution(
            slot: $this->slotA,
            substituteTeacher: $this->teacherA2,
            date: '2026-08-24',
            absence: $absenceResult['absence'],
            reason: 'Covering for Alice'
        );

        // 4. Approve substitution
        $approved = $subService->approveSubstitution($pending, $this->adminA);
        $this->assertEquals('approved', $approved->status);

        // 5. Verify timetable revision incremented
        $this->assertEquals(2, $this->timetableA->fresh()->revision);

        // 6. Verify audit change record created
        $this->assertDatabaseHas('timetable_operational_changes', [
            'timetable_id' => $this->timetableA->id,
            'change_type' => 'substitute_assigned',
            'revision' => 2,
        ]);

        // 7. Verify Teacher A2 received in-app notification
        $this->assertGreaterThanOrEqual(1, $this->teacherA2->user->unreadNotifications()->count());
    }
}
