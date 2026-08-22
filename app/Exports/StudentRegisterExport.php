<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentRegisterExport implements FromArray, WithHeadings, WithStyles
{
    protected array $data;

    public function __construct(array $reportData)
    {
        $this->data = $reportData['rows']->map(function ($row) {
            return [
                $row['admission_number'],
                $row['full_name'],
                $row['gender'],
                $row['date_of_birth'],
                $row['age'],
                $row['grade'],
                $row['class_name'],
                $row['is_boarding'],
                $row['status'],
                $row['guardian_name'],
                $row['guardian_phone'],
                $row['guardian_email'],
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            'Admission #',
            'Full Name',
            'Gender',
            'Date of Birth',
            'Age',
            'Grade / Form',
            'Class',
            'Type',
            'Status',
            'Primary Guardian',
            'Guardian Phone',
            'Guardian Email',
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
