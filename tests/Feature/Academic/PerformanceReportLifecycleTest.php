<?php

namespace Tests\Feature\Academic;

use App\Models\Curriculum;
use App\Models\PerformanceReport;
use App\Models\PerformanceReportSubject;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\GradeSchemeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceReportLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $headmasterUser;
    protected User $deputyUser;
    protected User $mathTeacher;
    protected User $englishTeacher;
    protected SchoolClass $class;
    protected Subject $mathSubject;
    protected Subject $englishSubject;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(GradeSchemeSeeder::class);

        $this->school = School::create([
            'name' => 'Hande High School',
            'code' => 'HHS001',
            'timezone' => 'Africa/Harare',
            'currency' => 'USD',
        ]);

        // Headmaster
        $this->headmasterUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Dr. Headmaster Sibanda',
            'email' => 'headmaster@handehigh.ac.zw',
            'password' => bcrypt('password123'),
        ]);
        $this->headmasterUser->assignRole('headmaster');

        // Deputy Headmaster
        $this->deputyUser = User::create([
            'school_id' => $this->school->id,
            'name' => 'Mr. Deputy Ncube',
            'email' => 'deputy@handehigh.ac.zw',
            'password' => bcrypt('password123'),
        ]);
        $this->deputyUser->assignRole('deputy-headmaster');

        // Teachers
        $this->mathTeacher = User::create([
            'school_id' => $this->school->id,
            'name' => 'Mr. T. Moyo',
            'email' => 'moyo@handehigh.ac.zw',
            'password' => bcrypt('password123'),
        ]);
        $this->mathTeacher->assignRole('teacher');

        $this->englishTeacher = User::create([
            'school_id' => $this->school->id,
            'name' => 'Mrs. S. Dube',
            'email' => 'dube@handehigh.ac.zw',
            'password' => bcrypt('password123'),
        ]);
        $this->englishTeacher->assignRole('teacher');

        // Class
        $this->class = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Form 2A',
            'grade' => 'Form 2',
            'academic_year' => '2026',
            'term' => 'Term 2',
        ]);

        // Subjects
        $this->mathSubject = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'MATH',
            'name' => 'Mathematics',
            'is_core' => true,
        ]);

        $this->englishSubject = Subject::create([
            'school_id' => $this->school->id,
            'code' => 'ENG',
            'name' => 'English Language',
            'is_core' => true,
        ]);

        // Student
        $this->student = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'John',
            'last_name' => 'Moyo',
            'admission_number' => 'HH202600123',
            'class_name' => 'Form 2A',
            'grade' => 'Form 2',
        ]);

        // Curricula
        Curriculum::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->mathSubject->id,
            'teacher_id' => $this->mathTeacher->id,
            'academic_year' => '2026',
            'term' => 'Term 2',
        ]);

        Curriculum::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'subject_id' => $this->englishSubject->id,
            'teacher_id' => $this->englishTeacher->id,
            'academic_year' => '2026',
            'term' => 'Term 2',
        ]);
    }

    public function test_complete_end_to_end_performance_report_lifecycle(): void
    {
        // 1. Initialize Report
        $lifecycleService = app(\App\Services\Academic\PerformanceReportLifecycleService::class);
        $report = $lifecycleService->getOrInitializeReport($this->student, '2026', 'Term 2', $this->class);

        $this->assertEquals(2, $report->subjects()->count());
        $this->assertEquals(PerformanceReport::STATUS_AVAILABLE_FOR_TEACHERS, $report->status);

        $mathItem = $report->subjects()->where('subject_id', $this->mathSubject->id)->first();
        $englishItem = $report->subjects()->where('subject_id', $this->englishSubject->id)->first();

        // 2. Math Teacher Captures 72% and Marks Complete
        $mathResponse = $this->actingAs($this->mathTeacher)
            ->post(route('admin.exams.performance-reports.save-subject', $mathItem->id), [
                'mark_obtained' => 72,
                'max_mark' => 100,
                'result_status' => 'present',
                'comment' => 'Shows a good understanding of algebraic methods.',
                'comment_font' => 'Arial',
                'comment_font_size' => 11,
                'action' => 'mark_complete',
            ]);

        $mathResponse->assertRedirect();
        $mathItem->refresh();
        $this->assertEquals(72.0, $mathItem->percentage);
        $this->assertEquals('B', $mathItem->grade);
        $this->assertTrue($mathItem->is_pass);
        $this->assertEquals('complete', $mathItem->status);

        // 3. English Teacher Captures 68% and Marks Complete
        $engResponse = $this->actingAs($this->englishTeacher)
            ->post(route('admin.exams.performance-reports.save-subject', $englishItem->id), [
                'mark_obtained' => 68,
                'max_mark' => 100,
                'result_status' => 'present',
                'comment' => 'Good comprehension and composition skills.',
                'comment_font' => 'Arial',
                'comment_font_size' => 11,
                'action' => 'mark_complete',
            ]);

        $engResponse->assertRedirect();
        $englishItem->refresh();
        $this->assertEquals(68.0, $englishItem->percentage);
        $this->assertEquals('B', $englishItem->grade);
        $this->assertEquals('complete', $englishItem->status);

        // 4. Verify All Subjects Complete & Report Moves to Awaiting Leadership
        $report->refresh();
        $this->assertEquals(70.0, $report->term_average);
        $this->assertEquals(2, $report->subjects_passed);
        $this->assertEquals(0, $report->subjects_failed);
        $this->assertEquals(PerformanceReport::STATUS_AWAITING_LEADERSHIP, $report->status);

        // 5. Headmaster Enters Comment and Digitally Signs
        $this->actingAs($this->headmasterUser)
            ->post(route('admin.exams.performance-reports.save-comment', $report->id), [
                'type' => 'headmaster',
                'comment' => 'Commendable academic effort this term. Well done.',
            ])
            ->assertRedirect();

        $this->actingAs($this->headmasterUser)
            ->post(route('admin.exams.performance-reports.sign', $report->id), [
                'role_type' => 'headmaster',
            ])
            ->assertRedirect();

        $report->refresh();
        $this->assertTrue($report->isHeadmasterSigned());

        // 6. Deputy Headmaster Enters Comment and Digitally Signs
        $this->actingAs($this->deputyUser)
            ->post(route('admin.exams.performance-reports.save-comment', $report->id), [
                'type' => 'deputy',
                'comment' => 'Very disciplined and consistent performance.',
            ])
            ->assertRedirect();

        $this->actingAs($this->deputyUser)
            ->post(route('admin.exams.performance-reports.sign', $report->id), [
                'role_type' => 'deputy',
            ])
            ->assertRedirect();

        $report->refresh();
        $this->assertTrue($report->isDeputySigned());

        // 7. Apply Official Digital School Stamp
        $this->actingAs($this->headmasterUser)
            ->post(route('admin.exams.performance-reports.stamp', $report->id))
            ->assertRedirect();

        $report->refresh();
        $this->assertTrue($report->isStampApplied());

        // 8. Finalize and Lock Report
        $this->actingAs($this->headmasterUser)
            ->post(route('admin.exams.performance-reports.finalize', $report->id))
            ->assertRedirect();

        $report->refresh();
        $this->assertEquals(PerformanceReport::STATUS_FINALIZED, $report->status);
        $this->assertTrue($report->is_locked);
        $this->assertNotNull($report->finalized_at);

        // 9. Verify Finalized Report is Locked against Teacher Modifications
        $tamperResponse = $this->actingAs($this->mathTeacher)
            ->post(route('admin.exams.performance-reports.save-subject', $mathItem->id), [
                'mark_obtained' => 99,
                'action' => 'save_draft',
            ]);
        $tamperResponse->assertForbidden();

        // 10. Verify PDF Generation
        $pdfResponse = $this->actingAs($this->headmasterUser)
            ->get(route('admin.exams.performance-reports.pdf', $report->id));
        $pdfResponse->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        // 11. Reopen Report for Amendment
        $reopenResponse = $this->actingAs($this->headmasterUser)
            ->post(route('admin.exams.performance-reports.reopen', $report->id), [
                'reason' => 'Adjustment required for coursework mark calculation.',
            ]);

        $reopenResponse->assertRedirect();
        $report->refresh();
        $this->assertEquals('2.0', $report->version);
        $this->assertFalse($report->is_locked);
        $this->assertEquals(PerformanceReport::STATUS_AWAITING_LEADERSHIP, $report->status);

        // Verify Audit Trail Exists
        $this->assertTrue($report->audits()->where('action', 'REPORT_REOPENED')->exists());
    }
}
