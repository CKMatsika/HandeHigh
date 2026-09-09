<?php

namespace App\Services\Academic;

use App\Models\PerformanceReport;
use App\Models\School;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PerformanceReportPdfService
{
    /**
     * Generate DomPDF instance for a single PerformanceReport.
     */
    public function generateReportPdf(PerformanceReport $report)
    {
        $report->loadMissing([
            'school',
            'student',
            'schoolClass',
            'subjects.subject',
            'subjects.assignedTeacher',
            'gradeScheme.bands',
            'headmasterUser',
            'deputyUser',
            'stampUser',
        ]);

        $school = $report->school;
        $branding = $school->branding_data;

        $pdf = Pdf::loadView('admin.performance-reports.pdf', [
            'report' => $report,
            'school' => $school,
            'branding' => $branding,
            'subjects' => $report->subjects()->with(['subject', 'assignedTeacher'])->get(),
            'generatedAt' => now(),
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif',
        ]);

        return $pdf;
    }

    /**
     * Download single performance report as PDF.
     */
    public function downloadPdf(PerformanceReport $report): Response
    {
        $pdf = $this->generateReportPdf($report);
        $student = $report->student;
        $filename = "Report_{$student->admission_number}_{$report->academic_year}_T{$report->term}_v{$report->version}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Stream single performance report PDF to browser.
     */
    public function streamPdf(PerformanceReport $report): Response
    {
        $pdf = $this->generateReportPdf($report);
        $student = $report->student;
        $filename = "Report_{$student->admission_number}_{$report->academic_year}_T{$report->term}.pdf";

        return $pdf->stream($filename);
    }
}
