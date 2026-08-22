<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EnrollmentSummaryExport implements FromArray, WithHeadings, WithStyles
{
    protected array $data;

    public function __construct(array $reportData)
    {
        $rows = [];

        // By Form/Grade Section
        foreach ($reportData['by_grade'] as $item) {
            $rows[] = [
                'By Form/Grade',
                $item['grade'],
                $item['male'],
                $item['female'],
                $item['total'],
                $item['percentage'] . '%',
            ];
        }

        // By Class Section
        foreach ($reportData['by_class'] as $item) {
            $rows[] = [
                'By Class',
                $item['class_name'],
                $item['male'],
                $item['female'],
                $item['total'],
                $item['percentage'] . '%',
            ];
        }

        // Totals row
        $rows[] = [
            'GRAND TOTAL',
            'All Grades & Classes',
            $reportData['male_total'],
            $reportData['female_total'],
            $reportData['total_enrollment'],
            '100%',
        ];

        $this->data = $rows;
    }

    public function headings(): array
    {
        return [
            'Category',
            'Group / Name',
            'Male Count',
            'Female Count',
            'Total Students',
            'Percentage of School',
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
