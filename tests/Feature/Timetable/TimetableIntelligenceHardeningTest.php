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
use App\Models\TimetableRequirement;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\Timetable\Candidate\TimetableCandidateService;
use App\Services\Timetable\Candidate\TimetableSimulationService;
use App\Services\Timetable\Generation\DeterministicAllocator;
use App\Services\Timetable\Generation\TimetableGenerator;
use App\Services\Timetable\Optimization\TimetableOptimizer;
use App\Services\Timetable\Optimization\TimetableScorer;
use App\Services\Timetable\TimetableConflictService;
use App\Services\Timetable\TimetableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TimetableIntelligenceHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected School $schoolA;
    protected School $schoolB;

    protected User $adminA;
    protected User $adminB;
    protected User $studentUserA;
    protected User $teacherUserA;

    protected SchoolClass $classA1;
    protected SchoolClass $classA2;
    protected SchoolClass $classB1;

    protected Subject $mathA;
    protected Subject $engA;
    protected Subject $sciA;
    protected Subject $histA;

    protected Teacher $teacherMathA;
    protected Teacher $teacherEngA;
    protected Teacher $teacherSciA;
    protected Teacher $teacherMultiA;

    protected Room $roomA1;
    protected Room $roomA2;
    protected Room $labA;

    protected Timetable $timetableA;
    protected Timetable $timetableB;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles
        Role::firstOrCreate(['name' => 'super-admin']);
        Role::firstOrCreate(['name' => 'school-admin']);
        Role::firstOrCreate(['name' => 'teacher']);
        Role::firstOrCreate(['name' => 'student']);

        // Schools
        $this->schoolA = School::create([
            'name' => 'Harare High School',
            'code' => 'HAR-HIGH-01',
            'is_active' => true,
        ]);

        $this->schoolB = School::create([
            'name' => 'Bulawayo High School',
            'code' => 'BUL-HIGH-01',
            'is_active' => true,
        ]);

        // Users
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

        $this->studentUserA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'email' => 'student@hararehigh.ac.zw',
        ]);
        $this->studentUserA->assignRole('student');

        $this->teacherUserA = User::factory()->create([
            'school_id' => $this->schoolA->id,
            'email' => 'teacher.math@hararehigh.ac.zw',
        ]);
        $this->teacherUserA->assignRole('teacher');

        // Subjects School A
        $this->mathA = Subject::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Mathematics',
            'code' => 'MTH-101',
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

        $this->histA = Subject::create([
            'school_id' => $this->schoolA->id,
            'name' => 'History',
            'code' => 'HIS-101',
            'is_active' => true,
        ]);

        // Teachers School A
        $this->teacherMathA = Teacher::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $this->teacherUserA->id,
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

        $tMultiUser = User::factory()->create(['school_id' => $this->schoolA->id]);
        $this->teacherMultiA = Teacher::create([
            'school_id' => $this->schoolA->id,
            'user_id' => $tMultiUser->id,
            'employee_id' => 'EMP-HAR-04',
            'first_name' => 'Kudzai',
            'last_name' => 'Dube',
            'email' => 'kudzai.dube@hararehigh.ac.zw',
            'status' => true,
        ]);
        $this->teacherMultiA->subjects()->attach([$this->mathA->id, $this->histA->id]);

        // Classes School A
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

        // Class School B
        $this->classB1 = SchoolClass::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Form 4 Bulawayo',
            'grade' => 'Form 4',
            'academic_year' => '2026',
            'term' => 'Term 1',
        ]);

        // Rooms School A
        $this->roomA1 = Room::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Room 101',
            'code' => 'RM-101',
            'type' => 'classroom',
            'capacity' => 45,
            'is_active' => true,
        ]);

        $this->roomA2 = Room::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Room 102',
            'code' => 'RM-102',
            'type' => 'classroom',
            'capacity' => 45,
            'is_active' => true,
        ]);

        $this->labA = Room::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Science Lab 1',
            'code' => 'LAB-01',
            'type' => 'lab',
            'capacity' => 35,
            'is_active' => true,
        ]);

        // Periods School A (6 periods daily)
        for ($p = 1; $p <= 6; $p++) {
            $startHour = 7 + $p;
            $startFormatted = sprintf('%02d:00', $startHour);
            $endFormatted = sprintf('%02d:45', $startHour);

            SchoolPeriod::create([
                'school_id' => $this->schoolA->id,
                'name' => "Period {$p}",
                'start_time' => $startFormatted,
                'end_time' => $endFormatted,
                'period_sequence' => $p,
                'period_type' => 'lesson',
                'is_active' => true,
            ]);
        }

        // Timetables
        $this->timetableA = Timetable::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Term 1 2026 Master Timetable',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'status' => 'draft',
            'settings' => [
                'school_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            ],
        ]);

        $this->timetableB = Timetable::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Bulawayo Term 1 Timetable',
            'academic_year' => '2026',
            'term' => 'Term 1',
            'status' => 'draft',
            'settings' => [
                'school_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            ],
        ]);
    }

    /**
     * TEST 1: Generation Correctness & Guarantees (Complete vs Impossible).
     */
    public function test_complete_generation_satisfies_all_requirements_with_zero_conflicts(): void
    {
        // 4 subjects for Class A1 (total 10 lessons across 5 days)
        TimetableRequirement::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'weekly_periods' => 3,
            'max_daily_lessons' => 1,
            'priority' => 5,
        ]);

        TimetableRequirement::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->engA->id,
            'teacher_id' => $this->teacherEngA->id,
            'weekly_periods' => 3,
            'max_daily_lessons' => 1,
            'priority' => 3,
        ]);

        TimetableRequirement::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->sciA->id,
            'teacher_id' => $this->teacherSciA->id,
            'room_id' => $this->labA->id,
            'weekly_periods' => 2,
            'max_daily_lessons' => 1,
            'priority' => 4,
        ]);

        TimetableRequirement::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->histA->id,
            'teacher_id' => $this->teacherMultiA->id,
            'weekly_periods' => 2,
            'max_daily_lessons' => 1,
            'priority' => 2,
        ]);

        $generator = app(TimetableGenerator::class);
        $result = $generator->generate($this->timetableA, ['seed' => 'COMPLETE_2026'], $this->adminA);

        $this->assertCount(1, $result['candidates']);
        $candidate = $result['candidates'][0];

        $this->assertEquals(0, $candidate->hard_conflicts_count, 'Candidate must have 0 hard conflicts.');
        $this->assertEmpty($candidate->unallocated_requirements, 'All requirements must be allocated in satisfiable configuration.');
        $this->assertGreaterThan(50.0, $candidate->score, 'Score should reflect valid allocation.');
        $this->assertEquals(10, count($candidate->allocations), 'Should have exactly 10 allocated slots.');
    }

    /**
     * TEST 2: Impossible Configuration Produces Explicit Diagnostics & Zero Invalid Slots.
     */
    public function test_impossible_configuration_diagnoses_blocker_without_corrupting_timetable(): void
    {
        // 1 Teacher (Tendai Moyo) who is the ONLY math teacher
        // Class A1 requires 20 periods of Math (4 per day)
        // Class A2 requires 20 periods of Math (4 per day)
        // But there are only 6 periods per day total (30 periods in week).
        // Total required = 40 periods for 1 teacher across 30 total period slots!
        TimetableRequirement::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'weekly_periods' => 20,
            'max_daily_lessons' => 4,
            'priority' => 1,
        ]);

        TimetableRequirement::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA2->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'weekly_periods' => 20,
            'max_daily_lessons' => 4,
            'priority' => 1,
        ]);

        $generator = app(TimetableGenerator::class);
        $result = $generator->generate($this->timetableA, ['seed' => 'IMPOSSIBLE_TEST'], $this->adminA);

        $candidate = $result['candidates'][0];
        $this->assertEquals(0, $candidate->hard_conflicts_count, 'Even with overconstraint, placed candidate slots must contain 0 hard conflicts.');
        $this->assertNotEmpty($candidate->unallocated_requirements, 'Overconstrained requirements must be recorded as unallocated.');

        $unallocated = $candidate->unallocated_requirements[0];
        $this->assertStringContainsString('Tendai Moyo', $unallocated['summary']);
        $this->assertGreaterThan(0, $unallocated['deficit']);
    }

    /**
     * TEST 3: Real-Time Pre-Application Revalidation (Live Environmental Conflict Rejection).
     */
    public function test_candidate_application_is_blocked_when_live_environmental_conflicts_occur(): void
    {
        // 1. Setup requirement & generate candidate
        TimetableRequirement::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'weekly_periods' => 2,
            'max_daily_lessons' => 1,
        ]);

        $generator = app(TimetableGenerator::class);
        $res = $generator->generate($this->timetableA, ['seed' => 'LIVE_REVAL_TEST'], $this->adminA);
        $candidate = $res['candidates'][0];
        $firstAlloc = $candidate->allocations[0];

        // 2. Scenario A: Live Examination Block introduced on the exact period of firstAlloc
        TimetableExamination::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'title' => 'Midterm Mathematics Exam',
            'day_of_week' => $firstAlloc['day_of_week'],
            'start_time' => $firstAlloc['start_time'],
            'end_time' => $firstAlloc['end_time'],
            'is_national_exam' => false,
        ]);

        // Attempting to apply candidate must throw ValidationException and log audit
        $candidateService = app(TimetableCandidateService::class);

        try {
            $candidateService->applyCandidate($this->timetableA, $candidate, $this->adminA);
            $this->fail('Expected ValidationException when applying candidate with live exam conflict.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('candidate', $e->errors());
            $this->assertStringContainsString('environmental constraints have changed', $e->errors()['candidate'][0]);
        }

        // Verify timetable slots remain empty (no partial write)
        $this->assertEquals(0, $this->timetableA->slots()->count());
    }

    /**
     * TEST 4: Atomic Transaction & Rollback on Exception.
     */
    public function test_transactional_rollback_preserves_database_state_on_failure(): void
    {
        TimetableRequirement::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'weekly_periods' => 2,
        ]);

        $generator = app(TimetableGenerator::class);
        $res = $generator->generate($this->timetableA, ['seed' => 'TRANS_TEST'], $this->adminA);
        $candidate = $res['candidates'][0];

        // Intentionally tamper candidate allocation to cause DB constraint exception (e.g. invalid class ID)
        $tamperedAllocations = $candidate->allocations;
        $tamperedAllocations[0]['school_class_id'] = 999999; // Non-existent class ID

        $candidate->update(['allocations' => $tamperedAllocations]);

        $candidateService = app(TimetableCandidateService::class);

        try {
            $candidateService->applyCandidate($this->timetableA, $candidate, $this->adminA);
            $this->fail('Expected Exception during application.');
        } catch (\Throwable $e) {
            // Expected
        }

        // Verify no orphaned slots exist
        $this->assertEquals(0, $this->timetableA->slots()->count());
        $this->assertFalse($candidate->fresh()->is_applied);
    }

    /**
     * TEST 5: Concurrency & Stale Candidate Protection.
     */
    public function test_stale_candidate_application_is_rejected(): void
    {
        TimetableRequirement::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'weekly_periods' => 2,
        ]);

        $generator = app(TimetableGenerator::class);
        $res = $generator->generate($this->timetableA, ['seed' => 'STALE_TEST'], $this->adminA);
        $candidate = $res['candidates'][0];

        // Simulate Admin B modifying the timetable 10 seconds later
        DB::table('timetable_candidates')->where('id', $candidate->id)->update([
            'created_at' => now()->subSeconds(10),
        ]);
        $this->timetableA->touch();

        $candidateService = app(TimetableCandidateService::class);

        $this->expectException(ValidationException::class);
        $candidateService->applyCandidate($this->timetableA, $candidate, $this->adminA);
    }

    /**
     * TEST 6: Multi-Layer Locked Slot Protection (Manual Edit, Delete, Generation, Optimization).
     */
    public function test_locked_slots_are_strictly_protected_across_all_operations(): void
    {
        $period1 = SchoolPeriod::where('school_id', $this->schoolA->id)->where('period_sequence', 1)->first();

        // 1. Create a locked slot manually
        $lockedSlot = TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_id' => $this->schoolA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'room_id' => $this->roomA1->id,
            'school_period_id' => $period1->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'slot_type' => 'lesson',
            'status' => 'scheduled',
            'is_locked' => true,
        ]);

        $timetableService = app(TimetableService::class);

        // A. Manual Delete of locked slot is blocked
        try {
            $timetableService->deleteSlot($lockedSlot);
            $this->fail('Expected ValidationException when deleting locked slot.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('slot', $e->errors());
        }

        // B. Manual Edit of locked slot schedule is blocked
        try {
            $timetableService->updateSlot($lockedSlot, [
                'day_of_week' => 'Tuesday',
            ]);
            $this->fail('Expected ValidationException when updating locked slot.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('slot', $e->errors());
        }

        // C. Generation preserves the locked slot
        TimetableRequirement::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->engA->id,
            'teacher_id' => $this->teacherEngA->id,
            'weekly_periods' => 2,
        ]);

        $generator = app(TimetableGenerator::class);
        $res = $generator->generate($this->timetableA, ['preserve_locked' => true, 'seed' => 'LOCK_PRESERVE'], $this->adminA);
        $candidate = $res['candidates'][0];

        $candidateService = app(TimetableCandidateService::class);
        $candidateService->applyCandidate($this->timetableA, $candidate, $this->adminA);

        // Verify the original locked slot still exists unchanged
        $persistedLockedSlot = $this->timetableA->slots()->where('is_locked', true)->first();
        $this->assertNotNull($persistedLockedSlot);
        $this->assertEquals('Monday', $persistedLockedSlot->day_of_week);
        $this->assertEquals($this->mathA->id, $persistedLockedSlot->subject_id);
    }

    /**
     * TEST 7: Partial Regeneration Scoping (Class, Subject, Day Boundaries).
     */
    public function test_partial_regeneration_strictly_confines_modifications_to_target_scope(): void
    {
        $period1 = SchoolPeriod::where('school_id', $this->schoolA->id)->where('period_sequence', 1)->first();
        $period2 = SchoolPeriod::where('school_id', $this->schoolA->id)->where('period_sequence', 2)->first();

        // Slot for Class A1
        $slotClassA1 = TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_id' => $this->schoolA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'school_period_id' => $period1->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '08:45',
            'slot_type' => 'lesson',
            'status' => 'scheduled',
            'is_locked' => false,
        ]);

        // Slot for Class A2 (should remain untouched during partial regeneration of Class A1)
        $slotClassA2 = TimetableSlot::create([
            'timetable_id' => $this->timetableA->id,
            'school_id' => $this->schoolA->id,
            'school_class_id' => $this->classA2->id,
            'subject_id' => $this->engA->id,
            'teacher_id' => $this->teacherEngA->id,
            'school_period_id' => $period2->id,
            'day_of_week' => 'Tuesday',
            'start_time' => '09:00',
            'end_time' => '09:45',
            'slot_type' => 'lesson',
            'status' => 'scheduled',
            'is_locked' => false,
        ]);

        $generator = app(TimetableGenerator::class);
        $res = $generator->generate($this->timetableA, [
            'class_id' => $this->classA1->id,
            'seed' => 'PARTIAL_CLASS_A1',
        ], $this->adminA);

        $candidate = $res['candidates'][0];

        // Ensure candidate allocations contain the preserved Class A2 slot
        $preservedA2 = collect($candidate->allocations)->firstWhere('school_class_id', $this->classA2->id);
        $this->assertNotNull($preservedA2, 'Class A2 slot must be preserved when regenerating Class A1 only.');
        $this->assertEquals('Tuesday', $preservedA2['day_of_week']);
    }

    /**
     * TEST 8: Multi-Tenant Penetration Matrix & IDOR Protection.
     */
    public function test_multi_tenant_penetration_matrix_and_idor_denials(): void
    {
        // 1. Candidate A in School A
        TimetableRequirement::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'weekly_periods' => 2,
        ]);
        $generator = app(TimetableGenerator::class);
        $res = $generator->generate($this->timetableA, ['seed' => 'TENANT_PEN_TEST'], $this->adminA);
        $candidateA = $res['candidates'][0];

        // Attack A: Admin B tries to view candidate A
        $response = $this->actingAs($this->adminB)->get(
            route('admin.timetables.candidates.show', [$this->timetableA, $candidateA])
        );
        $this->assertTrue(in_array($response->status(), [403, 404]));

        // Attack B: Admin B tries to apply candidate A
        $response = $this->actingAs($this->adminB)->post(
            route('admin.timetables.candidates.apply', [$this->timetableA, $candidateA])
        );
        $this->assertTrue(in_array($response->status(), [403, 404]));

        // Attack C: Admin B tries to delete requirement in School A
        $reqA = TimetableRequirement::where('school_id', $this->schoolA->id)->first();
        $response = $this->actingAs($this->adminB)->delete(
            route('admin.timetables.requirements.destroy', [$this->timetableA, $reqA])
        );
        $this->assertTrue(in_array($response->status(), [403, 404]));

        // Attack D: Forged school_id submitted in requirement store
        $response = $this->actingAs($this->adminA)->post(
            route('admin.timetables.requirements.store', $this->timetableA),
            [
                'school_id' => $this->schoolB->id, // Forged
                'school_class_id' => $this->classA1->id,
                'subject_id' => $this->mathA->id,
                'weekly_periods' => 3,
            ]
        );
        $created = TimetableRequirement::latest('id')->first();
        $this->assertEquals($this->schoolA->id, $created->school_id, 'Server must enforce active tenant context regardless of client payload.');
    }

    /**
     * TEST 9: Scoring Mathematics & Optimizer Correctness.
     */
    public function test_scoring_mathematics_bounds_and_optimizer_safety(): void
    {
        $period1 = SchoolPeriod::where('school_id', $this->schoolA->id)->where('period_sequence', 1)->first();
        $period2 = SchoolPeriod::where('school_id', $this->schoolA->id)->where('period_sequence', 2)->first();

        $slots = collect([
            new TimetableSlot([
                'timetable_id' => $this->timetableA->id,
                'school_id' => $this->schoolA->id,
                'school_class_id' => $this->classA1->id,
                'subject_id' => $this->mathA->id,
                'teacher_id' => $this->teacherMathA->id,
                'room_id' => $this->roomA1->id,
                'school_period_id' => $period1->id,
                'day_of_week' => 'Monday',
                'start_time' => '08:00',
                'end_time' => '08:45',
                'slot_type' => 'lesson',
                'status' => 'scheduled',
            ]),
            new TimetableSlot([
                'timetable_id' => $this->timetableA->id,
                'school_id' => $this->schoolA->id,
                'school_class_id' => $this->classA1->id,
                'subject_id' => $this->sciA->id,
                'teacher_id' => $this->teacherSciA->id,
                'room_id' => $this->labA->id,
                'school_period_id' => $period2->id,
                'day_of_week' => 'Monday',
                'start_time' => '09:00',
                'end_time' => '09:45',
                'slot_type' => 'lesson',
                'status' => 'scheduled',
            ]),
        ]);

        $scorer = app(TimetableScorer::class);
        $breakdown = $scorer->score($this->timetableA, $slots);

        // Total score in [0.00, 100.00]
        $this->assertGreaterThanOrEqual(0.00, $breakdown->totalScore);
        $this->assertLessThanOrEqual(100.00, $breakdown->totalScore);

        // Sum of category values equals total score
        $sumCategories = array_sum($breakdown->categories);
        $this->assertEqualsWithDelta($breakdown->totalScore, $sumCategories, 0.05);

        // Optimizer never introduces hard conflicts
        $optimizer = app(TimetableOptimizer::class);
        $optResult = $optimizer->optimize($this->timetableA, $slots);

        $conflictService = app(TimetableConflictService::class);
        $conflictCheck = $conflictService->detectConflicts($this->timetableA, $optResult['slots']);
        $this->assertEquals(0, $conflictCheck['hard_count']);
    }

    /**
     * TEST 10: Determinism Verification (5 Consecutive Identical Runs).
     */
    public function test_determinism_5_consecutive_runs_produce_identical_allocations_and_scores(): void
    {
        TimetableRequirement::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'weekly_periods' => 3,
        ]);

        TimetableRequirement::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->engA->id,
            'teacher_id' => $this->teacherEngA->id,
            'weekly_periods' => 2,
        ]);

        $generator = app(TimetableGenerator::class);
        $fixedSeed = 'DETERMINISTIC_VERIFICATION_SEED_2026';

        $firstRun = $generator->generate($this->timetableA, ['seed' => $fixedSeed], $this->adminA);
        $firstCandidate = $firstRun['candidates'][0];
        $firstAllocations = $firstCandidate->allocations;
        $firstScore = $firstCandidate->score;

        for ($run = 1; $run <= 4; $run++) {
            $nextRun = $generator->generate($this->timetableA, ['seed' => $fixedSeed], $this->adminA);
            $nextCandidate = $nextRun['candidates'][0];

            $this->assertEquals($firstScore, $nextCandidate->score, "Run {$run} score must be identical.");
            $this->assertEquals($firstAllocations, $nextCandidate->allocations, "Run {$run} allocations must be 100% identical.");
        }
    }

    /**
     * TEST 11: Non-Destructive In-Memory Simulation Proof.
     */
    public function test_simulation_is_completely_non_destructive(): void
    {
        TimetableRequirement::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'weekly_periods' => 3,
        ]);

        $slotCountBefore = TimetableSlot::count();
        $candidateCountBefore = TimetableCandidate::count();
        $timetableStatusBefore = $this->timetableA->fresh()->status;

        $simService = app(TimetableSimulationService::class);
        $simResult = $simService->simulateGeneration($this->timetableA, ['seed' => 'SIM_PROOF']);

        $this->assertTrue($simResult['is_safe']);
        $this->assertEquals('SAFE TO APPLY', $simResult['status']);

        // Assert database state is 100% unchanged
        $this->assertEquals($slotCountBefore, TimetableSlot::count(), 'Slots count must remain unchanged.');
        $this->assertEquals($candidateCountBefore, TimetableCandidate::count(), 'Candidate records must not be created during simulation.');
        $this->assertEquals($timetableStatusBefore, $this->timetableA->fresh()->status, 'Timetable status must remain unchanged.');
    }

    /**
     * TEST 12: Realistic School Matrix Benchmark (Performance & Reliability).
     */
    public function test_realistic_school_matrix_generation_and_optimization_benchmark(): void
    {
        // Create 8 classes, 12 teachers, 8 subjects, 6 rooms
        $classes = [];
        for ($c = 1; $c <= 8; $c++) {
            $classes[] = SchoolClass::create([
                'school_id' => $this->schoolA->id,
                'name' => "Grade {$c}",
                'grade' => "Grade {$c}",
                'academic_year' => '2026',
                'term' => 'Term 1',
            ]);
        }

        $subjects = [];
        foreach (['Accounting', 'Biology', 'Chemistry', 'French', 'Geography', 'Art', 'Music', 'Commerce'] as $sName) {
            $subjects[] = Subject::create([
                'school_id' => $this->schoolA->id,
                'name' => $sName,
                'code' => 'BENCH-' . strtoupper(substr($sName, 0, 3)) . '-101',
                'is_active' => true,
            ]);
        }

        $teachers = [];
        for ($t = 1; $t <= 12; $t++) {
            $u = User::factory()->create(['school_id' => $this->schoolA->id]);
            $teacher = Teacher::create([
                'school_id' => $this->schoolA->id,
                'user_id' => $u->id,
                'employee_id' => "EMP-HAR-BENCH-{$t}",
                'first_name' => "Teacher{$t}",
                'last_name' => 'Surname',
                'email' => "teacher{$t}@hararehigh.ac.zw",
                'status' => true,
            ]);
            // Attach 2 subjects per teacher
            $teacher->subjects()->attach([
                $subjects[($t - 1) % count($subjects)]->id,
                $subjects[$t % count($subjects)]->id,
            ]);
            $teachers[] = $teacher;
        }

        // Requirements: 3 subjects per class (3 periods each = 9 periods per class = 72 total periods)
        foreach ($classes as $class) {
            for ($s = 0; $s < 3; $s++) {
                $sub = $subjects[$s];
                TimetableRequirement::create([
                    'school_id' => $this->schoolA->id,
                    'timetable_id' => $this->timetableA->id,
                    'school_class_id' => $class->id,
                    'subject_id' => $sub->id,
                    'weekly_periods' => 3,
                    'max_daily_lessons' => 1,
                    'priority' => 1,
                ]);
            }
        }

        $generator = app(TimetableGenerator::class);

        $t0 = microtime(true);
        $result = $generator->generate($this->timetableA, ['candidate_count' => 3, 'seed' => 'REALISTIC_BENCHMARK'], $this->adminA);
        $elapsed = microtime(true) - $t0;

        $this->assertCount(3, $result['candidates']);
        foreach ($result['candidates'] as $candidate) {
            $this->assertEquals(0, $candidate->hard_conflicts_count, 'Every generated candidate must pass all hard constraints.');
            $this->assertGreaterThan(50.0, $candidate->score);
        }

        // Verify reasonable completion time for 3 full candidates on 72-lesson matrix
        $this->assertLessThan(45.0, $elapsed, 'Realistic matrix generation should complete well within operational bounds.');
    }

    /**
     * TEST 13: Complete End-to-End Workflow Verification.
     */
    public function test_complete_end_to_end_timetable_intelligence_lifecycle(): void
    {
        // 1. Create requirement
        $req = TimetableRequirement::create([
            'school_id' => $this->schoolA->id,
            'timetable_id' => $this->timetableA->id,
            'school_class_id' => $this->classA1->id,
            'subject_id' => $this->mathA->id,
            'teacher_id' => $this->teacherMathA->id,
            'room_id' => $this->roomA1->id,
            'weekly_periods' => 3,
            'max_daily_lessons' => 1,
            'priority' => 5,
        ]);
        $this->assertNotNull($req);

        // 2. Generate candidates
        $generator = app(TimetableGenerator::class);
        $genResult = $generator->generate($this->timetableA, ['candidate_count' => 2, 'seed' => 'E2E_SEED'], $this->adminA);
        $this->assertCount(2, $genResult['candidates']);
        $selectedCandidate = $genResult['candidates'][0];

        // 3. Simulate candidate
        $simService = app(TimetableSimulationService::class);
        $sim = $simService->simulateCandidate($this->timetableA, $selectedCandidate);
        $this->assertTrue($sim['is_safe']);

        // 4. Apply candidate
        $candidateService = app(TimetableCandidateService::class);
        $applyResult = $candidateService->applyCandidate($this->timetableA, $selectedCandidate, $this->adminA);
        $this->assertTrue($applyResult['success']);
        $this->assertEquals('generated', $this->timetableA->fresh()->status);
        $this->assertEquals(3, $this->timetableA->slots()->count());

        // 5. Lock one slot
        $slotToLock = $this->timetableA->slots()->first();
        $slotToLock->update(['is_locked' => true]);
        $this->assertTrue($slotToLock->fresh()->is_locked);

        // 6. Publish timetable
        $timetableService = app(TimetableService::class);
        $pubResult = $timetableService->publishTimetable($this->timetableA);
        $this->assertTrue($pubResult['success']);
        $this->assertEquals('published', $this->timetableA->fresh()->status);
        $this->assertNotNull($this->timetableA->fresh()->published_at);
    }
}
