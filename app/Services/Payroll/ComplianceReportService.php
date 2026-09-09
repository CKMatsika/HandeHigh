<?php

namespace App\Services\Payroll;

use App\Models\Payroll;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplianceReportService
{
    /**
     * Export NSSA P4 Monthly Return CSV
     */
    public function exportNssaP4Csv(Payroll $payroll): StreamedResponse
    {
        $payroll->load(['items.employee.department', 'school']);

        $filename = sprintf('NSSA_P4_Return_%d_%02d.csv', $payroll->period_year, $payroll->period_month);
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($payroll) {
            $handle = fopen('php://output', 'w');

            // NSSA P4 Official Header
            fputcsv($handle, ['NSSA SOCIAL SECURITY SCHEME - MONTHLY CONTRIBUTION RETURN (P4)']);
            fputcsv($handle, ['Employer Name:', $payroll->school->name ?? 'School']);
            fputcsv($handle, ['Period:', $payroll->period_year . '/' . sprintf('%02d', $payroll->period_month)]);
            fputcsv($handle, ['Date Generated:', now()->format('Y-m-d H:i')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'NSSA Number',
                'National ID',
                'Employee Name',
                'Basic Salary (USD)',
                'Insurable Capped (USD)',
                'Employee 4.5% (USD)',
                'Employer 4.5% (USD)',
                'Total NSSA (USD)',
                'Basic Salary (ZWG)',
                'Insurable Capped (ZWG)',
                'Employee 4.5% (ZWG)',
                'Employer 4.5% (ZWG)',
                'Total NSSA (ZWG)',
            ]);

            foreach ($payroll->items as $item) {
                $emp = $item->employee;
                $pensionableUsd = min($item->basic_salary_usd, 700.00);
                $pensionableZwg = min($item->basic_salary_zwg, 19600.00);

                fputcsv($handle, [
                    $emp?->nssa_number ?? 'N/A',
                    $emp?->national_id ?? 'N/A',
                    $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'N/A',
                    number_format($item->basic_salary_usd, 2, '.', ''),
                    number_format($pensionableUsd, 2, '.', ''),
                    number_format($item->nssa_employee_usd, 2, '.', ''),
                    number_format($item->nssa_employer_usd, 2, '.', ''),
                    number_format($item->nssa_employee_usd + $item->nssa_employer_usd, 2, '.', ''),
                    number_format($item->basic_salary_zwg, 2, '.', ''),
                    number_format($pensionableZwg, 2, '.', ''),
                    number_format($item->nssa_employee_zwg, 2, '.', ''),
                    number_format($item->nssa_employer_zwg, 2, '.', ''),
                    number_format($item->nssa_employee_zwg + $item->nssa_employer_zwg, 2, '.', ''),
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'TOTALS',
                '',
                '',
                number_format($payroll->items->sum('basic_salary_usd'), 2, '.', ''),
                '',
                number_format($payroll->items->sum('nssa_employee_usd'), 2, '.', ''),
                number_format($payroll->total_employer_nssa_usd, 2, '.', ''),
                number_format($payroll->items->sum('nssa_employee_usd') + $payroll->total_employer_nssa_usd, 2, '.', ''),
                number_format($payroll->items->sum('basic_salary_zwg'), 2, '.', ''),
                '',
                number_format($payroll->items->sum('nssa_employee_zwg'), 2, '.', ''),
                number_format($payroll->total_employer_nssa_zwg, 2, '.', ''),
                number_format($payroll->items->sum('nssa_employee_zwg') + $payroll->total_employer_nssa_zwg, 2, '.', ''),
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export NEC Monthly Return CSV
     */
    public function exportNecCsv(Payroll $payroll): StreamedResponse
    {
        $payroll->load(['items.employee.department', 'school']);

        $filename = sprintf('NEC_Contribution_Return_%d_%02d.csv', $payroll->period_year, $payroll->period_month);
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($payroll) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['NATIONAL EMPLOYMENT COUNCIL (NEC) - MONTHLY REMITTANCE SCHEDULE']);
            fputcsv($handle, ['Employer Name:', $payroll->school->name ?? 'School']);
            fputcsv($handle, ['Period:', $payroll->period_year . '/' . sprintf('%02d', $payroll->period_month)]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'National ID',
                'Employee Name',
                'Sector Code',
                'Basic Salary (USD)',
                'Employee Portion (USD)',
                'Employer Portion (USD)',
                'Total NEC (USD)',
                'Basic Salary (ZWG)',
                'Employee Portion (ZWG)',
                'Employer Portion (ZWG)',
                'Total NEC (ZWG)',
            ]);

            foreach ($payroll->items as $item) {
                $emp = $item->employee;
                fputcsv($handle, [
                    $emp?->national_id ?? 'N/A',
                    $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'N/A',
                    $emp?->nec_sector_code ?? 'NEC-EDU',
                    number_format($item->basic_salary_usd, 2, '.', ''),
                    number_format($item->nec_employee_usd, 2, '.', ''),
                    number_format($item->nec_employer_usd, 2, '.', ''),
                    number_format($item->nec_employee_usd + $item->nec_employer_usd, 2, '.', ''),
                    number_format($item->basic_salary_zwg, 2, '.', ''),
                    number_format($item->nec_employee_zwg, 2, '.', ''),
                    number_format($item->nec_employer_zwg, 2, '.', ''),
                    number_format($item->nec_employee_zwg + $item->nec_employer_zwg, 2, '.', ''),
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'TOTALS',
                '',
                '',
                number_format($payroll->items->sum('basic_salary_usd'), 2, '.', ''),
                number_format($payroll->items->sum('nec_employee_usd'), 2, '.', ''),
                number_format($payroll->total_employer_nec_usd, 2, '.', ''),
                number_format($payroll->items->sum('nec_employee_usd') + $payroll->total_employer_nec_usd, 2, '.', ''),
                number_format($payroll->items->sum('basic_salary_zwg'), 2, '.', ''),
                number_format($payroll->items->sum('nec_employee_zwg'), 2, '.', ''),
                number_format($payroll->total_employer_nec_zwg, 2, '.', ''),
                number_format($payroll->items->sum('nec_employee_zwg') + $payroll->total_employer_nec_zwg, 2, '.', ''),
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export Master Payroll Summary CSV
     */
    public function exportMasterSummaryCsv(Payroll $payroll): StreamedResponse
    {
        $payroll->load(['items.employee.department', 'school']);

        $filename = sprintf('Payroll_Master_Summary_%d_%02d.csv', $payroll->period_year, $payroll->period_month);
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($payroll) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['CONSOLIDATED MASTER PAYROLL SUMMARY SHEET']);
            fputcsv($handle, ['Organization:', $payroll->school->name ?? 'School']);
            fputcsv($handle, ['Period:', $payroll->period_year . '/' . sprintf('%02d', $payroll->period_month)]);
            fputcsv($handle, ['Status:', strtoupper($payroll->status)]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Employee ID',
                'Full Name',
                'Department',
                'Gross USD',
                'PAYE USD',
                'AIDS Levy USD',
                'NSSA USD',
                'NEC USD',
                'Union USD',
                'Med Aid USD',
                'Loans USD',
                'Total Deductions USD',
                'Net Pay USD',
                'Gross ZWG',
                'PAYE ZWG',
                'AIDS Levy ZWG',
                'NSSA ZWG',
                'NEC ZWG',
                'Union ZWG',
                'Med Aid ZWG',
                'Loans ZWG',
                'Total Deductions ZWG',
                'Net Pay ZWG',
                'Leave Balance (Days)',
            ]);

            foreach ($payroll->items as $item) {
                $emp = $item->employee;
                fputcsv($handle, [
                    $emp?->employee_id ?? 'N/A',
                    $emp ? ($emp->first_name . ' ' . $emp->last_name) : 'N/A',
                    $emp?->department?->name ?? 'N/A',
                    number_format($item->gross_usd, 2, '.', ''),
                    number_format($item->paye_usd, 2, '.', ''),
                    number_format($item->aids_levy_usd, 2, '.', ''),
                    number_format($item->nssa_employee_usd, 2, '.', ''),
                    number_format($item->nec_employee_usd, 2, '.', ''),
                    number_format($item->trade_union_usd, 2, '.', ''),
                    number_format($item->medical_aid_usd, 2, '.', ''),
                    number_format($item->loan_repayment_usd, 2, '.', ''),
                    number_format($item->total_deductions_usd, 2, '.', ''),
                    number_format($item->net_pay_usd, 2, '.', ''),
                    number_format($item->gross_zwg, 2, '.', ''),
                    number_format($item->paye_zwg, 2, '.', ''),
                    number_format($item->aids_levy_zwg, 2, '.', ''),
                    number_format($item->nssa_employee_zwg, 2, '.', ''),
                    number_format($item->nec_employee_zwg, 2, '.', ''),
                    number_format($item->trade_union_zwg, 2, '.', ''),
                    number_format($item->medical_aid_zwg, 2, '.', ''),
                    number_format($item->loan_repayment_zwg, 2, '.', ''),
                    number_format($item->total_deductions_zwg, 2, '.', ''),
                    number_format($item->net_pay_zwg, 2, '.', ''),
                    number_format($item->leave_balance, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
