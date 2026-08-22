<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DebtorsAgingExport implements FromArray, WithHeadings, WithStyles
{
    protected array $data;

    public function __construct(array $reportData)
    {
        $this->data = $reportData['rows']->map(function ($row) {
            return [
                $row['invoice_number'],
                $row['admission_number'],
                $row['student_name'],
                $row['form'],
                $row['class_name'],
                $row['academic_year'],
                $row['term'],
                $row['issued_at'],
                $row['due_date'],
                $row['days_overdue'],
                number_format($row['original_amount'], 2, '.', ''),
                number_format($row['paid_amount'], 2, '.', ''),
                number_format($row['credit_amount'], 2, '.', ''),
                number_format($row['outstanding'], 2, '.', ''),
                $row['bucket_label'],
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            'Invoice #',
            'Admission #',
            'Student Name',
            'Form',
            'Class',
            'Academic Year',
            'Term',
            'Issued Date',
            'Due Date',
            'Days Overdue',
            'Original Amount ($)',
            'Paid Amount ($)',
            'Credit Adj ($)',
            'Outstanding ($)',
            'Aging Bucket',
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
