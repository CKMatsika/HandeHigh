<?php

namespace Tests\Feature\Timetable;

use App\Models\Curriculum;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolPeriod;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableExamination;
use App\Models\TimetableFixedActivity;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\Timetable\TimetableConflictService;
use App\Services\Timetable\TimetablePeriodService;
use App\Services\Timetable\TimetableService;
use App\Services\Timetable\TimetableValidationService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableEngineTest extends TestCase
{
    use RefreshDatabase;

    protected School $schoolA;
    protected School $schoolB;
    protected User $adminA;
    protected User $adminB;
    protected User $teacherUserA;
    protected User $studentUserA;
    protected Teacher $teacherA;
    protected Teacher $teacherB;
    protected SchoolClass $classA;
    protected SchoolClass $classA2;
    protected SchoolClass $classB;
    protected Subject $mathA;
    protected Subject $engA;
    protected Subject $mathB;
    protected Room $roomA;
    protected Room $roomB;
    protected SchoolPeriod $period1A;
    protected SchoolPeriod $period2A;
    protected SchoolPeriod $period3A;
    protected Timetable $timetableA;

    protected function setUp(): void
    {
        parent::setUp();

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        // Create School A
        $this->schoolA = School::create([
            'name' => 'Harare High School',
            'code' => 'HARARE',
            'email' => 'admin@hararehigh.ac.zw',
        ]);

        // Create School B
        $this->schoolB = School::create([
            'name' => 'Bulawayo Academy',
            'code' => 'BULAWAYO',
            'email' => 'admin@bulawayo.ac.zw',
        ]);

        // Admin User for School A
        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'email' => 'admin@hararehigh.ac.zw',
        ]);
        $this->adminA->assignRole('school-admin');

        // Admin User for School B
        $this->adminB = User::factory()->create([
            'school_id' => $this->schoolB->id,
            'email' => 'admin@bulawayo.ac.zw',
        ]);
        $this->adminB->assignRole('school-admin');

        // Teacher User for School A
        $this->teacherUserA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'email' => 'teacher@hararehigh.ac.zw',
        ]);
        $this->teacherUserA->assignRole('teacher');

        // Student User for School A
        $this->studentUserA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'email' => 'student@hararehigh.ac.zw',
        ]);
        $this->studentUserA->assignRole('student');

        // Teacher models
        $this->teacherA = Teacher::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $this->teacherUserA->id,
            'employee_id' => 'EMP-HAR-01',
            'first_name' => 'Tatenda',
            'last_name' => 'Moyo',
            'email' => 'tatenda.moyo@hararehigh.ac.zw',
            'status' => true,
        ]);

        $teacherUserB = User::factory()->create([
            'school_id' => $this->schoolB->id,
            'email' => 'teacher@bulawayo.ac.zw',
        ]);
        $teacherUserB->assignRole('teacher');

        $this->teacherB = Teacher::create([
            'school_id' => $this->schoolB->id,
            'user_id' => $teacherUserB->id,
            'employee_id' => 'EMP-BUL-01',
            'first_name' => 'Sipho',
            'last_name' => 'Ndlovu',
            'email' => 'sipho.ndlovu@bulawayo.ac.zw',
            'status' => true,
        ]);

        // Classes
        $this->classA = SchoolClass::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Form 4A',
            'grade' => '4',
            'academic_year' => '2025',
            'term' => 'First Term',
        ]);

        $this->classA2 = SchoolClass::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Form 4B',
            'grade' => '4',
            'academic_year' => '2025',
            'term' => 'First Term',
        ]);

        $this->classB = SchoolClass::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Form 4A Bulawayo',
            'grade' => '4',
            'academic_year' => '2025',
            'term' => 'First Term',
        ]);

        // Subjects
        $this->mathA = Subject::create([
            'school_id' => $this->schoolA->id,
            'code' => 'MTH101',
            'name' => 'Mathematics',
            'is_core' => true,
        ]);

        $this->engA = Subject::create([
            'school_id' => $this->schoolA->id,
            'code' => 'ENG101',
            'name' => 'English Language',
            'is_core' => true,
        ]);

        $this->mathB = Subject::create([
            'school_id' => $this->schoolB->id,
            'code' => 'MTH101B',
            'name' => 'Mathematics Bulawayo',
            'is_core' => true,
        ]);

        // Assign Math to Teacher A
        $this->teacherA->subjects()->attach($this->mathA->id);

        // Rooms
        $this->roomA = Room::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Room 101',
            'code' => 'R101',
            'type' => 'classroom',
            'capacity' => 40,
            'is_active' => true,
        ]);

        $this->roomB = Room::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Room 201 B',
            'code' => 'R201B',
            'type' => 'classroom',
            'capacity' => 35,
            'is_active' => true,
        ]);

        // Periods for School A
        $this->period1A = SchoolPeriod::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Period 1',
            'period_sequence' => 1,
            'start_time' => '08:00',
            'end_time' => '08:45',
            'period_type' => 'lesson',
            'is_active' => true,
        ]);

        $this->period2A = SchoolPeriod::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Period 2',
            'period_sequence' => 2,
            'start_time' => '08:45',
            'end_time' => '09:30',
            'period_type' => 'lesson',
            'is_active' => true,
        ]);

        $this->period3A = SchoolPeriod::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Morning Break',
            'period_sequence' => 3,
            'start_time' => '09:30',
            'end_time' => '10:00',
            'period_type' => 'break',
            'is_active' => true,
        ]);

        // Timetable for School A
        $this->timetableA = Timetable::create([
            'school_id' => $this->schoolA->id,
            'name' => '2025 Term 1 Master Schedule',
            'academic_year' => '2025',
            'term' => 'First Term',
            'status' => 'draft',
            'settings' => [
                'school_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            ],
        ]);
    }

    public function test_period_configuration_and_overlap_prevention(): void
    {
        $periodService = app(TimetablePeriodService::class);

        // Period with invalid start/end time should be rejected
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $periodService->createPeriod($this->schoolA, [
            'name' => 'Invalid Period',
            'period_sequence' => 99,
            'start_time' => '10:00',
            'end_time' => '09:00', // start > end
            'period_type' => 'lesson',
        ]);
    }

    public function test_period_duplicate_sequence_prevention(): void
    {
        $periodService = app(TimetablePeriodService::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $periodService->createPeriod($this->schoolA, [
            'name' => 'Conflicting Sequence Period',
            'period_sequence' => 1, // already taken by Period 1
            'start_time' => '11:00',
            'end_time' => '11:45',
            'period_type' => 'lesson',
        ]);
    }

    public function test_period_time_overlap_prevention(): void
    {
        $periodService = app(TimetablePeriodService::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $periodService->createPeriod($this->schoolA, [
            'name' => 'Overlapping Period',
            'period_sequence' => 10,
            'start_time' => '08:15', // Overlaps with Period 1 (08:00 - 08:45)
            'end_time' => '09:00',
            'period_type' => 'lesson',
        ]);
    }

    public function test_teacher_double_booking_detection(): void
    {
        // Schedule Teacher A for Form 4A on Monday 08:00-08:45
        TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherA->id,
            'room_id' => $this->roomA->id,
            'school_period_id' => $this->period1A->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'status' => 'scheduled',
        ]);

        // Schedule same Teacher A for Form 4B on Monday 08:00-08:45 (Double Booking!)
        TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA2->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherA->id,
            'room_id' => null,
            'school_period_id' => $this->period1A->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'status' => 'scheduled',
        ]);

        $conflictService = app(TimetableConflictService::class);
        $result = $conflictService->detectConflicts($this->timetableA);

        $this->assertTrue($result['has_hard_conflicts']);
        $this->assertGreaterThanOrEqual(1, $result['hard_count']);

        $teacherDouble = collect($result['hard'])->firstWhere('type', 'TEACHER_DOUBLE_BOOKING');
        $this->assertNotNull($teacherDouble);
        $this->assertSame($this->teacherA->id, $teacherDouble->teacherId);
        $this->assertStringContainsString('Tatenda Moyo', $teacherDouble->message);
    }

    public function test_class_double_booking_detection(): void
    {
        // Create second teacher
        $teacher2User = User::factory()->create(['school_id' => $this->schoolA->id]);
        $teacher2 = Teacher::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $teacher2User->id,
            'employee_id' => 'EMP-HAR-02',
            'first_name' => 'Chipo',
            'last_name' => 'Gumbo',
            'email' => 'chipo@hararehigh.ac.zw',
            'status' => true,
        ]);
        $teacher2->subjects()->attach($this->engA->id);

        // Slot 1: Form 4A with Math on Monday 08:00-08:45
        $slot1 = new TimetableSlot([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherA->id,
            'room_id' => $this->roomA->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'status' => 'scheduled',
        ]);

        // Slot 2: Same Form 4A with English at overlapping time 08:30-09:15 (Class Collision!)
        $slot2 = new TimetableSlot([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->engA->id,
            'teacher_id' => $teacher2->id,
            'room_id' => null,
            'day_of_week' => 'Monday',
            'start_time' => '08:30',
            'end_time' => '09:15',
            'status' => 'scheduled',
        ]);

        $conflictService = app(TimetableConflictService::class);
        $result = $conflictService->detectConflicts($this->timetableA, collect([$slot1, $slot2]));

        $this->assertTrue($result['has_hard_conflicts']);
        $classDouble = collect($result['hard'])->firstWhere('type', 'CLASS_DOUBLE_BOOKING');
        $this->assertNotNull($classDouble);
        $this->assertContains($this->classA->id, $classDouble->classIds);
    }

    public function test_room_double_booking_detection(): void
    {
        // Teacher 1 in Room 101 with Form 4A
        TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherA->id,
            'room_id' => $this->roomA->id,
            'day_of_week' => 'Tuesday',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'status' => 'scheduled',
        ]);

        // Another teacher in Room 101 with Form 4B at same time
        $t2User = User::factory()->create(['school_id' => $this->schoolA->id]);
        $t2 = Teacher::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $t2User->id,
            'employee_id' => 'EMP-HAR-03',
            'first_name' => 'Tendai',
            'last_name' => 'Chikore',
            'email' => 'tendai@hararehigh.ac.zw',
            'status' => true,
        ]);
        $t2->subjects()->attach($this->engA->id);

        TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA2->id,
            'subject_id' => $this->engA->id,
            'teacher_id' => $t2->id,
            'room_id' => $this->roomA->id,
            'day_of_week' => 'Tuesday',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'status' => 'scheduled',
        ]);

        $conflictService = app(TimetableConflictService::class);
        $result = $conflictService->detectConflicts($this->timetableA);

        $this->assertTrue($result['has_hard_conflicts']);
        $roomDouble = collect($result['hard'])->firstWhere('type', 'ROOM_DOUBLE_BOOKING');
        $this->assertNotNull($roomDouble);
        $this->assertSame($this->roomA->id, $roomDouble->roomId);
    }

    public function test_fixed_activity_conflict_detection(): void
    {
        // Define whole school Monday assembly
        TimetableFixedActivity::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Monday Assembly',
            'activity_type' => 'assembly',
            'day_of_week' => 'Monday',
            'start_time' => '07:45',
            'end_time' => '08:30',
            'is_locked' => true,
        ]);

        // Schedule a lesson overlapping Monday assembly (08:00 - 08:45)
        TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherA->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'status' => 'scheduled',
        ]);

        $conflictService = app(TimetableConflictService::class);
        $result = $conflictService->detectConflicts($this->timetableA);

        $this->assertTrue($result['has_hard_conflicts']);
        $fixedConflict = collect($result['hard'])->firstWhere('type', 'FIXED_EVENT_CONFLICT');
        $this->assertNotNull($fixedConflict);
        $this->assertStringContainsString('Monday Assembly', $fixedConflict->message);
    }

    public function test_examination_and_national_examination_hard_constraints(): void
    {
        // Define National Exam on Wednesday 08:00-11:00
        TimetableExamination::create([
            'school_id' => $this->schoolA->id,
            'title' => 'ZIMSEC O-Level Mathematics Paper 1',
            'exam_type' => 'national',
            'day_of_week' => 'Wednesday',
            'start_time' => '08:00',
            'end_time' => '11:00',
            'subject_id' => $this->mathA->id,
            'school_class_id' => $this->classA->id,
            'is_locked' => true,
        ]);

        // Schedule a standard lesson on Wednesday 08:00-08:45
        TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherA->id,
            'day_of_week' => 'Wednesday',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'status' => 'scheduled',
        ]);

        $conflictService = app(TimetableConflictService::class);
        $result = $conflictService->detectConflicts($this->timetableA);

        $this->assertTrue($result['has_hard_conflicts']);
        $examConflict = collect($result['hard'])->firstWhere('type', 'NATIONAL_EXAMINATION_CONFLICT');
        $this->assertNotNull($examConflict);
        $this->assertTrue($examConflict->isHard());
    }

    public function test_teacher_subject_mismatch_validation(): void
    {
        // Teacher A is qualified in Mathematics, NOT English
        TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->engA->id, // English
            'teacher_id' => $this->teacherA->id, // Teacher A (Maths)
            'day_of_week' => 'Thursday',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'status' => 'scheduled',
        ]);

        $conflictService = app(TimetableConflictService::class);
        $result = $conflictService->detectConflicts($this->timetableA);

        $this->assertTrue($result['has_hard_conflicts']);
        $mismatch = collect($result['hard'])->firstWhere('type', 'TEACHER_SUBJECT_MISMATCH');
        $this->assertNotNull($mismatch);
        $this->assertSame($this->teacherA->id, $mismatch->teacherId);
    }

    public function test_inactive_and_non_lesson_period_rejection(): void
    {
        // Schedule a lesson in Morning Break period (non-lesson)
        TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherA->id,
            'school_period_id' => $this->period3A->id, // Morning Break
            'slot_type' => 'lesson',
            'day_of_week' => 'Friday',
            'start_time' => '09:30',
            'end_time' => '10:00',
            'status' => 'scheduled',
        ]);

        $conflictService = app(TimetableConflictService::class);
        $result = $conflictService->detectConflicts($this->timetableA);

        $this->assertTrue($result['has_hard_conflicts']);
        $periodConflict = collect($result['hard'])->firstWhere('type', 'NON_LESSON_PERIOD_CONFLICT');
        $this->assertNotNull($periodConflict);
    }

    public function test_duplicate_lesson_detection(): void
    {
        // Schedule identical slot twice in memory collection
        $slot1 = new TimetableSlot([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherA->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'status' => 'scheduled',
        ]);

        $slot2 = new TimetableSlot([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherA->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'status' => 'scheduled',
        ]);

        $conflictService = app(TimetableConflictService::class);
        $result = $conflictService->detectConflicts($this->timetableA, collect([$slot1, $slot2]));

        $this->assertTrue($result['has_hard_conflicts']);
        $dup = collect($result['hard'])->firstWhere('type', 'DUPLICATE_LESSON');
        $this->assertNotNull($dup);
    }

    public function test_tenant_isolation_cross_school_shielding(): void
    {
        // Admin of School A cannot see School B's timetables
        $timetableB = Timetable::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Bulawayo Master Schedule',
            'academic_year' => '2025',
            'term' => 'First Term',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->adminA)->get(route('admin.timetables.show', $timetableB));
        $this->assertContains($response->status(), [403, 404]);
    }

    public function test_cross_tenant_entity_rejection_in_conflict_engine(): void
    {
        // Assign School B's class to School A's timetable slot
        $slotWithForeignClass = new TimetableSlot([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classB->id, // School B Class!
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherA->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'status' => 'scheduled',
        ]);

        $conflictService = app(TimetableConflictService::class);
        $result = $conflictService->detectConflicts($this->timetableA, collect([$slotWithForeignClass]));

        $this->assertTrue($result['has_hard_conflicts']);
        $tenantMismatch = collect($result['hard'])->firstWhere('type', 'TENANT_MISMATCH');
        $this->assertNotNull($tenantMismatch);
    }

    public function test_caller_provided_school_id_cannot_override_tenant_context(): void
    {
        $response = $this->actingAs($this->adminA)->post(route('admin.timetables.store'), [
            'name' => 'Spoofed School Timetable',
            'academic_year' => '2025',
            'term' => 'First Term',
            'school_id' => $this->schoolB->id, // Attacker sends School B's ID
        ]);

        $response->assertRedirect();
        $created = Timetable::where('name', 'Spoofed School Timetable')->first();
        $this->assertNotNull($created);
        // Must be bound strictly to School A, not School B
        $this->assertSame($this->schoolA->id, $created->school_id);
    }

    public function test_weekly_curriculum_requirement_validation(): void
    {
        // Define Curriculum: Form 4A Mathematics requires 5 periods per week
        Curriculum::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherUserA->id,
            'academic_year' => '2025',
            'term' => 'First Term',
            'weekly_periods' => 5,
        ]);

        // Schedule only 2 periods
        TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherA->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'status' => 'scheduled',
        ]);
        TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherA->id,
            'day_of_week' => 'Tuesday',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'status' => 'scheduled',
        ]);

        $validationService = app(TimetableValidationService::class);
        $curriculumReport = $validationService->validateCurriculumRequirements($this->timetableA);

        $this->assertSame(1, $curriculumReport['unmet_count']);
        $item = $curriculumReport['items'][0];
        $this->assertSame(5, $item['required_periods']);
        $this->assertSame(2, $item['scheduled_periods']);
        $this->assertSame(-3, $item['difference']);
        $this->assertFalse($item['is_met']);
    }

    public function test_publish_blocked_when_hard_conflicts_exist(): void
    {
        // Create teacher double booking
        TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherA->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'status' => 'scheduled',
        ]);
        TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA2->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherA->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->adminA)->post(route('admin.timetables.publish', $this->timetableA));
        $response->assertRedirect(route('admin.timetables.conflicts', $this->timetableA));

        $this->timetableA->refresh();
        $this->assertSame('draft', $this->timetableA->status);
        $this->assertNull($this->timetableA->published_at);
    }

    public function test_valid_timetable_passes_validation_and_publishes_successfully(): void
    {
        // Single conflict-free lesson
        TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherA->id,
            'room_id' => $this->roomA->id,
            'school_period_id' => $this->period1A->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->adminA)->post(route('admin.timetables.publish', $this->timetableA));
        $response->assertRedirect(route('admin.timetables.show', $this->timetableA));

        $this->timetableA->refresh();
        $this->assertSame('published', $this->timetableA->status);
        $this->assertNotNull($this->timetableA->published_at);
    }

    public function test_unauthorized_role_is_denied(): void
    {
        $response = $this->actingAs($this->studentUserA)->get(route('admin.timetables.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->studentUserA)->post(route('admin.timetables.store'), [
            'name' => 'Hacked Timetable',
            'academic_year' => '2025',
            'term' => 'First Term',
        ]);
        $response->assertStatus(403);
    }

    public function test_manual_slot_editing_and_simulation_api(): void
    {
        // Check slot simulation endpoint
        $simResponse = $this->actingAs($this->adminA)->postJson(route('admin.timetables.check-conflict', $this->timetableA), [
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherA->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '08:45',
        ]);

        $simResponse->assertStatus(200);
        $simResponse->assertJson(['success' => true]);

        // Create slot via endpoint
        $createResponse = $this->actingAs($this->adminA)->postJson(route('admin.timetables.slots.store', $this->timetableA), [
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherA->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '08:45',
        ]);

        $createResponse->assertStatus(200);
        $createResponse->assertJson(['success' => true]);

        $this->assertDatabaseHas('timetable_slots', [
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA->id,
            'day_of_week' => 'Monday',
        ]);
    }

    public function test_printable_export_views_render_cleanly(): void
    {
        TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherA->id,
            'room_id' => $this->roomA->id,
            'school_period_id' => $this->period1A->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'status' => 'scheduled',
        ]);

        // Master export
        $response = $this->actingAs($this->adminA)->get(route('admin.timetables.export', [
            'timetable' => $this->timetableA,
            'type' => 'master',
        ]));
        $response->assertStatus(200);
        $response->assertSee('Master School Timetable');
        $response->assertSee('Mathematics');

        // Class export
        $response = $this->actingAs($this->adminA)->get(route('admin.timetables.export', [
            'timetable' => $this->timetableA,
            'type' => 'class',
            'class_id' => $this->classA->id,
        ]));
        $response->assertStatus(200);
        $response->assertSee('Class Timetable');
        $response->assertSee('Form 4A');

        // Teacher export
        $response = $this->actingAs($this->adminA)->get(route('admin.timetables.export', [
            'timetable' => $this->timetableA,
            'type' => 'teacher',
            'teacher_id' => $this->teacherA->id,
        ]));
        $response->assertStatus(200);
        $response->assertSee('Teacher Timetable');
        $response->assertSee('Tatenda Moyo');
    }
}
