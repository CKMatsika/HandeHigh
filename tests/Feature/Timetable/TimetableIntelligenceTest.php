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
use App\Models\TimetableCandidate;
use App\Models\TimetableExamination;
use App\Models\TimetableFixedActivity;
use App\Models\TimetableGenerationRun;
use App\Models\TimetableRequirement;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\Timetable\Candidate\TimetableCandidateService;
use App\Services\Timetable\Candidate\TimetableComparisonService;
use App\Services\Timetable\Candidate\TimetableSimulationService;
use App\Services\Timetable\Generation\TimetableGenerator;
use App\Services\Timetable\Optimization\TimetableScorer;
use App\Services\Timetable\TimetableConflictService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TimetableIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    protected School $schoolA;
    protected School $schoolB;
    protected User $adminA;
    protected User $adminB;
    protected User $studentUserA;
    protected Teacher $teacherMathA;
    protected Teacher $teacherEngA;
    protected Teacher $teacherSciA;
    protected Teacher $teacherMathB;
    protected SchoolClass $classA1;
    protected SchoolClass $classA2;
    protected SchoolClass $classB;
    protected Subject $mathA;
    protected Subject $engA;
    protected Subject $sciA;
    protected Subject $mathB;
    protected Room $roomA1;
    protected Room $roomA2;
    protected Room $roomB;
    protected Collection $periodsA;
    protected Timetable $timetableA;
    protected Timetable $timetableB;

    protected function setUp(): void
    {
        parent::setUp();

        (new RoleSeeder)->run();
        (new RolePermissionSeeder)->run();

        // Create Schools
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

        // Admins
        $this->adminA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'email' => 'admin@hararehigh.ac.zw',
        ]);
        $this->adminA->assignRole('school-admin');

        $this->adminB = User::factory()->create([
            'school_id' => $this->schoolB->id,
            'email' => 'admin@bulawayo.ac.zw',
        ]);
        $this->adminB->assignRole('school-admin');

        // Student
        $this->studentUserA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'email' => 'student@hararehigh.ac.zw',
        ]);
        $this->studentUserA->assignRole('student');

        // Subjects
        $this->mathA = Subject::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Mathematics',
            'code' => 'MATH-101',
            'is_active' => true,
        ]);

        $this->engA = Subject::create([
            'school_id' => $this->schoolA->id,
            'name' => 'English Language',
            'code' => 'ENG-101',
            'is_active' => true,
        ]);

        $this->sciA = Subject::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Physical Science',
            'code' => 'SCI-101',
            'is_active' => true,
        ]);

        $this->mathB = Subject::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Mathematics',
            'code' => 'MATH-201',
            'is_active' => true,
        ]);

        // Teachers School A
        $tMathUser = User::factory()->create(['school_id' => $this->schoolA->id]);
        $this->teacherMathA = Teacher::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $tMathUser->id,
            'employee_id' => 'EMP-HAR-01',
            'first_name' => 'Tendai',
            'last_name' => 'Moyo',
            'email' => 'tendai.moyo@hararehigh.ac.zw',
            'status' => true,
        ]);
        $this->teacherMathA->subjects()->attach([$this->mathA->id]);

        $tEngUser = User::factory()->create(['school_id' => $this->schoolA->id]);
        $this->teacherEngA = Teacher::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $tEngUser->id,
            'employee_id' => 'EMP-HAR-02',
            'first_name' => 'Rudo',
            'last_name' => 'Chiwara',
            'email' => 'rudo.chiwara@hararehigh.ac.zw',
            'status' => true,
        ]);
        $this->teacherEngA->subjects()->attach([$this->engA->id]);

        $tSciUser = User::factory()->create(['school_id' => $this->schoolA->id]);
        $this->teacherSciA = Teacher::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $tSciUser->id,
            'employee_id' => 'EMP-HAR-03',
            'first_name' => 'Farai',
            'last_name' => 'Ndlovu',
            'email' => 'farai.ndlovu@hararehigh.ac.zw',
            'status' => true,
        ]);
        $this->teacherSciA->subjects()->attach([$this->sciA->id]);

        // Teacher School B
        $tMathUserB = User::factory()->create(['school_id' => $this->schoolB->id]);
        $this->teacherMathB = Teacher::create([
            'school_id' => $this->schoolB->id,
            'user_id' => $tMathUserB->id,
            'employee_id' => 'EMP-BUL-01',
            'first_name' => 'Sipho',
            'last_name' => 'Sibanda',
            'email' => 'sipho.sibanda@bulawayo.ac.zw',
            'status' => true,
        ]);
        $this->teacherMathB->subjects()->attach([$this->mathB->id]);

        // Classes
        $this->classA1 = SchoolClass::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Form 4A',
            'grade' => 'Form 4',
            'academic_year' => '2026',
            'term' => 'Term 1',
        ]);

        $this->classA2 = SchoolClass::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Form 4B',
            'grade' => 'Form 4',
            'academic_year' => '2026',
            'term' => 'Term 1',
        ]);

        $this->classB = SchoolClass::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Form 4 Bulawayo',
            'grade' => 'Form 4',
            'academic_year' => '2026',
            'term' => 'Term 1',
        ]);

        // Rooms
        $this->roomA1 = Room::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Room 12',
            'code' => 'RM-12',
            'type' => 'classroom',
            'capacity' => 45,
            'is_active' => true,
        ]);

        $this->roomA2 = Room::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Science Lab 1',
            'code' => 'LAB-01',
            'type' => 'lab',
            'capacity' => 35,
            'is_active' => true,
        ]);

        $this->roomB = Room::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Bulawayo Room 1',
            'code' => 'BUL-RM-01',
            'type' => 'classroom',
            'capacity' => 40,
            'is_active' => true,
        ]);

        // Periods School A (5 lessons + break + lunch)
        SchoolPeriod::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Period 1',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'period_sequence' => 1,
            'period_type' => 'lesson',
            'is_active' => true,
        ]);

        SchoolPeriod::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Period 2',
            'start_time' => '08:45',
            'end_time' => '09:30',
            'period_sequence' => 2,
            'period_type' => 'lesson',
            'is_active' => true,
        ]);

        SchoolPeriod::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Tea Break',
            'start_time' => '09:30',
            'end_time' => '09:50',
            'period_sequence' => 3,
            'period_type' => 'break',
            'is_active' => true,
        ]);

        SchoolPeriod::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Period 3',
            'start_time' => '09:50',
            'end_time' => '10:35',
            'period_sequence' => 4,
            'period_type' => 'lesson',
            'is_active' => true,
        ]);

        SchoolPeriod::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Period 4',
            'start_time' => '10:35',
            'end_time' => '11:20',
            'period_sequence' => 5,
            'period_type' => 'lesson',
            'is_active' => true,
        ]);

        SchoolPeriod::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Period 5',
            'start_time' => '11:20',
            'end_time' => '12:05',
            'period_sequence' => 6,
            'period_type' => 'lesson',
            'is_active' => true,
        ]);

        // Timetable A
        $this->timetableA = Timetable::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Term 1 Master Schedule 2026',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'status' => 'draft',
            'settings' => [
                'school_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            ],
        ]);

        // Timetable B
        $this->timetableB = Timetable::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Bulawayo Term 1 Schedule',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'status' => 'draft',
            'settings' => [
                'school_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            ],
        ]);
    }

    public function test_generates_valid_deterministic_timetable_with_fixed_seed(): void
    {
        // Setup Curriculum requirements
        Curriculum::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'weekly_periods' => 4,
        ]);

        Curriculum::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA1->id,
            'subject_id' => $this->engA->id,
            'teacher_id' => $this->teacherEngA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'weekly_periods' => 3,
        ]);

        $generator = app(TimetableGenerator::class);

        $seed = '20260823_TEST_SEED';
        $result1 = $generator->generate($this->timetableA, ['seed' => $seed, 'candidate_count' => 1], $this->adminA);
        $result2 = $generator->generate($this->timetableA, ['seed' => $seed, 'candidate_count' => 1], $this->adminA);

        $this->assertCount(1, $result1['candidates']);
        $this->assertCount(1, $result2['candidates']);

        // Assert exact determinism with same seed
        $allocations1 = $result1['candidates'][0]->allocations;
        $allocations2 = $result2['candidates'][0]->allocations;

        $this->assertEquals($allocations1, $allocations2);
        $this->assertEquals($result1['candidates'][0]->score, $result2['candidates'][0]->score);
        $this->assertEquals(0, $result1['candidates'][0]->hard_conflicts_count);
    }

    public function test_generation_respects_all_phase_3c_hard_constraints(): void
    {
        // 2 classes sharing 3 teachers
        Curriculum::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'weekly_periods' => 4,
        ]);

        Curriculum::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA2->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'weekly_periods' => 4,
        ]);

        Curriculum::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA1->id,
            'subject_id' => $this->engA->id,
            'teacher_id' => $this->teacherEngA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'weekly_periods' => 3,
        ]);

        Curriculum::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA2->id,
            'subject_id' => $this->engA->id,
            'teacher_id' => $this->teacherEngA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'weekly_periods' => 3,
        ]);

        $generator = app(TimetableGenerator::class);
        $res = $generator->generate($this->timetableA, ['seed' => 'HARARE_2026', 'candidate_count' => 1], $this->adminA);

        $candidate = $res['candidates'][0];
        $this->assertEquals(0, $candidate->hard_conflicts_count);

        // Verify with Phase 3C conflict service directly
        $slots = collect();
        foreach ($candidate->allocations as $a) {
            $s = new TimetableSlot($a);
            $s->timetable_id = $this->timetableA->id;
            $slots->push($s);
        }

        $conflictService = app(TimetableConflictService::class);
        $conflicts = $conflictService->detectConflicts($this->timetableA, $slots);

        $this->assertFalse($conflicts['has_hard_conflicts']);
        $this->assertEquals(0, $conflicts['hard_count']);
    }

    public function test_generation_respects_fixed_activities_and_examinations(): void
    {
        $period1 = SchoolPeriod::where('school_id', $this->schoolA->id)->where('period_sequence', 1)->first();

        // Fixed Activity: Monday Period 1 (Whole School Assembly)
        TimetableFixedActivity::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'name' => 'Morning Assembly',
            'activity_type' => 'assembly',
            'day_of_week' => 'Monday',
            'school_period_id' => $period1->id,
            'start_time' => $period1->start_time,
            'end_time' => $period1->end_time,
            'is_blocking' => true,
        ]);

        // Examination: Friday Period 1 (ZIMSEC Mock Exam)
        TimetableExamination::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'title' => 'Form 4 Math Mock',
            'day_of_week' => 'Friday',
            'school_period_id' => $period1->id,
            'start_time' => $period1->start_time,
            'end_time' => $period1->end_time,
            'is_national_exam' => true,
        ]);

        Curriculum::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'weekly_periods' => 5,
        ]);

        $generator = app(TimetableGenerator::class);
        $res = $generator->generate($this->timetableA, ['seed' => 'EXAM_TEST_SEED'], $this->adminA);

        $candidate = $res['candidates'][0];
        $this->assertEquals(0, $candidate->hard_conflicts_count);

        // Verify no lesson was scheduled on Monday Period 1 or Friday Period 1 for Class A1
        foreach ($candidate->allocations as $slot) {
            if ($slot['school_class_id'] == $this->classA1->id) {
                $isMonP1 = ($slot['day_of_week'] === 'Monday' && $slot['school_period_id'] == $period1->id);
                $isFriP1 = ($slot['day_of_week'] === 'Friday' && $slot['school_period_id'] == $period1->id);
                $this->assertFalse($isMonP1, 'Lesson should not be placed in Monday Period 1 (Assembly).');
                $this->assertFalse($isFriP1, 'Lesson should not be placed in Friday Period 1 (Examination).');
            }
        }
    }

    public function test_generation_preserves_locked_slots(): void
    {
        $period1 = SchoolPeriod::where('school_id', $this->schoolA->id)->where('period_sequence', 1)->first();

        // Create an explicit locked slot in DB
        $lockedSlot = TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->sciA->id,
            'teacher_id' => $this->teacherSciA->id,
            'room_id' => $this->roomA2->id,
            'school_period_id' => $period1->id,
            'day_of_week' => 'Wednesday',
            'start_time' => $period1->start_time,
            'end_time' => $period1->end_time,
            'is_locked' => true,
            'slot_type' => 'lesson',
            'status' => 'scheduled',
        ]);

        Curriculum::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'weekly_periods' => 4,
        ]);

        Curriculum::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA1->id,
            'subject_id' => $this->sciA->id,
            'teacher_id' => $this->teacherSciA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'weekly_periods' => 3,
        ]);

        $generator = app(TimetableGenerator::class);
        $res = $generator->generate($this->timetableA, ['seed' => 'LOCK_PRESERVE_SEED', 'preserve_locked' => true], $this->adminA);

        $candidate = $res['candidates'][0];
        $this->assertEquals(0, $candidate->hard_conflicts_count);

        // Verify Wednesday Period 1 still contains Science for Class A1 and is marked locked
        $foundLocked = false;
        foreach ($candidate->allocations as $slot) {
            if ($slot['school_class_id'] == $this->classA1->id &&
                $slot['day_of_week'] === 'Wednesday' &&
                $slot['school_period_id'] == $period1->id &&
                $slot['subject_id'] == $this->sciA->id &&
                ! empty($slot['is_locked'])) {
                $foundLocked = true;
                break;
            }
        }

        $this->assertTrue($foundLocked, 'Locked slot must be preserved in generated candidate.');
    }

    public function test_optimizer_calculates_explainable_score_breakdown(): void
    {
        Curriculum::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'weekly_periods' => 4,
        ]);

        $generator = app(TimetableGenerator::class);
        $res = $generator->generate($this->timetableA, ['seed' => 'SCORE_EXPLAIN_SEED'], $this->adminA);

        $candidate = $res['candidates'][0];
        $breakdown = $candidate->score_breakdown;

        $this->assertIsArray($breakdown);
        $this->assertArrayHasKey('total_score', $breakdown);
        $this->assertArrayHasKey('categories', $breakdown);
        $this->assertArrayHasKey('weights', $breakdown);
        $this->assertArrayHasKey('explanations', $breakdown);

        // Check required scoring categories
        $this->assertArrayHasKey('subject_daily_spread', $breakdown['categories']);
        $this->assertArrayHasKey('teacher_workload_balance', $breakdown['categories']);
        $this->assertArrayHasKey('class_workload_balance', $breakdown['categories']);
        $this->assertArrayHasKey('consecutive_lesson_penalty', $breakdown['categories']);
        $this->assertArrayHasKey('core_subject_morning_preference', $breakdown['categories']);
        $this->assertArrayHasKey('teacher_free_period_balance', $breakdown['categories']);
        $this->assertArrayHasKey('room_utilization', $breakdown['categories']);
        $this->assertArrayHasKey('gap_penalty', $breakdown['categories']);

        $this->assertGreaterThan(0.0, $candidate->score);
        $this->assertLessThanOrEqual(100.0, $candidate->score);
    }

    public function test_multiple_candidate_generation_and_ranking(): void
    {
        Curriculum::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'weekly_periods' => 4,
        ]);

        Curriculum::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA1->id,
            'subject_id' => $this->engA->id,
            'teacher_id' => $this->teacherEngA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'weekly_periods' => 4,
        ]);

        $generator = app(TimetableGenerator::class);
        $res = $generator->generate($this->timetableA, [
            'seed' => 'MULTI_CANDIDATE_SEED',
            'candidate_count' => 3,
        ], $this->adminA);

        $this->assertCount(3, $res['candidates']);
        $this->assertEquals(3, TimetableCandidate::where('timetable_id', $this->timetableA->id)->count());

        $scores = array_map(fn ($c) => (float) $c->score, $res['candidates']);
        $this->assertCount(3, $scores);
    }

    public function test_simulation_engine_does_not_mutate_database(): void
    {
        Curriculum::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'weekly_periods' => 3,
        ]);

        $initialSlotsCount = TimetableSlot::count();
        $initialRunsCount = TimetableGenerationRun::count();
        $initialCandidatesCount = TimetableCandidate::count();

        $simService = app(TimetableSimulationService::class);
        $sim = $simService->simulateGeneration($this->timetableA, ['seed' => 'SIM_TEST_SEED']);

        // Assert database state is 100% unchanged
        $this->assertEquals($initialSlotsCount, TimetableSlot::count());
        $this->assertEquals($initialRunsCount, TimetableGenerationRun::count());
        $this->assertEquals($initialCandidatesCount, TimetableCandidate::count());

        // Assert simulation metrics
        $this->assertTrue($sim['is_safe']);
        $this->assertEquals('SAFE TO APPLY', $sim['status']);
        $this->assertEquals(0, $sim['hard_conflicts']);
        $this->assertGreaterThan(0, $sim['projected_score']);
        $this->assertEquals(3, $sim['requirements_fulfilled']);
    }

    public function test_unallocatable_requirement_produces_detailed_blocking_explanation(): void
    {
        // 50 periods requested, but school only has 25 periods per week (5 lessons * 5 days)
        Curriculum::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'weekly_periods' => 50,
        ]);

        $generator = app(TimetableGenerator::class);
        $res = $generator->generate($this->timetableA, ['seed' => 'OVERBOOK_SEED'], $this->adminA);

        $candidate = $res['candidates'][0];
        $this->assertNotEmpty($candidate->unallocated_requirements);

        $unalloc = $candidate->unallocated_requirements[0];
        $this->assertEquals($this->classA1->id, $unalloc['class_id']);
        $this->assertEquals($this->mathA->id, $unalloc['subject_id']);
        $this->assertEquals(50, $unalloc['required_periods']);
        $this->assertGreaterThan(0, $unalloc['deficit']);
        $this->assertNotEmpty($unalloc['reasons']);
        $this->assertStringContainsString('Unable to fully allocate', $unalloc['summary']);
    }

    public function test_candidate_application_is_transactional_and_revalidates_constraints(): void
    {
        Curriculum::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'weekly_periods' => 3,
        ]);

        $generator = app(TimetableGenerator::class);
        $genRes = $generator->generate($this->timetableA, ['seed' => 'APPLY_TEST_SEED'], $this->adminA);
        $candidate = $genRes['candidates'][0];

        $candidateService = app(TimetableCandidateService::class);
        $applyRes = $candidateService->applyCandidate($this->timetableA, $candidate, $this->adminA);

        $this->assertTrue($applyRes['success']);
        $this->assertTrue($candidate->fresh()->is_applied);
        $this->assertEquals('generated', $this->timetableA->fresh()->status);
        $this->assertEquals(3, $this->timetableA->slots()->count());
    }

    public function test_candidate_application_rolls_back_if_environmental_hard_conflict_introduced(): void
    {
        Curriculum::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'weekly_periods' => 3,
        ]);

        $generator = app(TimetableGenerator::class);
        $genRes = $generator->generate($this->timetableA, ['seed' => 'ROLLBACK_TEST_SEED'], $this->adminA);
        $candidate = $genRes['candidates'][0];

        // Now introduce a conflicting national examination across ALL periods on Monday
        $period1 = SchoolPeriod::where('school_id', $this->schoolA->id)->where('period_sequence', 1)->first();
        TimetableExamination::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'title' => 'National ZIMSEC Blocking Exam',
            'day_of_week' => $candidate->allocations[0]['day_of_week'],
            'school_period_id' => $candidate->allocations[0]['school_period_id'],
            'start_time' => $candidate->allocations[0]['start_time'],
            'end_time' => $candidate->allocations[0]['end_time'],
            'is_national_exam' => true,
        ]);

        $candidateService = app(TimetableCandidateService::class);

        $this->expectException(ValidationException::class);
        $candidateService->applyCandidate($this->timetableA, $candidate, $this->adminA);
    }

    public function test_cross_tenant_generation_isolation_shield(): void
    {
        // Admin B (School B) tries to generate for School A Timetable -> rejected by tenant-scoped model binding (404)
        $response = $this->actingAs($this->adminB)->postJson(
            route('admin.timetables.generate.run', $this->timetableA),
            ['seed' => 'ATTACK_SEED']
        );

        $this->assertTrue(in_array($response->status(), [403, 404]), 'Cross-tenant generation must be rejected with 403 or 404.');
    }

    public function test_cross_tenant_candidate_access_and_application_prevention(): void
    {
        Curriculum::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'weekly_periods' => 2,
        ]);

        $generator = app(TimetableGenerator::class);
        $res = $generator->generate($this->timetableA, ['seed' => 'TENANT_TEST'], $this->adminA);
        $candidateA = $res['candidates'][0];

        // School B admin tries to view candidate A
        $response = $this->actingAs($this->adminB)->get(
            route('admin.timetables.candidates.show', [$this->timetableA, $candidateA])
        );
        $this->assertTrue(in_array($response->status(), [403, 404]), 'Cross-tenant candidate viewing must be rejected with 403 or 404.');

        // School B admin tries to apply candidate A
        $applyResponse = $this->actingAs($this->adminB)->post(
            route('admin.timetables.candidates.apply', [$this->timetableA, $candidateA])
        );
        $this->assertTrue(in_array($applyResponse->status(), [403, 404]), 'Cross-tenant candidate application must be rejected with 403 or 404.');
    }

    public function test_unauthorized_role_is_denied_generation_and_application(): void
    {
        // Student user tries to run generation
        $response = $this->actingAs($this->studentUserA)->post(
            route('admin.timetables.generate.run', $this->timetableA),
            ['seed' => 'STUDENT_ATTACK']
        );

        $response->assertStatus(403);
    }

    public function test_slot_lock_and_unlock_endpoints(): void
    {
        $period1 = SchoolPeriod::where('school_id', $this->schoolA->id)->where('period_sequence', 1)->first();

        $slot = TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'room_id' => $this->roomA1->id,
            'school_period_id' => $period1->id,
            'day_of_week' => 'Monday',
            'start_time' => $period1->start_time,
            'end_time' => $period1->end_time,
            'is_locked' => false,
            'slot_type' => 'lesson',
            'status' => 'scheduled',
        ]);

        $this->assertFalse($slot->isLocked());

        // Lock slot
        $lockRes = $this->actingAs($this->adminA)->postJson(
            route('admin.timetables.slots.lock', [$this->timetableA, $slot])
        );
        $lockRes->assertOk();
        $this->assertTrue($slot->fresh()->isLocked());

        // Unlock slot
        $unlockRes = $this->actingAs($this->adminA)->postJson(
            route('admin.timetables.slots.unlock', [$this->timetableA, $slot])
        );
        $unlockRes->assertOk();
        $this->assertFalse($slot->fresh()->isLocked());
    }

    public function test_requirements_crud_and_curriculum_import(): void
    {
        Curriculum::create([
            'school_id' => $this->schoolA->id,
            'class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'academic_year' => '2026',
            'term' => 'Term 1',
            'weekly_periods' => 5,
        ]);

        // Import from curriculum
        $importRes = $this->actingAs($this->adminA)->post(
            route('admin.timetables.requirements.import', $this->timetableA)
        );
        $importRes->assertRedirect(route('admin.timetables.requirements.index', $this->timetableA));

        $this->assertEquals(1, TimetableRequirement::where('timetable_id', $this->timetableA->id)->count());

        // Add custom requirement
        $addRes = $this->actingAs($this->adminA)->post(
            route('admin.timetables.requirements.store', $this->timetableA),
            [
                'school_class_id' => $this->classA2->id,
                'subject_id' => $this->sciA->id,
                'teacher_id' => $this->teacherSciA->id,
                'room_id' => $this->roomA2->id,
                'weekly_periods' => 4,
                'max_daily_lessons' => 2,
                'priority' => 5,
            ]
        );
        $addRes->assertRedirect(route('admin.timetables.requirements.index', $this->timetableA));
        $this->assertEquals(2, TimetableRequirement::where('timetable_id', $this->timetableA->id)->count());

        // Delete requirement
        $req = TimetableRequirement::where('school_class_id', $this->classA2->id)->first();
        $delRes = $this->actingAs($this->adminA)->delete(
            route('admin.timetables.requirements.destroy', [$this->timetableA, $req])
        );
        $delRes->assertRedirect(route('admin.timetables.requirements.index', $this->timetableA));
        $this->assertEquals(1, TimetableRequirement::where('timetable_id', $this->timetableA->id)->count());
    }
}
