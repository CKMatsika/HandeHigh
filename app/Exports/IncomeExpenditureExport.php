<?php

namespace App\Exports;

use App\Models\School;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class IncomeExpenditureExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected array $rows = [];
    protected ?School $school;
    protected array $reportData;

    public function __construct(array $reportData, ?School $school = null)
    {
        $this->reportData = $reportData;
        $this->school = $school;
        $this->buildExportRows();
    }

    protected function buildExportRows(): void
    {
        $startDate = $this->reportData['start_date'] ?? '';
        $endDate = $this->reportData['end_date'] ?? '';

        $this->rows[] = ['Section', 'Category', 'Account Code', 'Account Name', 'Amount ($)'];

        // Operating Revenue
        $this->rows[] = ['REVENUE', '--- OPERATING REVENUE ---', '', '', ''];
        $revAccounts = $this->reportData['revenue_accounts'] ?? [];
        if (empty($revAccounts) || count($revAccounts) === 0) {
            $this->rows[] = ['REVENUE', 'No revenue entries recorded for this period', '', '', '0.00'];
        } else {
            foreach ($revAccounts as $row) {
                $code = is_array($row) ? ($row['code'] ?? '') : ($row->account->code ?? $row->code ?? '');
                $name = is_array($row) ? ($row['name'] ?? '') : ($row->account->name ?? $row->name ?? '');
                $category = is_array($row) ? ($row['category'] ?? '') : ($row->category ?? '');
                $amount = is_array($row) ? ($row['amount'] ?? 0) : ($row->balance ?? 0);
                $this->rows[] = [
                    'REVENUE',
                    ucwords(str_replace('_', ' ', (string)$category)),
                    $code,
                    $name,
                    number_format((float)$amount, 2, '.', ''),
                ];
            }
        }
        $totalRevenue = (float)($this->reportData['total_income'] ?? 0);
        $this->rows[] = ['REVENUE TOTAL', 'TOTAL OPERATING REVENUE', '', '', number_format($totalRevenue, 2, '.', '')];

        // Blank separator
        $this->rows[] = ['', '', '', '', ''];

        // Operating Expenditure
        $this->rows[] = ['EXPENDITURE', '--- OPERATING EXPENDITURE ---', '', '', ''];
        $expAccounts = $this->reportData['expense_accounts'] ?? [];
        if (empty($expAccounts) || count($expAccounts) === 0) {
            $this->rows[] = ['EXPENDITURE', 'No expenditure entries recorded for this period', '', '', '0.00'];
        } else {
            foreach ($expAccounts as $row) {
                $code = is_array($row) ? ($row['code'] ?? '') : ($row->account->code ?? $row->code ?? '');
                $name = is_array($row) ? ($row['name'] ?? '') : ($row->account->name ?? $row->name ?? '');
                $category = is_array($row) ? ($row['category'] ?? '') : ($row->category ?? '');
                $amount = is_array($row) ? ($row['amount'] ?? 0) : ($row->balance ?? 0);
                $this->rows[] = [
                    'EXPENDITURE',
                    ucwords(str_replace('_', ' ', (string)$category)),
                    $code,
                    $name,
                    number_format((float)$amount, 2, '.', ''),
                ];
            }
        }
        $totalExpenses = (float)($this->reportData['total_expenditure'] ?? 0);
        $this->rows[] = ['EXPENDITURE TOTAL', 'TOTAL OPERATING EXPENDITURE', '', '', number_format($totalExpenses, 2, '.', '')];

        // Blank separator
        $this->rows[] = ['', '', '', '', ''];

        // Net Operating Surplus / Deficit
        $net = (float)($this->reportData['net_surplus_deficit'] ?? ($totalRevenue - $totalExpenses));
        $netLabel = $net >= 0 ? 'NET OPERATING SURPLUS' : 'NET OPERATING DEFICIT';
        $this->rows[] = ['NET POSITION', $netLabel, '', '', number_format($net, 2, '.', '')];
    }

    public function headings(): array
    {
        $schoolName = $this->school?->name ?? 'Hande High School';
        $start = $this->reportData['start_date'] ?? '';
        $end = $this->reportData['end_date'] ?? '';

        return [
            [$schoolName],
            ['INCOME AND EXPENDITURE STATEMENT (PROFIT & LOSS)'],
            ["Period: {$start} to {$end}"],
            [],
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Income & Expenditure';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true, 'size' => 12]],
            3 => ['font' => ['italic' => true, 'size' => 10]],
            5 => ['font' => ['bold' => true]],
        ];
    }
}
