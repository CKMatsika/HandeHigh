<?php

namespace Database\Seeders;

use App\Models\StatutoryRate;
use Illuminate\Database\Seeder;

class StatutoryRatesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. ZIMRA PAYE Progressive Brackets (USD Monthly)
        $usdPayeBrackets = [
            ['bracket_min' => 0,       'bracket_max' => 100,      'rate_percentage' => 0,  'description' => 'USD Tax-Free Threshold ($0 - $100)'],
            ['bracket_min' => 100.01,  'bracket_max' => 300,      'rate_percentage' => 20, 'description' => 'USD 20% Bracket ($100.01 - $300)'],
            ['bracket_min' => 300.01,  'bracket_max' => 1000,     'rate_percentage' => 25, 'description' => 'USD 25% Bracket ($300.01 - $1,000)'],
            ['bracket_min' => 1000.01, 'bracket_max' => 2000,     'rate_percentage' => 30, 'description' => 'USD 30% Bracket ($1,000.01 - $2,000)'],
            ['bracket_min' => 2000.01, 'bracket_max' => 3000,     'rate_percentage' => 35, 'description' => 'USD 35% Bracket ($2,000.01 - $3,000)'],
            ['bracket_min' => 3000.01, 'bracket_max' => 99999999, 'rate_percentage' => 40, 'description' => 'USD 40% Top Bracket (Over $3,000)'],
        ];

        foreach ($usdPayeBrackets as $b) {
            StatutoryRate::updateOrCreate(
                ['school_id' => null, 'rate_type' => 'paye_bracket', 'currency' => 'USD', 'bracket_min' => $b['bracket_min']],
                [
                    'bracket_max' => $b['bracket_max'],
                    'rate_percentage' => $b['rate_percentage'],
                    'is_active' => true,
                    'description' => $b['description'],
                ]
            );
        }

        // 2. ZIMRA PAYE Progressive Brackets (ZWG Monthly)
        $zwgPayeBrackets = [
            ['bracket_min' => 0,        'bracket_max' => 2800,     'rate_percentage' => 0,  'description' => 'ZWG Tax-Free Threshold (ZWG 0 - 2,800)'],
            ['bracket_min' => 2800.01,  'bracket_max' => 8400,     'rate_percentage' => 20, 'description' => 'ZWG 20% Bracket (ZWG 2,800.01 - 8,400)'],
            ['bracket_min' => 8400.01,  'bracket_max' => 28000,    'rate_percentage' => 25, 'description' => 'ZWG 25% Bracket (ZWG 8,400.01 - 28,000)'],
            ['bracket_min' => 28000.01, 'bracket_max' => 56000,    'rate_percentage' => 30, 'description' => 'ZWG 30% Bracket (ZWG 28,000.01 - 56,000)'],
            ['bracket_min' => 56000.01, 'bracket_max' => 84000,    'rate_percentage' => 35, 'description' => 'ZWG 35% Bracket (ZWG 56,000.01 - 84,000)'],
            ['bracket_min' => 84000.01, 'bracket_max' => 99999999, 'rate_percentage' => 40, 'description' => 'ZWG 40% Top Bracket (Over ZWG 84,000)'],
        ];

        foreach ($zwgPayeBrackets as $b) {
            StatutoryRate::updateOrCreate(
                ['school_id' => null, 'rate_type' => 'paye_bracket', 'currency' => 'ZWG', 'bracket_min' => $b['bracket_min']],
                [
                    'bracket_max' => $b['bracket_max'],
                    'rate_percentage' => $b['rate_percentage'],
                    'is_active' => true,
                    'description' => $b['description'],
                ]
            );
        }

        // 3. AIDS Levy (3% of PAYE)
        StatutoryRate::updateOrCreate(
            ['school_id' => null, 'rate_type' => 'aids_levy', 'currency' => 'ALL'],
            [
                'rate_percentage' => 3.0,
                'is_active' => true,
                'description' => 'Zimbabwe National AIDS Council Levy (3% of PAYE)',
            ]
        );

        // 4. NSSA Contribution Rates & Caps
        // Employee 4.5%
        StatutoryRate::updateOrCreate(
            ['school_id' => null, 'rate_type' => 'nssa_employee', 'currency' => 'ALL'],
            [
                'rate_percentage' => 4.5,
                'is_active' => true,
                'description' => 'NSSA Pension Employee Contribution Rate (4.5%)',
            ]
        );

        // Employer 4.5%
        StatutoryRate::updateOrCreate(
            ['school_id' => null, 'rate_type' => 'nssa_employer', 'currency' => 'ALL'],
            [
                'rate_percentage' => 4.5,
                'is_active' => true,
                'description' => 'NSSA Pension Employer Contribution Rate (4.5%)',
            ]
        );

        // NSSA Max Insurable Earnings Cap USD
        StatutoryRate::updateOrCreate(
            ['school_id' => null, 'rate_type' => 'nssa_max_earnings', 'currency' => 'USD'],
            [
                'flat_amount' => 700.00,
                'is_active' => true,
                'description' => 'NSSA Maximum Insurable Earnings Cap (USD $700.00/mo)',
            ]
        );

        // NSSA Max Insurable Earnings Cap ZWG
        StatutoryRate::updateOrCreate(
            ['school_id' => null, 'rate_type' => 'nssa_max_earnings', 'currency' => 'ZWG'],
            [
                'flat_amount' => 19600.00,
                'is_active' => true,
                'description' => 'NSSA Maximum Insurable Earnings Cap (ZWG 19,600.00/mo)',
            ]
        );

        // 5. Medical Aid Tax Credit (50% of employee contribution)
        StatutoryRate::updateOrCreate(
            ['school_id' => null, 'rate_type' => 'medical_aid_credit', 'currency' => 'ALL'],
            [
                'rate_percentage' => 50.0,
                'is_active' => true,
                'description' => 'ZIMRA Medical Aid Tax Credit (50% of employee medical aid)',
            ]
        );

        // 6. NEC Sector Rules (Educational Services, Commercial, Agriculture)
        $necSectors = [
            ['code' => 'NEC-EDU', 'rate' => 1.5, 'desc' => 'NEC for Educational Services (1.5% employee / 1.5% employer)'],
            ['code' => 'NEC-COMM', 'rate' => 1.2, 'desc' => 'NEC for Commercial Sectors (1.2% employee / 1.2% employer)'],
            ['code' => 'NEC-AGRIC', 'rate' => 1.0, 'desc' => 'NEC for Agricultural Industry (1.0% employee / 1.0% employer)'],
        ];

        foreach ($necSectors as $nec) {
            StatutoryRate::updateOrCreate(
                ['school_id' => null, 'rate_type' => 'nec_rule', 'sector_code' => $nec['code']],
                [
                    'currency' => 'ALL',
                    'rate_percentage' => $nec['rate'],
                    'is_active' => true,
                    'description' => $nec['desc'],
                ]
            );
        }
    }
}
