<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceRegisterExport implements FromArray, WithHeadings, WithStyles
{
    protected array $data;

    public function __construct(array $reportData)
    {
        $this->data = $reportData['rows']->map(function ($row) {
            return [
                $row['date'],
                $row['admission_number'],
                $row['student_name'],
                $row['grade'],
                $row['class_name'],
                $row['status'],
                $row['check_in'],
                $row['check_out'],
                $row['marked_by'],
                $row['notes'],
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            'Date',
            'Admission #',
            'Student Name',
            'Form / Grade',
            'Class',
            'Attendance Status',
            'Check In',
            'Check Out',
            'Marked By',
            'Notes / Remarks',
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
