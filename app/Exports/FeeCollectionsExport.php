<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FeeCollectionsExport implements FromArray, WithHeadings, WithStyles
{
    protected array $data;

    public function __construct(array $reportData)
    {
        $this->data = $reportData['collections']->map(function ($row) {
            return [
                $row['date'],
                $row['reference'],
                $row['admission_number'],
                $row['student_name'],
                $row['form'],
                $row['class_name'],
                $row['payment_method'],
                $row['fee_type'],
                number_format($row['amount'], 2, '.', ''),
                $row['cashier'],
                $row['status'],
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Reference #',
            'Admission #',
            'Student / Payer',
            'Form',
            'Class',
            'Payment Method',
            'Fee Type',
            'Amount ($)',
            'Recorded By',
            'Status',
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
