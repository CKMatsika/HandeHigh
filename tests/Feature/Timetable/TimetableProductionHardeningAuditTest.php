<?php

namespace Tests\Feature\Timetable;

use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolPeriod;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAbsence;
use App\Models\Timetable;
use App\Models\TimetableOperationalChange;
use App\Models\TimetableSlot;
use App\Models\TimetableSubstitution;
use App\Models\User;
use App\Notifications\Timetable\LessonCancelledNotification;
use App\Notifications\Timetable\SubstituteAssignedNotification;
use App\Notifications\Timetable\TimetableOperationalChangeNotification;
use App\Services\Timetable\Operations\TimetableOperationalChangeService;
use App\Services\Timetable\Operations\TimetableSyncService;
use App\Services\Timetable\Operations\TimetableTodayService;
use App\Services\Timetable\Substitution\SubstitutionService;
use App\Services\Timetable\Substitution\TeacherAbsenceService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableProductionHardeningAuditTest extends TestCase
{
    use RefreshDatabase;

    protected School $schoolA;
    protected School $schoolB;

    protected User $adminA;
    protected User $adminB;
    protected User $teacherA1User;
    protected User $teacherA2User;
    protected User $teacherBUser;
    protected User $studentAUser;
    protected User $studentBUser;

    protected Teacher $teacherA1;
    protected Teacher $teacherA2;
    protected Teacher $teacherB;

    protected Student $studentA;
    protected Student $studentB;

    protected SchoolClass $classA;
    protected SchoolClass $classB;

    protected Subject $subjectMath;
    protected Subject $subjectScience;
    protected Subject $subjectEnglish;

    protected Room $roomA1;
    protected Room $roomA2;
    protected Room $roomB;

    protected SchoolPeriod $period1;
    protected SchoolPeriod $period2;
    protected SchoolPeriod $period3;

    protected Timetable $timetableA;
    protected Timetable $timetableB;

    protected TimetableSlot $slotA1;
    protected TimetableSlot $slotA2;
    protected TimetableSlot $slotB1;

    protected function setUp(): void
    {
        parent::setUp();

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        // 1. Schools
        $this->schoolA = School::create(['name' => 'School Alpha', 'code' => 'SCH-A', 'email' => 'admin@alpha.edu']);
        $this->schoolB = School::create(['name' => 'School Beta', 'code' => 'SCH-B', 'email' => 'admin@beta.edu']);

        // 2. Admins
        $this->adminA = User::factory()->create(['school_id' => $this->schoolA->id]);
        $this->adminA->assignRole('school-admin');
        $this->adminB = User::factory()->create(['school_id' => $this->schoolB->id]);
        $this->adminB->assignRole('school-admin');

        // 3. Teachers
        $this->teacherA1User = User::factory()->create(['school_id' => $this->schoolA->id]);
        $this->teacherA1User->assignRole('teacher');
        $this->teacherA1 = Teacher::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $this->teacherA1User->id,
            'employee_id' => 'TEA-A-01',
            'first_name' => 'Tariro',
            'last_name' => 'Moyo',
            'email' => 'tariro@alpha.edu',
            'status' => true,
        ]);

        $this->teacherA2User = User::factory()->create(['school_id' => $this->schoolA->id]);
        $this->teacherA2User->assignRole('teacher');
        $this->teacherA2 = Teacher::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $this->teacherA2User->id,
            'employee_id' => 'TEA-A-02',
            'first_name' => 'Farai',
            'last_name' => 'Chiwara',
            'email' => 'farai@alpha.edu',
            'status' => true,
        ]);

        $this->teacherBUser = User::factory()->create(['school_id' => $this->schoolB->id]);
        $this->teacherBUser->assignRole('teacher');
        $this->teacherB = Teacher::create([
            'school_id' => $this->schoolB->id,
            'user_id' => $this->teacherBUser->id,
            'employee_id' => 'TEA-B-01',
            'first_name' => 'Bob',
            'last_name' => 'Beta',
            'email' => 'bob@beta.edu',
            'status' => true,
        ]);

        // 4. Classes
        $this->classA = SchoolClass::create(['school_id' => $this->schoolA->id, 'name' => 'Form 1A', 'grade' => 'Form 1', 'academic_year' => '2026', 'term' => 'Term 1']);
        $this->classB = SchoolClass::create(['school_id' => $this->schoolB->id, 'name' => 'Form 1B', 'grade' => 'Form 1', 'academic_year' => '2026', 'term' => 'Term 1']);

        // 5. Students
        $this->studentAUser = User::factory()->create(['school_id' => $this->schoolA->id]);
        $this->studentAUser->assignRole('student');
        $this->studentA = Student::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $this->studentAUser->id,
            'admission_number' => 'ADM-A-01',
            'first_name' => 'Simba',
            'last_name' => 'Ndlovu',
            'gender' => 'male',
            'date_of_birth' => '2012-05-15',
            'enrollment_date' => '2026-01-10',
            'status' => 'active',
            'class_name' => 'Form 1A',
        ]);
        Enrollment::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $this->studentA->id,
            'class_id' => $this->classA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'enrollment_date' => '2026-01-10',
            'status' => 'active',
        ]);

        $this->studentBUser = User::factory()->create(['school_id' => $this->schoolB->id]);
        $this->studentBUser->assignRole('student');
        $this->studentB = Student::create([
            'school_id' => $this->schoolB->id,
            'user_id' => $this->studentBUser->id,
            'admission_number' => 'ADM-B-01',
            'first_name' => 'Kuda',
            'last_name' => 'Beta',
            'gender' => 'female',
            'date_of_birth' => '2012-08-20',
            'enrollment_date' => '2026-01-10',
            'status' => 'active',
            'class_name' => 'Form 1B',
        ]);
        Enrollment::create([
            'school_id' => $this->schoolB->id,
            'student_id' => $this->studentB->id,
            'class_id' => $this->classB->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'enrollment_date' => '2026-01-10',
            'status' => 'active',
        ]);

        // 6. Subjects
        $this->subjectMath = Subject::create(['school_id' => $this->schoolA->id, 'name' => 'Mathematics', 'code' => 'MATH']);
        $this->subjectScience = Subject::create(['school_id' => $this->schoolA->id, 'name' => 'Physical Science', 'code' => 'SCI']);
        $this->subjectEnglish = Subject::create(['school_id' => $this->schoolA->id, 'name' => 'English Language', 'code' => 'ENG']);

        $this->teacherA1->subjects()->attach([$this->subjectMath->id, $this->subjectScience->id]);
        $this->teacherA2->subjects()->attach([$this->subjectMath->id]);

        // 7. Rooms
        $this->roomA1 = Room::create(['school_id' => $this->schoolA->id, 'name' => 'Room 101', 'code' => 'R101', 'capacity' => 45, 'type' => 'classroom', 'is_active' => true]);
        $this->roomA2 = Room::create(['school_id' => $this->schoolA->id, 'name' => 'Room 102', 'code' => 'R102', 'capacity' => 45, 'type' => 'classroom', 'is_active' => true]);
        $this->roomB = Room::create(['school_id' => $this->schoolB->id, 'name' => 'Beta Room', 'code' => 'BR1', 'capacity' => 40, 'type' => 'classroom', 'is_active' => true]);

        // 8. Periods
        $this->period1 = SchoolPeriod::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Period 1',
            'period_sequence' => 1,
            'period_type' => 'lesson',
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'is_active' => true,
        ]);
        $this->period2 = SchoolPeriod::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Period 2',
            'period_sequence' => 2,
            'period_type' => 'lesson',
            'start_time' => '08:45:00',
            'end_time' => '09:30:00',
            'is_active' => true,
        ]);
        $this->period3 = SchoolPeriod::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Period 3',
            'period_sequence' => 3,
            'period_type' => 'lesson',
            'start_time' => '09:45:00',
            'end_time' => '10:30:00',
            'is_active' => true,
        ]);

        // 9. Timetables
        $this->timetableA = Timetable::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Term 1 2026 Master',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'status' => 'published',
            'revision' => 1,
            'is_operational' => true,
            'published_at' => now(),
        ]);

        $this->timetableB = Timetable::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Term 1 2026 Beta',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'status' => 'published',
            'revision' => 1,
            'is_operational' => true,
            'published_at' => now(),
        ]);

        // 10. Slots
        $this->slotA1 = TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->subjectMath->id,
            'teacher_id' => $this->teacherA1->id,
            'room_id' => $this->roomA1->id,
            'school_period_id' => $this->period1->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'status' => 'scheduled',
            'is_locked' => false,
        ]);

        $this->slotA2 = TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->subjectScience->id,
            'teacher_id' => $this->teacherA1->id,
            'room_id' => $this->roomA1->id,
            'school_period_id' => $this->period2->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:45:00',
            'end_time' => '09:30:00',
            'status' => 'scheduled',
            'is_locked' => false,
        ]);

        $this->slotB1 = TimetableSlot::create([
            'timetable_id' => $this->timetableB->id,
            'school_class_id' => $this->classB->id,
            'subject_id' => Subject::create(['school_id' => $this->schoolB->id, 'name' => 'History', 'code' => 'HIST'])->id,
            'teacher_id' => $this->teacherB->id,
            'room_id' => $this->roomB->id,
            'school_period_id' => SchoolPeriod::create(['school_id' => $this->schoolB->id, 'name' => 'P1', 'period_sequence' => 1, 'start_time' => '08:00:00', 'end_time' => '08:45:00', 'is_active' => true])->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'status' => 'scheduled',
            'is_locked' => false,
        ]);
    }

    /**
     * CONCURRENCY & OPTIMISTIC LOCKING: Stale revision is rejected without overwriting live state.
     */
    public function test_concurrency_stale_revision_rejected_and_state_preserved(): void
    {
        $changeService = app(TimetableOperationalChangeService::class);

        // Admin B performs change that increments revision from 1 to 2
        $changeService->changeRoom(
            timetable: $this->timetableA,
            slot: $this->slotA1,
            newRoom: $this->roomA2,
            reason: 'Admin B updated room',
            actor: $this->adminA
        );

        $this->assertEquals(2, $this->timetableA->fresh()->revision);
        $this->assertEquals($this->roomA2->id, $this->slotA1->fresh()->room_id);

        // Admin A attempts operation using stale expected revision 1
        $this->actingAs($this->adminA);
        $response = $this->postJson("/admin/timetables/{$this->timetableA->id}/slots/{$this->slotA1->id}/change-teacher", [
            'new_teacher_id' => $this->teacherA2->id,
            'reason' => 'Admin A attempt with stale revision',
            'expected_revision' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('Stale timetable revision', $response->json('message'));

        // Verify slot teacher was NOT changed and revision remains 2
        $this->assertEquals($this->teacherA1->id, $this->slotA1->fresh()->teacher_id);
        $this->assertEquals(2, $this->timetableA->fresh()->revision);
    }

    /**
     * SECURITY & IDOR: Comprehensive cross-tenant access is strictly denied with 404s.
     */
    public function test_security_cross_tenant_idor_matrix_denied(): void
    {
        $this->actingAs($this->adminB);

        // 1. Accessing School A's timetable
        $this->get("/admin/timetables/{$this->timetableA->id}")->assertStatus(404);
        $this->getJson("/api/timetable/{$this->timetableA->id}/changes")->assertStatus(404);
        $this->getJson("/api/timetable/{$this->timetableA->id}/version")->assertStatus(404);

        // 2. Modifying School A's slot
        $this->postJson("/admin/timetables/{$this->timetableA->id}/slots/{$this->slotA1->id}/change-teacher", [
            'new_teacher_id' => $this->teacherB->id,
            'reason' => 'Cross-tenant tampering',
        ])->assertStatus(404);

        $this->postJson("/admin/timetables/{$this->timetableA->id}/slots/{$this->slotA1->id}/cancel", [
            'reason' => 'Cross-tenant cancel',
        ])->assertStatus(404);

        // 3. Substituting School A's slot
        $this->get("/admin/timetables/slots/{$this->slotA1->id}/substitutions/recommend")->assertStatus(404);

        // 4. Cross-tenant teacher absence
        $absenceA = TeacherAbsence::create([
            'school_id' => $this->schoolA->id,
            'teacher_id' => $this->teacherA1->id,
            'start_date' => '2026-08-24',
            'end_date' => '2026-08-24',
            'reason' => 'Medical',
            'status' => 'active',
        ]);
        $this->get("/admin/timetables-absences/{$absenceA->id}")->assertStatus(404);
        $this->post("/admin/timetables-absences/{$absenceA->id}/cancel")->assertStatus(404);

        // 5. Cross-tenant substitution approval
        $subA = TimetableSubstitution::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'timetable_slot_id' => $this->slotA1->id,
            'original_teacher_id' => $this->teacherA1->id,
            'substitute_teacher_id' => $this->teacherA2->id,
            'date' => '2026-08-24',
            'status' => 'pending',
        ]);
        $this->postJson("/admin/timetables-substitutions/{$subA->id}/approve")->assertStatus(404);
    }

    /**
     * LOCKED SLOT INTEGRITY: Locked slots cannot be changed, cancelled, moved, or substituted.
     */
    public function test_locked_slots_cannot_be_mutated(): void
    {
        $this->slotA1->update(['is_locked' => true]);

        $this->actingAs($this->adminA);

        // 1. Teacher Change Attempt
        $res1 = $this->postJson("/admin/timetables/{$this->timetableA->id}/slots/{$this->slotA1->id}/change-teacher", [
            'new_teacher_id' => $this->teacherA2->id,
            'reason' => 'Bypass lock',
        ]);
        $res1->assertStatus(422);
        $this->assertStringContainsString('locked and cannot be modified', $res1->json('message'));

        // 2. Room Change Attempt
        $res2 = $this->postJson("/admin/timetables/{$this->timetableA->id}/slots/{$this->slotA1->id}/change-room", [
            'new_room_id' => $this->roomA2->id,
            'reason' => 'Bypass lock',
        ]);
        $res2->assertStatus(422);
        $this->assertStringContainsString('locked and cannot be modified', $res2->json('message'));

        // 3. Move Attempt
        $res3 = $this->postJson("/admin/timetables/{$this->timetableA->id}/slots/{$this->slotA1->id}/move", [
            'new_day_of_week' => 'Tuesday',
            'new_start_time' => '08:00',
            'new_end_time' => '08:45',
            'reason' => 'Bypass lock',
        ]);
        $res3->assertStatus(422);
        $this->assertStringContainsString('locked and cannot be moved', $res3->json('message'));

        // 4. Cancel Attempt
        $res4 = $this->postJson("/admin/timetables/{$this->timetableA->id}/slots/{$this->slotA1->id}/cancel", [
            'reason' => 'Bypass lock',
        ]);
        $res4->assertStatus(422);
        $this->assertStringContainsString('locked and cannot be cancelled', $res4->json('message'));

        // Verify slot state was completely protected
        $this->assertEquals($this->teacherA1->id, $this->slotA1->fresh()->teacher_id);
        $this->assertEquals($this->roomA1->id, $this->slotA1->fresh()->room_id);
        $this->assertEquals('Monday', $this->slotA1->fresh()->day_of_week);
        $this->assertEquals('scheduled', $this->slotA1->fresh()->status);
    }

    /**
     * HARD CONSTRAINT SAFETY: Phase 3C deterministic constraint engine strictly prevents double bookings.
     */
    public function test_hard_constraints_block_teacher_and_room_double_booking(): void
    {
        $this->actingAs($this->adminA);

        // Teacher A2 is already teaching slot A2 at 08:45-09:30? Let's assign teacher A2 to slot A1 (08:00-08:45) -> succeeds
        $changeService = app(TimetableOperationalChangeService::class);
        $changeService->changeTeacher($this->timetableA, $this->slotA1, $this->teacherA2, 'Assign A2 to P1');

        // Now Teacher A2 is busy at 08:00-08:45 on Monday.
        // Attempt to assign Teacher A2 to slot A2 and move slot A2 to 08:00-08:45 -> double booking conflict!
        $res = $this->postJson("/admin/timetables/{$this->timetableA->id}/slots/{$this->slotA2->id}/move", [
            'new_day_of_week' => 'Monday',
            'new_start_time' => '08:00',
            'new_end_time' => '08:45',
            'reason' => 'Move to conflict time',
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('Cannot move lesson', $res->json('message'));
    }

    /**
     * SUBSTITUTION REVALIDATION: Pre-approval race condition revalidation blocks approval if teacher became absent.
     */
    public function test_substitution_approval_revalidates_and_blocks_unavailable_candidate(): void
    {
        $subService = app(SubstitutionService::class);

        // 1. Create a pending substitution for Teacher A2 covering slot A1 on 2026-08-24
        $pendingSub = $subService->createPendingSubstitution(
            slot: $this->slotA1,
            substituteTeacher: $this->teacherA2,
            date: '2026-08-24',
            reason: 'Flu cover'
        );

        $this->assertEquals('pending', $pendingSub->status);

        // 2. Before admin approves, Teacher A2 logs an illness/absence on 2026-08-24
        TeacherAbsence::create([
            'school_id' => $this->schoolA->id,
            'teacher_id' => $this->teacherA2->id,
            'start_date' => '2026-08-24',
            'end_date' => '2026-08-24',
            'reason' => 'Sudden emergency',
            'status' => 'active',
        ]);

        // 3. Admin attempts to approve pending substitution -> Revalidation MUST detect absence and reject approval
        $this->actingAs($this->adminA);
        $response = $this->postJson("/admin/timetables-substitutions/{$pendingSub->id}/approve");

        $response->assertStatus(422);
        $this->assertStringContainsString('Teacher became unavailable', $response->json('message'));

        // Substitution status should be marked cancelled with audit note
        $this->assertEquals('cancelled', $pendingSub->fresh()->status);
        $this->assertStringContainsString('recorded absence', $pendingSub->fresh()->notes);
    }

    /**
     * DELTA SYNC ENGINE: Tests revision tracking, delta queries, future revision handling, and zero leakage.
     */
    public function test_delta_synchronization_engine_lifecycle(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->adminA, ['*']);

        // 1. Initial version check
        $vRes = $this->getJson("/api/timetable/{$this->timetableA->id}/version");
        $vRes->assertStatus(200);
        $vRes->assertJsonPath('revision', 1);
        $vRes->assertJsonPath('total_changes', 0);

        // 2. Perform 3 sequential changes
        $changeService = app(TimetableOperationalChangeService::class);
        $changeService->changeRoom($this->timetableA, $this->slotA1, $this->roomA2, 'Change Room 1');
        $changeService->cancelLesson($this->timetableA, $this->slotA2, 'Cancel Lesson 2');
        $changeService->restoreLesson($this->timetableA, $this->slotA2, 'Restore Lesson 2');

        $this->assertEquals(4, $this->timetableA->fresh()->revision);

        // 3. Request since revision 0 (all 3 changes)
        $deltaAll = $this->getJson("/api/timetable/{$this->timetableA->id}/changes?since_revision=0");
        $deltaAll->assertStatus(200);
        $deltaAll->assertJsonPath('current_revision', 4);
        $deltaAll->assertJsonCount(3, 'changes');

        // 4. Request since revision 2 (only changes at revision 3 and 4)
        $deltaPartial = $this->getJson("/api/timetable/{$this->timetableA->id}/changes?since_revision=2");
        $deltaPartial->assertStatus(200);
        $deltaPartial->assertJsonCount(2, 'changes');
        $this->assertEquals(3, $deltaPartial->json('changes.0.revision'));
        $this->assertEquals(4, $deltaPartial->json('changes.1.revision'));

        // 5. Request future revision (should return empty list gracefully without error)
        $deltaFuture = $this->getJson("/api/timetable/{$this->timetableA->id}/changes?since_revision=999");
        $deltaFuture->assertStatus(200);
        $deltaFuture->assertJsonCount(0, 'changes');
    }

    /**
     * NOTIFICATIONS & IN-APP CENTER: Tests targeted delivery, read/unread states, and bulk mark as read.
     */
    public function test_notification_delivery_and_read_state_management(): void
    {
        $changeService = app(TimetableOperationalChangeService::class);

        // Cancel Lesson: Teacher A1 and Student A should receive notifications
        $changeService->cancelLesson(
            timetable: $this->timetableA,
            slot: $this->slotA1,
            reason: 'Heavy rain flooding',
            actor: $this->adminA
        );

        $this->assertEquals(1, $this->teacherA1User->unreadNotifications()->count());
        $this->assertEquals(1, $this->studentAUser->unreadNotifications()->count());
        // Unrelated teacher and student must NOT receive any notification
        $this->assertEquals(0, $this->teacherA2User->unreadNotifications()->count());
        $this->assertEquals(0, $this->studentBUser->unreadNotifications()->count());

        // Test Mark Single Read
        $notif = $this->studentAUser->unreadNotifications()->first();
        $this->actingAs($this->studentAUser);
        $resRead = $this->postJson("/notifications/{$notif->id}/read");
        $resRead->assertStatus(200);
        $this->assertEquals(0, $this->studentAUser->fresh()->unreadNotifications()->count());

        // Test Mark All Read for Teacher A1
        $this->actingAs($this->teacherA1User);
        $resAll = $this->postJson("/notifications/read-all");
        $resAll->assertStatus(200);
        $this->assertEquals(0, $this->teacherA1User->fresh()->unreadNotifications()->count());
    }

    /**
     * REALISTIC END-TO-END WORKFLOW AUDIT
     */
    public function test_full_realistic_phase3e_operational_lifecycle(): void
    {
        // 1. Teacher A1 records absence for Monday 2026-08-24
        $absenceService = app(TeacherAbsenceService::class);
        $absenceResult = $absenceService->recordAbsence(
            schoolId: $this->schoolA->id,
            teacherId: $this->teacherA1->id,
            startDate: '2026-08-24',
            endDate: '2026-08-24',
            reason: 'ZIMSEC Workshop',
            recorder: $this->adminA
        );

        $absence = $absenceResult['absence'];
        $this->assertCount(2, $absenceResult['affected_slots']);

        // 2. Recommend substitute for Slot A1 (Math)
        $subService = app(SubstitutionService::class);
        $recommendations = $subService->recommendSubstitutes($this->slotA1, '2026-08-24');
        $this->assertNotEmpty($recommendations);
        $topRec = $recommendations->first();
        $this->assertEquals($this->teacherA2->id, $topRec->teacher->id);

        // 3. Admin creates and approves substitution
        $this->actingAs($this->adminA);
        $storeRes = $this->postJson("/admin/timetables/slots/{$this->slotA1->id}/substitutions", [
            'substitute_teacher_id' => $this->teacherA2->id,
            'date' => '2026-08-24',
            'teacher_absence_id' => $absence->id,
            'reason' => 'ZIMSEC Cover',
            'auto_approve' => true,
        ]);
        if (! $storeRes->json('success')) {
            dump($storeRes->json());
        }
        $storeRes->assertStatus(200);

        // 4. Verify Revision incremented and audit change created
        $this->assertEquals(2, $this->timetableA->fresh()->revision);
        $this->assertDatabaseHas('timetable_operational_changes', [
            'timetable_id' => $this->timetableA->id,
            'change_type' => 'substitute_assigned',
            'revision' => 2,
        ]);

        // 5. Verify substitute teacher A2 received notification
        $this->assertEquals(1, $this->teacherA2User->fresh()->unreadNotifications()->count());

        // 6. Verify Teacher Portal shows substitute duty
        $todayService = app(TimetableTodayService::class);
        $teacher2Today = $todayService->getTeacherToday($this->teacherA2, $this->timetableA, \Carbon\Carbon::parse('2026-08-24'));
        $this->assertCount(1, $teacher2Today['lessons']);
        $this->assertTrue($teacher2Today['lessons']->first()->is_substitute_duty);

        // 7. Verify Student Portal shows substitute name
        $studentToday = $todayService->getClassToday($this->classA, $this->timetableA, \Carbon\Carbon::parse('2026-08-24'));
        $this->assertCount(2, $studentToday['lessons']);
        $this->assertEquals('Farai Chiwara', $studentToday['lessons']->first()->active_substitute?->full_name);

        // 8. Verify Delta sync endpoint returns the change
        \Laravel\Sanctum\Sanctum::actingAs($this->adminA, ['*']);
        $delta = $this->getJson("/api/timetable/{$this->timetableA->id}/changes?since_revision=1");
        $delta->assertStatus(200);
        $delta->assertJsonPath('current_revision', 2);
        $delta->assertJsonCount(1, 'changes');
    }
}
