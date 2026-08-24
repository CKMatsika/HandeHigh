<?php

namespace Tests\Feature\Timetable;

use App\Models\Curriculum;
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
use App\Services\Timetable\Substitution\SubstitutionService;
use App\Services\Timetable\Substitution\TeacherAbsenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TimetableSubstitutionTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $adminUser;
    protected Teacher $teacherAbsent;
    protected Teacher $teacherCandidateQualified;
    protected Teacher $teacherCandidateUnqualified;
    protected Teacher $teacherBusy;
    protected SchoolClass $class1;
    protected Subject $physicsSubject;
    protected Subject $historySubject;
    protected Room $labRoom;
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

        // Absent Teacher
        $u1 = User::factory()->create(['school_id' => $this->school->id]);
        $this->teacherAbsent = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $u1->id,
            'employee_id' => 'EMP-SUB-01',
            'first_name' => 'Alan',
            'last_name' => 'Turing',
            'email' => 'alan@school.com',
            'status' => true,
            'specialization' => 'Physics',
        ]);

        // Qualified candidate (teaches physics)
        $u2 = User::factory()->create(['school_id' => $this->school->id]);
        $this->teacherCandidateQualified = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $u2->id,
            'employee_id' => 'EMP-SUB-02',
            'first_name' => 'Marie',
            'last_name' => 'Curie',
            'email' => 'marie@school.com',
            'status' => true,
            'specialization' => 'Physics',
        ]);

        // Unqualified candidate (teaches history)
        $u3 = User::factory()->create(['school_id' => $this->school->id]);
        $this->teacherCandidateUnqualified = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $u3->id,
            'employee_id' => 'EMP-SUB-03',
            'first_name' => 'Herodotus',
            'last_name' => 'Greek',
            'email' => 'herodotus@school.com',
            'status' => true,
            'specialization' => 'History',
        ]);

        // Busy teacher (already has class during Period 1)
        $u4 = User::factory()->create(['school_id' => $this->school->id]);
        $this->teacherBusy = Teacher::create([
            'school_id' => $this->school->id,
            'user_id' => $u4->id,
            'employee_id' => 'EMP-SUB-04',
            'first_name' => 'Isaac',
            'last_name' => 'Newton',
            'email' => 'isaac@school.com',
            'status' => true,
            'specialization' => 'Physics',
        ]);

        $this->physicsSubject = Subject::create(['school_id' => $this->school->id, 'name' => 'Physics', 'code' => 'PHY']);
        $this->historySubject = Subject::create(['school_id' => $this->school->id, 'name' => 'History', 'code' => 'HIS']);

        $this->teacherAbsent->subjects()->attach($this->physicsSubject->id);
        $this->teacherCandidateQualified->subjects()->attach($this->physicsSubject->id);
        $this->teacherCandidateUnqualified->subjects()->attach($this->historySubject->id);
        $this->teacherBusy->subjects()->attach($this->physicsSubject->id);

        $this->class1 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Form 3B', 'grade' => 'Form 3', 'academic_year' => '2026', 'term' => 'Term 1']);
        $class2 = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Form 2C', 'grade' => 'Form 2', 'academic_year' => '2026', 'term' => 'Term 1']);

        $this->labRoom = Room::create(['school_id' => $this->school->id, 'name' => 'Physics Lab', 'code' => 'LAB1', 'type' => 'lab', 'capacity' => 40, 'is_active' => true]);
        $room2 = Room::create(['school_id' => $this->school->id, 'name' => 'Room 2', 'code' => 'R2', 'type' => 'classroom', 'capacity' => 40, 'is_active' => true]);

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
            'name' => 'Published Term Timetable',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'status' => 'published',
            'revision' => 1,
            'is_operational' => true,
        ]);

        // Slot 1: Alan Turing teaching Physics in Period 1 Monday
        $this->slot1 = TimetableSlot::create([
            'timetable_id' => $this->timetable->id,
            'school_class_id' => $this->class1->id,
            'subject_id' => $this->physicsSubject->id,
            'teacher_id' => $this->teacherAbsent->id,
            'room_id' => $this->labRoom->id,
            'school_period_id' => $this->period1->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'status' => 'scheduled',
        ]);

        // Busy Teacher has a class in Period 1 Monday in Room 2
        TimetableSlot::create([
            'timetable_id' => $this->timetable->id,
            'school_class_id' => $class2->id,
            'subject_id' => $this->physicsSubject->id,
            'teacher_id' => $this->teacherBusy->id,
            'room_id' => $room2->id,
            'school_period_id' => $this->period1->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'status' => 'scheduled',
        ]);
    }

    public function test_teacher_absence_identifies_affected_lessons(): void
    {
        $absenceService = app(TeacherAbsenceService::class);
        $result = $absenceService->recordAbsence(
            schoolId: $this->school->id,
            teacherId: $this->teacherAbsent->id,
            startDate: '2026-08-24', // Monday
            endDate: '2026-08-25',   // Tuesday
            reason: 'Medical Leave',
            recorder: $this->adminUser
        );

        $this->assertInstanceOf(TeacherAbsence::class, $result['absence']);
        $this->assertCount(1, $result['affected_slots']);
        $this->assertEquals($this->slot1->id, $result['affected_slots']->first()->id);
        $this->assertEquals('2026-08-24', $result['affected_slots']->first()->occurrence_date);
    }

    public function test_substitute_recommendation_ranks_qualified_teacher_highest(): void
    {
        $subService = app(SubstitutionService::class);
        $recommendations = $subService->recommendSubstitutes($this->slot1, '2026-08-24');

        // TeacherBusy should be omitted because they have a lesson in Period 1 Monday!
        $candidateIds = $recommendations->pluck('teacher.id')->toArray();
        $this->assertNotContains($this->teacherBusy->id, $candidateIds);
        $this->assertNotContains($this->teacherAbsent->id, $candidateIds);

        // Marie Curie (qualified) should rank higher than Herodotus (unqualified for physics)
        $this->assertTrue($recommendations->count() >= 2);
        $topRec = $recommendations->first();
        $this->assertEquals($this->teacherCandidateQualified->id, $topRec->teacher->id);
        $this->assertGreaterThan(70, $topRec->score);
    }

    public function test_substitution_requires_approval_and_updates_operational_state(): void
    {
        $subService = app(SubstitutionService::class);
        $pendingSub = $subService->createPendingSubstitution(
            slot: $this->slot1,
            substituteTeacher: $this->teacherCandidateQualified,
            date: '2026-08-24',
            reason: 'Medical cover'
        );

        $this->assertEquals('pending', $pendingSub->status);

        // Approve substitution
        $approvedSub = $subService->approveSubstitution($pendingSub, $this->adminUser);

        $this->assertEquals('approved', $approvedSub->status);
        $this->assertEquals($this->adminUser->id, $approvedSub->approved_by);
        $this->assertNotNull($approvedSub->approved_at);

        // Timetable revision incremented
        $this->assertEquals(2, $this->timetable->fresh()->revision);

        // Operational change recorded
        $this->assertDatabaseHas('timetable_operational_changes', [
            'timetable_slot_id' => $this->slot1->id,
            'change_type' => 'substitute_assigned',
        ]);
    }

    public function test_revalidation_blocks_approval_if_substitute_became_absent(): void
    {
        $subService = app(SubstitutionService::class);
        $pendingSub = $subService->createPendingSubstitution(
            slot: $this->slot1,
            substituteTeacher: $this->teacherCandidateQualified,
            date: '2026-08-24',
            reason: 'Medical cover'
        );

        // Candidate Marie Curie suddenly gets recorded as absent on 2026-08-24
        TeacherAbsence::create([
            'school_id' => $this->school->id,
            'teacher_id' => $this->teacherCandidateQualified->id,
            'start_date' => '2026-08-24',
            'end_date' => '2026-08-24',
            'reason' => 'Emergency',
            'status' => 'active',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Teacher became unavailable');

        // Approval must fail revalidation and throw exception!
        $subService->approveSubstitution($pendingSub, $this->adminUser);
    }
}
