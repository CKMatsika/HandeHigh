<?php

namespace App\Exports;

use App\Models\School;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectProfitAndLossExport implements FromArray, WithHeadings, WithStyles, WithTitle
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
        $entityTitle = 'General / All Activities';
        if (!empty($this->reportData['project'])) {
            $entityTitle = 'Project: ' . $this->reportData['project']->name . ' (' . ($this->reportData['project']->code ?? '') . ')';
        } elseif (!empty($this->reportData['cost_center'])) {
            $entityTitle = 'Cost Center: ' . $this->reportData['cost_center']->name;
        }

        $this->rows[] = ['PROFIT AND LOSS STATEMENT', $entityTitle, '', '', ''];
        $this->rows[] = ['Period', $this->reportData['period_label'] ?? '', 'Dates', ($this->reportData['start_date'] ?? '') . ' to ' . ($this->reportData['end_date'] ?? ''), ''];
        $this->rows[] = ['', '', '', '', ''];

        $this->rows[] = ['Section', 'Category', 'Account Code', 'Account Name', 'Amount ($)'];

        // REVENUE
        $this->rows[] = ['REVENUE', '--- REVENUE / INCOME ---', '', '', ''];
        $revAccounts = $this->reportData['revenue_by_account'] ?? [];
        if (empty($revAccounts) || count($revAccounts) === 0) {
            $this->rows[] = ['REVENUE', 'No revenue entries recorded', '', '', '0.00'];
        } else {
            foreach ($revAccounts as $row) {
                $this->rows[] = [
                    'REVENUE',
                    ucwords(str_replace('_', ' ', (string)($row['category'] ?? 'Revenue'))),
                    $row['code'] ?? '',
                    $row['name'] ?? '',
                    number_format((float)($row['amount'] ?? 0), 2, '.', ''),
                ];
            }
        }
        $totalRevenue = (float)($this->reportData['total_revenue'] ?? 0);
        $this->rows[] = ['REVENUE TOTAL', 'TOTAL REVENUE', '', '', number_format($totalRevenue, 2, '.', '')];
        $this->rows[] = ['', '', '', '', ''];

        // DIRECT COSTS / COGS
        $this->rows[] = ['EXPENDITURE', '--- DIRECT OPERATING COSTS ---', '', '', ''];
        $expAccounts = $this->reportData['expense_by_account'] ?? [];
        $directCosts = collect($expAccounts)->where('is_direct', true);
        if ($directCosts->isEmpty()) {
            $this->rows[] = ['EXPENDITURE', 'No direct costs recorded', '', '', '0.00'];
        } else {
            foreach ($directCosts as $row) {
                $this->rows[] = [
                    'DIRECT COST',
                    ucwords(str_replace('_', ' ', (string)($row['category'] ?? 'Expense'))),
                    $row['code'] ?? '',
                    $row['name'] ?? '',
                    number_format((float)($row['amount'] ?? 0), 2, '.', ''),
                ];
            }
        }
        $this->rows[] = ['GROSS PROFIT', 'GROSS PROFIT / MARGIN', '', '', number_format((float)($this->reportData['gross_profit'] ?? 0), 2, '.', '')];
        $this->rows[] = ['', '', '', '', ''];

        // OVERHEADS
        $this->rows[] = ['EXPENDITURE', '--- OVERHEADS & OPERATING EXPENSES ---', '', '', ''];
        $overheads = collect($expAccounts)->where('is_direct', false);
        if ($overheads->isEmpty()) {
            $this->rows[] = ['OVERHEADS', 'No overhead expenses recorded', '', '', '0.00'];
        } else {
            foreach ($overheads as $row) {
                $this->rows[] = [
                    'OVERHEAD',
                    ucwords(str_replace('_', ' ', (string)($row['category'] ?? 'Expense'))),
                    $row['code'] ?? '',
                    $row['name'] ?? '',
                    number_format((float)($row['amount'] ?? 0), 2, '.', ''),
                ];
            }
        }
        $totalExp = (float)($this->reportData['total_expenditure'] ?? 0);
        $this->rows[] = ['EXPENSE TOTAL', 'TOTAL EXPENDITURE', '', '', number_format($totalExp, 2, '.', '')];
        $this->rows[] = ['', '', '', '', ''];

        // NET PROFIT / LOSS
        $net = (float)($this->reportData['net_profit_loss'] ?? 0);
        $this->rows[] = ['SUMMARY', 'NET PROFIT / (LOSS)', '', '', number_format($net, 2, '.', '')];
        $this->rows[] = ['SUMMARY', 'NET PROFIT MARGIN (%)', '', '', ($this->reportData['net_margin_percent'] ?? 0) . '%'];

        // If budget exists
        if (!empty($this->reportData['budget_amount']) && $this->reportData['budget_amount'] > 0) {
            $this->rows[] = ['BUDGET', 'ALLOCATED BUDGET', '', '', number_format((float)$this->reportData['budget_amount'], 2, '.', '')];
            $this->rows[] = ['BUDGET', 'BUDGET VARIANCE (REMAINING)', '', '', number_format((float)($this->reportData['budget_variance'] ?? 0), 2, '.', '')];
            $this->rows[] = ['BUDGET', 'BUDGET UTILIZATION (%)', '', '', ($this->reportData['budget_utilization_percent'] ?? 0) . '%'];
        }
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'P&L Statement';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
        ];
    }
}
