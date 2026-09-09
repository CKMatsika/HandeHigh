<?php

namespace App\Services\Payroll;

use App\Models\Payroll;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TarmsExportService
{
    /**
     * Generate a ZIMRA TaRMS-compliant CSV export for a payroll.
     *
     * @param Payroll $payroll
     * @return StreamedResponse
     */
    public function exportCsv(Payroll $payroll): StreamedResponse
    {
        $payroll->load(['items.employee.department', 'school']);

        $filename = sprintf(
            'ZIMRA_TaRMS_Payroll_%s_%d_%02d.csv',
            preg_replace('/[^A-Za-z0-9_-]/', '', $payroll->school->name ?? 'School'),
            $payroll->period_year,
            $payroll->period_month
        );

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($payroll) {
            $handle = fopen('php://output', 'w');

            // ZIMRA TaRMS Standard Header
            fputcsv($handle, [
                'Employee TIN',
                'National ID',
                'Employee ID',
                'Full Name',
                'Department',
                'NEC Sector Code',
                'Gross Pay (ZWG)',
                'PAYE Tax (ZWG)',
                'AIDS Levy (ZWG)',
                'NSSA Employee (ZWG)',
                'Gross Pay (USD)',
                'PAYE Tax (USD)',
                'AIDS Levy (USD)',
                'NSSA Employee (USD)',
                'Total ZIMRA Due (ZWG)',
                'Total ZIMRA Due (USD)',
            ]);

            foreach ($payroll->items as $item) {
                $employee = $item->employee;
                $zimraDueZwg = $item->paye_zwg + $item->aids_levy_zwg;
                $zimraDueUsd = $item->paye_usd + $item->aids_levy_usd;

                fputcsv($handle, [
                    $employee?->zimra_tin ?? 'N/A',
                    $employee?->national_id ?? 'N/A',
                    $employee?->employee_id ?? 'N/A',
                    $employee ? ($employee->first_name . ' ' . $employee->last_name) : 'N/A',
                    $employee?->department?->name ?? 'N/A',
                    $employee?->nec_sector_code ?? 'NEC-EDU',
                    number_format($item->gross_zwg, 2, '.', ''),
                    number_format($item->paye_zwg, 2, '.', ''),
                    number_format($item->aids_levy_zwg, 2, '.', ''),
                    number_format($item->nssa_employee_zwg, 2, '.', ''),
                    number_format($item->gross_usd, 2, '.', ''),
                    number_format($item->paye_usd, 2, '.', ''),
                    number_format($item->aids_levy_usd, 2, '.', ''),
                    number_format($item->nssa_employee_usd, 2, '.', ''),
                    number_format($zimraDueZwg, 2, '.', ''),
                    number_format($zimraDueUsd, 2, '.', ''),
                ]);
            }

            // Summary Row
            fputcsv($handle, [
                'TOTALS',
                '',
                '',
                '',
                '',
                '',
                number_format($payroll->total_gross_zwg, 2, '.', ''),
                number_format($payroll->total_paye_zwg, 2, '.', ''),
                number_format($payroll->total_aids_levy_zwg, 2, '.', ''),
                number_format($payroll->items->sum('nssa_employee_zwg'), 2, '.', ''),
                number_format($payroll->total_gross_usd, 2, '.', ''),
                number_format($payroll->total_paye_usd, 2, '.', ''),
                number_format($payroll->total_aids_levy_usd, 2, '.', ''),
                number_format($payroll->items->sum('nssa_employee_usd'), 2, '.', ''),
                number_format($payroll->total_paye_zwg + $payroll->total_aids_levy_zwg, 2, '.', ''),
                number_format($payroll->total_paye_usd + $payroll->total_aids_levy_usd, 2, '.', ''),
            ]);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
