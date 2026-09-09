<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\StatutoryRate;
use Carbon\Carbon;

class ZimbabwePayrollService
{
    /**
     * Calculate dual-currency statutory compliance and net pay for an employee.
     *
     * @param Employee $employee
     * @param array $inputs Contains USD and ZWG earnings/deductions
     * @param int|null $schoolId
     * @param string|null $effectiveDate
     * @return array
     */
    public function calculateEmployeePayroll(Employee $employee, array $inputs, ?int $schoolId = null, ?string $effectiveDate = null): array
    {
        $date = $effectiveDate ?? now()->toDateString();

        // 1. Fetch Dynamic Statutory Configuration
        $rates = $this->loadStatutoryRates($schoolId, $date);

        // 2. Parse USD Inputs
        $basicUsd = (float)($inputs['basic_salary_usd'] ?? $inputs['basic_salary'] ?? 0);
        $housingUsd = (float)($inputs['housing_allowance_usd'] ?? $inputs['housing_allowance'] ?? 0);
        $transportUsd = (float)($inputs['transport_allowance_usd'] ?? $inputs['transport_allowance'] ?? 0);
        $commUsd = (float)($inputs['communication_allowance_usd'] ?? $inputs['communication_allowance'] ?? 0);
        $eduUsd = (float)($inputs['education_allowance_usd'] ?? $inputs['education_allowance'] ?? 0);
        $leaveUsd = (float)($inputs['leave_allowance_usd'] ?? $inputs['leave_allowance'] ?? 0);
        $topUpUsd = (float)($inputs['school_top_up_usd'] ?? $inputs['school_top_up'] ?? 0);
        $bonusUsd = (float)($inputs['bonus_usd'] ?? $inputs['bonus'] ?? 0);
        $overtimeUsd = (float)($inputs['overtime_usd'] ?? $inputs['overtime'] ?? 0);
        $otherEarningsUsd = (float)($inputs['other_earnings_usd'] ?? $inputs['other_earnings'] ?? 0);

        $allowancesUsd = $housingUsd + $transportUsd + $commUsd + $eduUsd + $leaveUsd + $topUpUsd + $otherEarningsUsd;
        $grossUsd = $basicUsd + $allowancesUsd + $bonusUsd + $overtimeUsd;

        // 3. Parse ZWG Inputs
        $basicZwg = (float)($inputs['basic_salary_zwg'] ?? 0);
        $housingZwg = (float)($inputs['housing_allowance_zwg'] ?? 0);
        $transportZwg = (float)($inputs['transport_allowance_zwg'] ?? 0);
        $commZwg = (float)($inputs['communication_allowance_zwg'] ?? 0);
        $eduZwg = (float)($inputs['education_allowance_zwg'] ?? 0);
        $leaveZwg = (float)($inputs['leave_allowance_zwg'] ?? 0);
        $topUpZwg = (float)($inputs['school_top_up_zwg'] ?? 0);
        $bonusZwg = (float)($inputs['bonus_zwg'] ?? 0);
        $overtimeZwg = (float)($inputs['overtime_zwg'] ?? 0);
        $otherEarningsZwg = (float)($inputs['other_earnings_zwg'] ?? 0);

        $allowancesZwg = $housingZwg + $transportZwg + $commZwg + $eduZwg + $leaveZwg + $topUpZwg + $otherEarningsZwg;
        $grossZwg = $basicZwg + $allowancesZwg + $bonusZwg + $overtimeZwg;

        // 4. Calculate NSSA (Employee 4.5% capped, Employer 4.5% capped)
        $nssaEmpRate = $rates['nssa_employee_rate'];
        $nssaEmprRate = $rates['nssa_employer_rate'];

        $nssaCapUsd = $rates['nssa_cap_usd'];
        $pensionableUsd = min($basicUsd, $nssaCapUsd);
        $nssaEmpUsd = round($pensionableUsd * ($nssaEmpRate / 100), 2);
        $nssaEmprUsd = round($pensionableUsd * ($nssaEmprRate / 100), 2);

        $nssaCapZwg = $rates['nssa_cap_zwg'];
        $pensionableZwg = min($basicZwg, $nssaCapZwg);
        $nssaEmpZwg = round($pensionableZwg * ($nssaEmpRate / 100), 2);
        $nssaEmprZwg = round($pensionableZwg * ($nssaEmprRate / 100), 2);

        // 5. Calculate NEC (based on employee nec_sector_code)
        $sectorCode = $inputs['nec_sector_code'] ?? $employee->nec_sector_code ?? 'NEC-EDU';
        $necConfig = $this->getNecConfig($sectorCode, $rates);

        $necEmpUsd = 0;
        $necEmprUsd = 0;
        if ($basicUsd > 0) {
            $necEmpUsd = $necConfig['rate_percentage'] > 0
                ? round($basicUsd * ($necConfig['rate_percentage'] / 100), 2)
                : (float)$necConfig['flat_amount'];
            $necEmprUsd = $necEmpUsd; // Matching employer contribution under CBA
        }

        $necEmpZwg = 0;
        $necEmprZwg = 0;
        if ($basicZwg > 0) {
            $necEmpZwg = $necConfig['rate_percentage'] > 0
                ? round($basicZwg * ($necConfig['rate_percentage'] / 100), 2)
                : (float)$necConfig['flat_amount'];
            $necEmprZwg = $necEmpZwg;
        }

        // Custom manual override if provided
        if (isset($inputs['nec_usd'])) $necEmpUsd = (float)$inputs['nec_usd'];
        if (isset($inputs['nec_zwg'])) $necEmpZwg = (float)$inputs['nec_zwg'];

        // 6. Trade Union Deductions
        $isUnion = isset($inputs['trade_union_member'])
            ? (bool)$inputs['trade_union_member']
            : (bool)$employee->trade_union_member;

        $tradeUnionUsd = 0;
        $tradeUnionZwg = 0;
        if ($isUnion) {
            $unionRate = (float)($inputs['trade_union_rate'] ?? $employee->trade_union_rate ?? 0);
            $unionFlat = (float)($inputs['trade_union_flat_amount'] ?? $employee->trade_union_flat_amount ?? 0);

            if ($unionRate > 0) {
                $tradeUnionUsd = round($basicUsd * ($unionRate / 100), 2);
                $tradeUnionZwg = round($basicZwg * ($unionRate / 100), 2);
            } elseif ($unionFlat > 0) {
                $tradeUnionUsd = $unionFlat;
            }
        }
        if (isset($inputs['trade_union_usd'])) $tradeUnionUsd = (float)$inputs['trade_union_usd'];
        if (isset($inputs['trade_union_zwg'])) $tradeUnionZwg = (float)$inputs['trade_union_zwg'];

        // 7. Medical Aid & ZIMRA 50% Tax Credit
        $medAidUsd = (float)($inputs['medical_aid_usd'] ?? $employee->medical_aid_usd ?? 0);
        $medAidZwg = (float)($inputs['medical_aid_zwg'] ?? $employee->medical_aid_zwg ?? 0);

        $medCreditRatio = ($rates['medical_aid_credit_percentage'] ?? 50.0) / 100;
        $medCreditUsd = round($medAidUsd * $medCreditRatio, 2);
        $medCreditZwg = round($medAidZwg * $medCreditRatio, 2);

        // 8. Progressive PAYE Calculations
        $grossPayeUsd = $this->calculateProgressiveTax($grossUsd, $rates['usd_brackets']);
        $payeUsd = max(0, round($grossPayeUsd - $medCreditUsd, 2));

        $grossPayeZwg = $this->calculateProgressiveTax($grossZwg, $rates['zwg_brackets']);
        $payeZwg = max(0, round($grossPayeZwg - $medCreditZwg, 2));

        // 9. AIDS Levy (3% of Net PAYE)
        $aidsRate = $rates['aids_levy_rate'];
        $aidsLevyUsd = round($payeUsd * ($aidsRate / 100), 2);
        $aidsLevyZwg = round($payeZwg * ($aidsRate / 100), 2);

        // 10. Voluntary Deductions (Loans & Others)
        $loanUsd = (float)($inputs['loan_repayment_usd'] ?? $inputs['loan_repayment'] ?? 0);
        $loanZwg = (float)($inputs['loan_repayment_zwg'] ?? 0);

        $otherDeductUsd = (float)($inputs['other_deductions_usd'] ?? $inputs['other_deductions'] ?? 0);
        $otherDeductZwg = (float)($inputs['other_deductions_zwg'] ?? 0);

        // 11. Total Deductions & Net Pay
        $totalDeductionsUsd = round($payeUsd + $aidsLevyUsd + $nssaEmpUsd + $necEmpUsd + $tradeUnionUsd + $medAidUsd + $loanUsd + $otherDeductUsd, 2);
        $netPayUsd = max(0, round($grossUsd - $totalDeductionsUsd, 2));

        $totalDeductionsZwg = round($payeZwg + $aidsLevyZwg + $nssaEmpZwg + $necEmpZwg + $tradeUnionZwg + $medAidZwg + $loanZwg + $otherDeductZwg, 2);
        $netPayZwg = max(0, round($grossZwg - $totalDeductionsZwg, 2));

        // Determine currency mode
        $currencyMode = 'USD';
        if ($grossUsd > 0 && $grossZwg > 0) {
            $currencyMode = 'DUAL';
        } elseif ($grossZwg > 0 && $grossUsd == 0) {
            $currencyMode = 'ZWG';
        }

        return [
            'currency_mode' => $currencyMode,

            // USD Breakdown
            'basic_salary_usd' => $basicUsd,
            'housing_allowance_usd' => $housingUsd,
            'transport_allowance_usd' => $transportUsd,
            'communication_allowance_usd' => $commUsd,
            'education_allowance_usd' => $eduUsd,
            'leave_allowance_usd' => $leaveUsd,
            'school_top_up_usd' => $topUpUsd,
            'allowances_usd' => $allowancesUsd,
            'bonus_usd' => $bonusUsd,
            'overtime_usd' => $overtimeUsd,
            'other_earnings_usd' => $otherEarningsUsd,
            'gross_usd' => $grossUsd,

            'gross_paye_usd' => $grossPayeUsd,
            'medical_aid_usd' => $medAidUsd,
            'medical_aid_tax_credit_usd' => $medCreditUsd,
            'paye_usd' => $payeUsd,
            'aids_levy_usd' => $aidsLevyUsd,
            'nssa_employee_usd' => $nssaEmpUsd,
            'nssa_employer_usd' => $nssaEmprUsd,
            'nec_employee_usd' => $necEmpUsd,
            'nec_employer_usd' => $necEmprUsd,
            'trade_union_usd' => $tradeUnionUsd,
            'loan_repayment_usd' => $loanUsd,
            'other_deductions_usd' => $otherDeductUsd,
            'total_deductions_usd' => $totalDeductionsUsd,
            'net_pay_usd' => $netPayUsd,

            // ZWG Breakdown
            'basic_salary_zwg' => $basicZwg,
            'housing_allowance_zwg' => $housingZwg,
            'transport_allowance_zwg' => $transportZwg,
            'communication_allowance_zwg' => $commZwg,
            'education_allowance_zwg' => $eduZwg,
            'leave_allowance_zwg' => $leaveZwg,
            'school_top_up_zwg' => $topUpZwg,
            'allowances_zwg' => $allowancesZwg,
            'bonus_zwg' => $bonusZwg,
            'overtime_zwg' => $overtimeZwg,
            'other_earnings_zwg' => $otherEarningsZwg,
            'gross_zwg' => $grossZwg,

            'gross_paye_zwg' => $grossPayeZwg,
            'medical_aid_zwg' => $medAidZwg,
            'medical_aid_tax_credit_zwg' => $medCreditZwg,
            'paye_zwg' => $payeZwg,
            'aids_levy_zwg' => $aidsLevyZwg,
            'nssa_employee_zwg' => $nssaEmpZwg,
            'nssa_employer_zwg' => $nssaEmprZwg,
            'nec_employee_zwg' => $necEmpZwg,
            'nec_employer_zwg' => $necEmprZwg,
            'trade_union_zwg' => $tradeUnionZwg,
            'loan_repayment_zwg' => $loanZwg,
            'other_deductions_zwg' => $otherDeductZwg,
            'total_deductions_zwg' => $totalDeductionsZwg,
            'net_pay_zwg' => $netPayZwg,

            // Combined / Legacy Compatibility Fields
            'basic_salary' => $basicUsd ?: $basicZwg,
            'gross_pay' => $grossUsd ?: $grossZwg,
            'paye' => $payeUsd ?: $payeZwg,
            'aids_levy' => $aidsLevyUsd ?: $aidsLevyZwg,
            'nssa_employee' => $nssaEmpUsd ?: $nssaEmpZwg,
            'nssa_employer' => $nssaEmprUsd ?: $nssaEmprZwg,
            'nec' => $necEmpUsd ?: $necEmpZwg,
            'trade_union' => $tradeUnionUsd ?: $tradeUnionZwg,
            'loan_repayment' => $loanUsd ?: $loanZwg,
            'other_deductions' => $otherDeductUsd ?: $otherDeductZwg,
            'total_deductions' => $totalDeductionsUsd ?: $totalDeductionsZwg,
            'net_pay' => $netPayUsd ?: $netPayZwg,

            // Statutory Leave (Zimbabwe Labour Act: 2.5 working days per calendar month)
            'leave_days_accrued' => round(($employee->leave_days_accrued ?? 0) + 2.5, 2),
            'leave_days_taken' => round($employee->leave_days_taken ?? 0, 2),
            'leave_balance' => max(0, round((($employee->leave_days_accrued ?? 0) + 2.5) - ($employee->leave_days_taken ?? 0), 2)),
        ];
    }

    /**
     * Progressive PAYE tax bracket calculation for a given income and bracket set.
     *
     * @param float $taxableIncome
     * @param array $brackets
     * @return float
     */
    public function calculateProgressiveTax(float $taxableIncome, array $brackets): float
    {
        if ($taxableIncome <= 0) {
            return 0.0;
        }

        $tax = 0.0;
        foreach ($brackets as $bracket) {
            $rawMin = (float)$bracket['bracket_min'];
            $max = (float)$bracket['bracket_max'];
            $rate = (float)$bracket['rate_percentage'];

            // Handle standard statutory presentation where brackets start at $X.01
            $min = (fmod($rawMin, 1) > 0 && abs(fmod($rawMin, 1) - 0.01) < 0.005) ? floor($rawMin) : $rawMin;

            if ($taxableIncome > $min) {
                $taxableInBracket = min($taxableIncome, $max) - $min;
                if ($taxableInBracket > 0 && $rate > 0) {
                    $tax += $taxableInBracket * ($rate / 100);
                }
            }
        }

        return round($tax, 2);
    }

    /**
     * Forecast Cumulative Method for calculating PAYE across a financial year
     * to safely handle mid-year salary changes, bonus tax spreading, and directive calculations.
     *
     * @param float $ytdTaxableIncome Cumulative taxable earnings prior to current period
     * @param float $currentMonthTaxable Current period taxable earnings
     * @param int $currentMonthNumber Month index (1 to 12)
     * @param float $ytdPayePaid Cumulative PAYE already paid prior to current period
     * @param array $brackets Annual tax brackets
     * @return array ['current_paye' => float, 'projected_annual_tax' => float]
     */
    public function calculateForecastPaye(
        float $ytdTaxableIncome,
        float $currentMonthTaxable,
        int $currentMonthNumber,
        float $ytdPayePaid,
        array $brackets
    ): array {
        $totalCumulativeIncome = $ytdTaxableIncome + $currentMonthTaxable;
        $monthsRemaining = max(1, 12 - $currentMonthNumber + 1);

        // Project full annual income: YTD actual + forecast of current salary for remainder of year
        $annualizedTaxable = $totalCumulativeIncome + ($currentMonthTaxable * ($monthsRemaining - 1));

        // Multiply bracket thresholds by 12 for annual evaluation
        $annualBrackets = array_map(function ($b) {
            return [
                'bracket_min' => (float)$b['bracket_min'] * 12,
                'bracket_max' => (float)$b['bracket_max'] * 12,
                'rate_percentage' => (float)$b['rate_percentage'],
            ];
        }, $brackets);

        $projectedAnnualTax = $this->calculateProgressiveTax($annualizedTaxable, $annualBrackets);

        // Proportionate tax liability to date
        $cumulativeTaxDueToDate = ($projectedAnnualTax / 12) * $currentMonthNumber;
        $currentMonthPaye = max(0, round($cumulativeTaxDueToDate - $ytdPayePaid, 2));

        return [
            'current_paye' => $currentMonthPaye,
            'projected_annual_tax' => round($projectedAnnualTax, 2),
            'cumulative_tax_due' => round($cumulativeTaxDueToDate, 2),
        ];
    }

    /**
     * Load dynamic statutory rates from database with fallbacks.
     */
    public function loadStatutoryRates(?int $schoolId = null, ?string $date = null): array
    {
        $effectiveDate = $date ?? now()->toDateString();
        $ratesQuery = collect();

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('statutory_rates')) {
                $ratesQuery = StatutoryRate::active()
                    ->effective($effectiveDate)
                    ->where(function ($q) use ($schoolId) {
                        $q->whereNull('school_id');
                        if ($schoolId) {
                            $q->orWhere('school_id', $schoolId);
                        }
                    })
                    ->get();
            }
        } catch (\Throwable $e) {
            $ratesQuery = collect();
        }

        // USD PAYE Brackets
        $usdBrackets = $ratesQuery->where('rate_type', 'paye_bracket')
            ->where('currency', 'USD')
            ->sortBy('bracket_min')
            ->values()
            ->toArray();

        if (empty($usdBrackets)) {
            $usdBrackets = [
                ['bracket_min' => 0,       'bracket_max' => 100,      'rate_percentage' => 0],
                ['bracket_min' => 100.01,  'bracket_max' => 300,      'rate_percentage' => 20],
                ['bracket_min' => 300.01,  'bracket_max' => 1000,     'rate_percentage' => 25],
                ['bracket_min' => 1000.01, 'bracket_max' => 2000,     'rate_percentage' => 30],
                ['bracket_min' => 2000.01, 'bracket_max' => 3000,     'rate_percentage' => 35],
                ['bracket_min' => 3000.01, 'bracket_max' => 99999999, 'rate_percentage' => 40],
            ];
        }

        // ZWG PAYE Brackets
        $zwgBrackets = $ratesQuery->where('rate_type', 'paye_bracket')
            ->where('currency', 'ZWG')
            ->sortBy('bracket_min')
            ->values()
            ->toArray();

        if (empty($zwgBrackets)) {
            $zwgBrackets = [
                ['bracket_min' => 0,        'bracket_max' => 2800,     'rate_percentage' => 0],
                ['bracket_min' => 2800.01,  'bracket_max' => 8400,     'rate_percentage' => 20],
                ['bracket_min' => 8400.01,  'bracket_max' => 28000,    'rate_percentage' => 25],
                ['bracket_min' => 28000.01, 'bracket_max' => 56000,    'rate_percentage' => 30],
                ['bracket_min' => 56000.01, 'bracket_max' => 84000,    'rate_percentage' => 35],
                ['bracket_min' => 84000.01, 'bracket_max' => 99999999, 'rate_percentage' => 40],
            ];
        }

        // AIDS Levy Rate
        $aidsLevy = $ratesQuery->where('rate_type', 'aids_levy')->first();
        $aidsLevyRate = $aidsLevy ? (float)$aidsLevy->rate_percentage : 3.0;

        // NSSA Rates & Caps
        $nssaEmp = $ratesQuery->where('rate_type', 'nssa_employee')->first();
        $nssaEmpRate = $nssaEmp ? (float)$nssaEmp->rate_percentage : 4.5;

        $nssaEmpr = $ratesQuery->where('rate_type', 'nssa_employer')->first();
        $nssaEmprRate = $nssaEmpr ? (float)$nssaEmpr->rate_percentage : 4.5;

        $nssaCapUsdRecord = $ratesQuery->where('rate_type', 'nssa_max_earnings')->where('currency', 'USD')->first();
        $nssaCapUsd = $nssaCapUsdRecord ? (float)$nssaCapUsdRecord->flat_amount : 700.00;

        $nssaCapZwgRecord = $ratesQuery->where('rate_type', 'nssa_max_earnings')->where('currency', 'ZWG')->first();
        $nssaCapZwg = $nssaCapZwgRecord ? (float)$nssaCapZwgRecord->flat_amount : 19600.00;

        // Medical Aid Credit %
        $medCredit = $ratesQuery->where('rate_type', 'medical_aid_credit')->first();
        $medCreditRate = $medCredit ? (float)$medCredit->rate_percentage : 50.0;

        // NEC Rules
        $necRules = $ratesQuery->where('rate_type', 'nec_rule')->keyBy('sector_code');

        return [
            'usd_brackets' => $usdBrackets,
            'zwg_brackets' => $zwgBrackets,
            'aids_levy_rate' => $aidsLevyRate,
            'nssa_employee_rate' => $nssaEmpRate,
            'nssa_employer_rate' => $nssaEmprRate,
            'nssa_cap_usd' => $nssaCapUsd,
            'nssa_cap_zwg' => $nssaCapZwg,
            'medical_aid_credit_percentage' => $medCreditRate,
            'nec_rules' => $necRules,
        ];
    }

    private function getNecConfig(string $sectorCode, array $rates): array
    {
        if (isset($rates['nec_rules'][$sectorCode])) {
            $rule = $rates['nec_rules'][$sectorCode];
            return [
                'rate_percentage' => (float)$rule->rate_percentage,
                'flat_amount' => (float)$rule->flat_amount,
            ];
        }

        // Default to Educational Services sector 1.5%
        return [
            'rate_percentage' => 1.5,
            'flat_amount' => 0,
        ];
    }
}
