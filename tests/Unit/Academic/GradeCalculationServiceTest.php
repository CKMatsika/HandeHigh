<?php

namespace Tests\Unit\Academic;

use App\Models\GradeBand;
use App\Models\GradeScheme;
use App\Models\PerformanceReportSubject;
use App\Services\Academic\GradeCalculationService;
use Database\Seeders\GradeSchemeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GradeCalculationService $gradeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GradeSchemeSeeder::class);
        $this->gradeService = new GradeCalculationService();
    }

    public function test_percentage_calculation_precision_and_boundaries(): void
    {
        $this->assertEquals(72.0, $this->gradeService->calculatePercentage(72, 100));
        $this->assertEquals(85.5, $this->gradeService->calculatePercentage(171, 200));
        $this->assertEquals(100.0, $this->gradeService->calculatePercentage(50, 50));
        $this->assertEquals(0.0, $this->gradeService->calculatePercentage(0, 100));
        $this->assertNull($this->gradeService->calculatePercentage(null, 100));
        $this->assertNull($this->gradeService->calculatePercentage(50, 0));
    }

    public function test_default_zimsec_o_level_grading_boundaries(): void
    {
        // 75+ => A (Distinction, Pass)
        $evalA = $this->gradeService->evaluateGrade(75.0);
        $this->assertEquals('A', $evalA['grade']);
        $this->assertTrue($evalA['is_pass']);
        $this->assertStringContainsString('text-emerald', $evalA['color']);

        // 65 - 74.9 => B (Merit, Pass)
        $evalB = $this->gradeService->evaluateGrade(65.0);
        $this->assertEquals('B', $evalB['grade']);
        $this->assertTrue($evalB['is_pass']);

        // 50 - 64.9 => C (Credit, Pass)
        $evalC = $this->gradeService->evaluateGrade(50.0);
        $this->assertEquals('C', $evalC['grade']);
        $this->assertTrue($evalC['is_pass']);

        // 45 - 49.9 => D (Pass)
        $evalD = $this->gradeService->evaluateGrade(45.0);
        $this->assertEquals('D', $evalD['grade']);
        $this->assertTrue($evalD['is_pass']);

        // 40 - 44.9 => E (Pass)
        $evalE = $this->gradeService->evaluateGrade(40.0);
        $this->assertEquals('E', $evalE['grade']);
        $this->assertTrue($evalE['is_pass']);

        // Below 40 => U (Ungraded / Fail)
        $evalU = $this->gradeService->evaluateGrade(39.9);
        $this->assertEquals('U', $evalU['grade']);
        $this->assertFalse($evalU['is_pass']);
        $this->assertStringContainsString('text-rose', $evalU['color']);
    }

    public function test_special_sitting_statuses(): void
    {
        $absent = $this->gradeService->evaluateGrade(null, 'absent');
        $this->assertEquals('ABS', $absent['grade']);
        $this->assertFalse($absent['is_pass']);
        $this->assertTrue($absent['is_special']);

        $noResult = $this->gradeService->evaluateGrade(null, 'no_result');
        $this->assertEquals('NR', $noResult['grade']);
        $this->assertFalse($noResult['is_pass']);

        $withheld = $this->gradeService->evaluateGrade(null, 'withheld');
        $this->assertEquals('W', $withheld['grade']);
        $this->assertFalse($withheld['is_pass']);
    }

    public function test_term_aggregates_and_academic_standing(): void
    {
        $subjects = collect([
            new PerformanceReportSubject(['percentage' => 78.0, 'is_pass' => true, 'result_status' => 'present']),
            new PerformanceReportSubject(['percentage' => 82.0, 'is_pass' => true, 'result_status' => 'present']),
            new PerformanceReportSubject(['percentage' => 68.0, 'is_pass' => true, 'result_status' => 'present']),
            new PerformanceReportSubject(['percentage' => 74.0, 'is_pass' => true, 'result_status' => 'present']),
            new PerformanceReportSubject(['percentage' => 90.0, 'is_pass' => true, 'result_status' => 'present']),
        ]);

        $aggregates = $this->gradeService->calculateTermAggregates($subjects);

        $this->assertEquals(78.4, $aggregates['term_average']);
        $this->assertEquals('A', $aggregates['overall_grade']);
        $this->assertEquals(5, $aggregates['subjects_passed']);
        $this->assertEquals(0, $aggregates['subjects_failed']);
        $this->assertEquals('DISTINCTION / EXCELLENT', $aggregates['overall_status']);
    }
}
