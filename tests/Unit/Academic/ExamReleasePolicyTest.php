<?php

namespace Tests\Unit\Academic;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\Timetable;
use App\Models\TimetableExamination;
use App\Services\Academic\ExamReleasePolicyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamReleasePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected ExamReleasePolicyService $policyService;
    protected School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policyService = new ExamReleasePolicyService();
        $this->school = School::create([
            'name' => 'Hande High School',
            'code' => 'HHS001',
            'timezone' => 'Africa/Harare',
            'currency' => 'USD',
        ]);
    }

    public function test_report_unavailable_when_final_exam_is_still_running(): void
    {
        $timetable = Timetable::create([
            'school_id' => $this->school->id,
            'name' => 'End of Term 2 Exam',
            'academic_year' => '2026',
            'term' => 'Term 2',
            'status' => 'published',
        ]);

        // Final exam scheduled for 31 August 2026 14:00 - 16:00
        TimetableExamination::create([
            'school_id' => $this->school->id,
            'timetable_id' => $timetable->id,
            'title' => 'Physics Paper 2',
            'exam_type' => 'internal',
            'exam_date' => '2026-08-31',
            'day_of_week' => 'Monday',
            'start_time' => '14:00',
            'end_time' => '16:00',
            'is_locked' => true,
        ]);

        // Simulated time: 31 August 2026 15:30 (Exam still in progress)
        $simulatedNow = Carbon::parse('2026-08-31 15:30:00', 'Africa/Harare');

        $status = $this->policyService->getReleaseStatus($this->school, '2026', 'Term 2', $simulatedNow);
        $this->assertFalse($status['is_available']);
        $this->assertStringContainsString('still active', $status['message']);
    }

    public function test_report_becomes_available_immediately_after_final_exam_ends(): void
    {
        $timetable = Timetable::create([
            'school_id' => $this->school->id,
            'name' => 'End of Term 2 Exam',
            'academic_year' => '2026',
            'term' => 'Term 2',
            'status' => 'published',
        ]);

        TimetableExamination::create([
            'school_id' => $this->school->id,
            'timetable_id' => $timetable->id,
            'title' => 'Chemistry Paper 1',
            'exam_type' => 'internal',
            'exam_date' => '2026-08-31',
            'day_of_week' => 'Monday',
            'start_time' => '14:00',
            'end_time' => '16:00',
            'is_locked' => true,
        ]);

        // Simulated time: 31 August 2026 16:01 (Exam ended)
        $simulatedNow = Carbon::parse('2026-08-31 16:01:00', 'Africa/Harare');

        $status = $this->policyService->getReleaseStatus($this->school, '2026', 'Term 2', $simulatedNow);
        $this->assertTrue($status['is_available']);
        $this->assertStringContainsString('available for teacher marks entry', $status['message']);
    }

    public function test_delayed_release_policy_enforcement(): void
    {
        // Configure 24h delay
        SchoolSetting::create([
            'school_id' => $this->school->id,
            'key' => 'performance_report_release_policy',
            'value' => 'auto_delay_hours',
            'type' => 'string',
        ]);

        SchoolSetting::create([
            'school_id' => $this->school->id,
            'key' => 'performance_report_delay_hours',
            'value' => '24',
            'type' => 'integer',
        ]);

        $timetable = Timetable::create([
            'school_id' => $this->school->id,
            'name' => 'Term 2 Exams',
            'academic_year' => '2026',
            'term' => 'Term 2',
            'status' => 'published',
        ]);

        TimetableExamination::create([
            'school_id' => $this->school->id,
            'timetable_id' => $timetable->id,
            'title' => 'Biology Paper 1',
            'exam_type' => 'internal',
            'exam_date' => '2026-08-31',
            'day_of_week' => 'Monday',
            'start_time' => '14:00',
            'end_time' => '16:00',
            'is_locked' => true,
        ]);

        // 12 hours after exam (Still within delay window)
        $twelveHoursLater = Carbon::parse('2026-09-01 04:00:00', 'Africa/Harare');
        $this->assertFalse($this->policyService->isReportAvailable($this->school, '2026', 'Term 2', $twelveHoursLater));

        // 25 hours after exam (Delay window passed)
        $twentyFiveHoursLater = Carbon::parse('2026-09-01 17:01:00', 'Africa/Harare');
        $this->assertTrue($this->policyService->isReportAvailable($this->school, '2026', 'Term 2', $twentyFiveHoursLater));
    }
}
