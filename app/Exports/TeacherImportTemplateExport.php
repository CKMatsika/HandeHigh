<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeacherImportTemplateExport implements FromArray, WithHeadings, WithColumnWidths, WithStyles, WithTitle
{
    public function array(): array { return []; }

    public function headings(): array
    {
        return ['first_name', 'last_name', 'email', 'employee_id', 'phone', 'address', 'gender', 'date_of_birth', 'qualification', 'specialization', 'experience_years', 'hire_date', 'salary'];
    }

    public function title(): string { return 'Teachers'; }

    public function columnWidths(): array
    {
        return ['A' => 18, 'B' => 18, 'C' => 30, 'D' => 18, 'E' => 18, 'F' => 30, 'G' => 14, 'H' => 18, 'I' => 28, 'J' => 24, 'K' => 20, 'L' => 18, 'M' => 14];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '334155']]]];
    }
}
