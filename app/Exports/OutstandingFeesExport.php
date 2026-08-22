<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OutstandingFeesExport implements FromArray, WithHeadings, WithStyles
{
    protected array $data;

    public function __construct(array $reportData)
    {
        $this->data = $reportData['rows']->map(function ($row) {
            return [
                $row['admission_number'],
                $row['student_name'],
                $row['form'],
                $row['class_name'],
                $row['invoice_count'],
                number_format($row['total_charges'], 2, '.', ''),
                number_format($row['total_paid'], 2, '.', ''),
                number_format($row['outstanding'], 2, '.', ''),
                $row['collection_rate'] . '%',
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            'Admission #',
            'Student Name',
            'Form',
            'Class',
            'Invoice Count',
            'Total Charges ($)',
            'Total Paid ($)',
            'Outstanding Balance ($)',
            'Collection Rate',
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
