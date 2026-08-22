<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExamResultsExport implements FromArray, WithHeadings, WithStyles
{
    protected array $data;

    public function __construct(array $reportData)
    {
        $this->data = $reportData['rows']->map(function ($row) {
            return [
                $row['academic_year'],
                $row['term'],
                $row['admission_number'],
                $row['student_name'],
                $row['class_name'],
                $row['subject_code'],
                $row['subject_name'],
                $row['score'],
                $row['max_score'],
                $row['percentage'] . '%',
                $row['grade'],
                $row['remarks'],
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            'Academic Year',
            'Term',
            'Admission #',
            'Student Name',
            'Class',
            'Subject Code',
            'Subject Name',
            'Score',
            'Max Score',
            'Percentage',
            'Grade',
            'Remarks',
        ];
    }

    public function array(): array
    {
        return $this->data;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
