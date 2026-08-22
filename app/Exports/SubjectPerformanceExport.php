<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SubjectPerformanceExport implements FromArray, WithHeadings, WithStyles
{
    protected array $data;

    public function __construct(array $reportData)
    {
        $this->data = $reportData['rows']->map(function ($row) {
            return [
                $row['subject_code'],
                $row['subject_name'],
                $row['candidates'],
                $row['average'] . '%',
                $row['highest'] . '%',
                $row['lowest'] . '%',
                $row['pass_count'],
                $row['fail_count'],
                $row['pass_rate'] . '%',
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            'Subject Code',
            'Subject Name',
            'Candidates',
            'Average Mark',
            'Highest Mark',
            'Lowest Mark',
            'Passed (>=50%)',
            'Failed (<50%)',
            'Pass Rate',
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
