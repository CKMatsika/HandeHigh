<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClassCategoryRegisterExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    private array $rows;

    public function __construct(Collection $students)
    {
        $this->rows = $students->values()->map(function ($student, int $index) {
            $category = $student->funding_status === 'scholarship'
                ? 'Scholarship'
                : ($student->report_is_boarding ? 'Boarding' : 'Day');

            return [
                $index + 1,
                trim(implode(' ', array_filter([$student->first_name, $student->other_names, $student->last_name]))),
                $student->admission_number ?: '',
                $student->registration_number ?: '',
                $student->report_grade ?: '',
                $student->report_class_name ?: '',
                $category,
                ucfirst($student->gender ?: 'Unknown'),
                ucfirst($student->status ?: 'Active'),
            ];
        })->all();
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            '#',
            'Student Name',
            'Admission Number',
            'Registration Number',
            'Grade / Form',
            'Class / Stream',
            'Category',
            'Gender',
            'Status',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Class Category Register';
    }
}
