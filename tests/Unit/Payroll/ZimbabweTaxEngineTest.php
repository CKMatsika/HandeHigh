<?php

namespace Tests\Unit\Payroll;

use App\Models\Employee;
use App\Services\Payroll\ZimbabwePayrollService;
use Database\Seeders\StatutoryRatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZimbabweTaxEngineTest extends TestCase
{
    use RefreshDatabase;

    protected ZimbabwePayrollService $service;

    protected array $usdBrackets = [
        ['bracket_min' => 0,       'bracket_max' => 100,      'rate_percentage' => 0],
        ['bracket_min' => 100.01,  'bracket_max' => 300,      'rate_percentage' => 20],
        ['bracket_min' => 300.01,  'bracket_max' => 1000,     'rate_percentage' => 25],
        ['bracket_min' => 1000.01, 'bracket_max' => 2000,     'rate_percentage' => 30],
        ['bracket_min' => 2000.01, 'bracket_max' => 3000,     'rate_percentage' => 35],
        ['bracket_min' => 3000.01, 'bracket_max' => 99999999, 'rate_percentage' => 40],
    ];

    protected array $zwgBrackets = [
        ['bracket_min' => 0,        'bracket_max' => 2800,     'rate_percentage' => 0],
        ['bracket_min' => 2800.01,  'bracket_max' => 8400,     'rate_percentage' => 20],
        ['bracket_min' => 8400.01,  'bracket_max' => 28000,    'rate_percentage' => 25],
        ['bracket_min' => 28000.01, 'bracket_max' => 56000,    'rate_percentage' => 30],
        ['bracket_min' => 56000.01, 'bracket_max' => 84000,    'rate_percentage' => 35],
        ['bracket_min' => 84000.01, 'bracket_max' => 99999999, 'rate_percentage' => 40],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        (new StatutoryRatesSeeder)->run();
        $this->service = new ZimbabwePayrollService();
    }

    /**
     * Test USD Progressive Tax Bracket Calculations across all boundary transitions.
     */
    public function test_usd_progressive_tax_brackets_transitions(): void
    {
        // 1. Below / at tax-free threshold ($100) -> 0%
        $tax0 = $this->service->calculateProgressiveTax(100.00, $this->usdBrackets);
        $this->assertEquals(0.00, $tax0);

        // 2. In 20% Bracket ($200): $100 @ 0% + $100 @ 20% = $20.00
        $tax200 = $this->service->calculateProgressiveTax(200.00, $this->usdBrackets);
        $this->assertEquals(20.00, $tax200);

        // 3. Exactly at top of 20% Bracket ($300): $100 @ 0% + $200 @ 20% = $40.00
        $tax300 = $this->service->calculateProgressiveTax(300.00, $this->usdBrackets);
        $this->assertEquals(40.00, $tax300);

        // 4. In 25% Bracket ($500): $40 + ($200 * 0.25 = $50) = $90.00
        $tax500 = $this->service->calculateProgressiveTax(500.00, $this->usdBrackets);
        $this->assertEquals(90.00, $tax500);

        // 5. At top of 25% Bracket ($1,000): $40 + ($700 * 0.25 = $175) = $215.00
        $tax1000 = $this->service->calculateProgressiveTax(1000.00, $this->usdBrackets);
        $this->assertEquals(215.00, $tax1000);

        // 6. In 30% Bracket ($1,500): $215 + ($500 * 0.30 = $150) = $365.00
        $tax1500 = $this->service->calculateProgressiveTax(1500.00, $this->usdBrackets);
        $this->assertEquals(365.00, $tax1500);

        // 7. In 35% Bracket ($2,500): $215 + ($1000 * 0.30 = $300) + ($500 * 0.35 = $175) = $690.00
        $tax2500 = $this->service->calculateProgressiveTax(2500.00, $this->usdBrackets);
        $this->assertEquals(690.00, $tax2500);

        // 8. In 40% Bracket ($4,000): $215 + $300 + $350 + ($1000 * 0.40 = $400) = $1265.00
        $tax4000 = $this->service->calculateProgressiveTax(4000.00, $this->usdBrackets);
        $this->assertEquals(1265.00, $tax4000);
    }

    /**
     * Test ZWG Progressive Tax Bracket Calculations across boundary transitions.
     */
    public function test_zwg_progressive_tax_brackets_transitions(): void
    {
        // 1. Tax free under ZWG 2,800
        $taxZwg0 = $this->service->calculateProgressiveTax(2800.00, $this->zwgBrackets);
        $this->assertEquals(0.00, $taxZwg0);

        // 2. In 20% Bracket (ZWG 5,000): (5000 - 2800) * 0.20 = ZWG 440.00
        $taxZwg5000 = $this->service->calculateProgressiveTax(5000.00, $this->zwgBrackets);
        $this->assertEquals(440.00, $taxZwg5000);

        // 3. In 25% Bracket (ZWG 10,000): (8400 - 2800)*0.20 + (10000 - 8400)*0.25 = 1120 + 400 = 1520.00
        $taxZwg10k = $this->service->calculateProgressiveTax(10000.00, $this->zwgBrackets);
        $this->assertEquals(1520.00, $taxZwg10k);
    }

    /**
     * Test 3% AIDS Levy Formula (strictly 3% of PAYE, NOT 3% of gross earnings).
     */
    public function test_aids_levy_calculation_on_paye(): void
    {
        $employee = new Employee();
        $employee->nec_sector_code = 'NEC-EDU';

        // Employee paid $1,000 USD gross -> PAYE = $215.00
        // AIDS Levy = 3% of $215.00 = $6.45
        $calc = $this->service->calculateEmployeePayroll($employee, [
            'basic_salary_usd' => 1000.00,
        ]);

        $this->assertEquals(215.00, $calc['paye_usd']);
        $this->assertEquals(6.45, $calc['aids_levy_usd']);
        // Verify AIDS Levy is NOT 3% of gross income (which would be $30.00)
        $this->assertNotEquals(30.00, $calc['aids_levy_usd']);
    }

    /**
     * Test 50% ZIMRA Medical Aid Tax Credit and zero-tax floor boundary.
     */
    public function test_medical_aid_tax_credit_reduces_paye(): void
    {
        $employee = new Employee();
        $employee->nec_sector_code = 'NEC-EDU';

        // Gross = $500 USD (Gross PAYE = $90.00)
        // Medical Aid = $100 USD -> 50% Tax Credit = $50.00
        // Net PAYE = $90.00 - $50.00 = $40.00
        // AIDS Levy = 3% of $40.00 = $1.20
        $calc = $this->service->calculateEmployeePayroll($employee, [
            'basic_salary_usd' => 500.00,
            'medical_aid_usd' => 100.00,
        ]);

        $this->assertEquals(90.00, $calc['gross_paye_usd']);
        $this->assertEquals(50.00, $calc['medical_aid_tax_credit_usd']);
        $this->assertEquals(40.00, $calc['paye_usd']);
        $this->assertEquals(1.20, $calc['aids_levy_usd']);

        // Test boundary where Medical Aid Credit exceeds Gross PAYE (Zero floor)
        // Gross = $200 USD (Gross PAYE = $20.00)
        // Medical Aid = $100 USD (50% Credit = $50.00) -> Net PAYE must be $0.00, not negative
        $calcZero = $this->service->calculateEmployeePayroll($employee, [
            'basic_salary_usd' => 200.00,
            'medical_aid_usd' => 100.00,
        ]);

        $this->assertEquals(0.00, $calcZero['paye_usd']);
        $this->assertEquals(0.00, $calcZero['aids_levy_usd']);
    }

    /**
     * Test NSSA 4.5% Employee and Matching 4.5% Employer Contribution with Insurable Cap.
     */
    public function test_nssa_contribution_and_insurable_earnings_cap(): void
    {
        $employee = new Employee();

        // 1. Below Cap ($500 USD):
        // Employee 4.5% = $22.50, Employer 4.5% = $22.50
        $calc500 = $this->service->calculateEmployeePayroll($employee, [
            'basic_salary_usd' => 500.00,
        ]);
        $this->assertEquals(22.50, $calc500['nssa_employee_usd']);
        $this->assertEquals(22.50, $calc500['nssa_employer_usd']);

        // 2. Above Cap ($2,000 USD):
        // Capped at $700.00 -> Employee 4.5% of $700 = $31.50, Employer = $31.50
        $calc2000 = $this->service->calculateEmployeePayroll($employee, [
            'basic_salary_usd' => 2000.00,
        ]);
        $this->assertEquals(31.50, $calc2000['nssa_employee_usd']);
        $this->assertEquals(31.50, $calc2000['nssa_employer_usd']);
    }

    /**
     * Test NEC Sector Collective Bargaining Agreement (CBA) calculations.
     */
    public function test_nec_sector_cba_deductions(): void
    {
        $employee = new Employee();
        $employee->nec_sector_code = 'NEC-EDU'; // 1.5%

        $calc = $this->service->calculateEmployeePayroll($employee, [
            'basic_salary_usd' => 1000.00,
        ]);

        // Employee 1.5% of $1,000 = $15.00, Employer match = $15.00
        $this->assertEquals(15.00, $calc['nec_employee_usd']);
        $this->assertEquals(15.00, $calc['nec_employer_usd']);
    }

    /**
     * Test Trade Union Membership Deductions.
     */
    public function test_trade_union_membership_deductions(): void
    {
        $employee = new Employee();
        $employee->trade_union_member = true;
        $employee->trade_union_rate = 1.0; // 1%

        $calc = $this->service->calculateEmployeePayroll($employee, [
            'basic_salary_usd' => 1000.00,
        ]);

        $this->assertEquals(10.00, $calc['trade_union_usd']);
    }

    /**
     * Test Forecast Cumulative Method for mid-year adjustments and bonus tax spreading.
     */
    public function test_forecast_cumulative_tax_method(): void
    {
        // Employee earning $1,000/mo. In Month 6, employee receives a $600 bonus ($1,600 taxable in Month 6).
        // Cumulative earnings Months 1-5 = $5,000. YTD PAYE paid (5 * $215 = $1,075).
        $forecast = $this->service->calculateForecastPaye(
            5000.00,    // YTD taxable
            1600.00,    // Month 6 taxable (salary + bonus)
            6,          // Month 6
            1075.00,    // YTD PAYE paid
            $this->usdBrackets
        );

        $this->assertArrayHasKey('current_paye', $forecast);
        $this->assertArrayHasKey('projected_annual_tax', $forecast);
        $this->assertGreaterThan(215.00, $forecast['current_paye']);
    }
}
