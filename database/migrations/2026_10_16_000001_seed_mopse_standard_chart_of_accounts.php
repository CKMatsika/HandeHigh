<?php

use App\Models\Account;
use App\Models\School;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $accountsToEnsure = [
            // Current Assets
            [
                'code' => '1103',
                'name' => 'Cash on Hand / Kiosk Till',
                'type' => 'asset',
                'category' => 'cash',
                'parent_code' => '1100',
                'is_postable' => true,
                'description' => 'Cash count / daily retail till float',
                'sort_order' => 13,
            ],
            [
                'code' => '1210',
                'name' => 'Accounts Receivable - BEAM 2023',
                'type' => 'asset',
                'category' => 'receivable',
                'parent_code' => '1200',
                'is_postable' => true,
                'description' => 'Government BEAM reconciliation arrears 2023',
                'sort_order' => 23,
            ],
            [
                'code' => '1211',
                'name' => 'Accounts Receivable - BEAM 2024',
                'type' => 'asset',
                'category' => 'receivable',
                'parent_code' => '1200',
                'is_postable' => true,
                'description' => 'Government BEAM reconciliation arrears 2024',
                'sort_order' => 24,
            ],
            [
                'code' => '1212',
                'name' => 'Accounts Receivable - BEAM 2025',
                'type' => 'asset',
                'category' => 'receivable',
                'parent_code' => '1200',
                'is_postable' => true,
                'description' => 'Government BEAM reconciliation 2025',
                'sort_order' => 25,
            ],
            [
                'code' => '1290',
                'name' => 'Allowance for Doubtful Debts',
                'type' => 'asset',
                'category' => 'receivable',
                'parent_code' => '1200',
                'is_postable' => true,
                'description' => 'Contra-asset ageing review allowance for doubtful debts',
                'sort_order' => 29,
            ],
            // Bank Accounts (MoPSE standard AFC, CABS, ZB)
            [
                'code' => '1321',
                'name' => 'Cash at AFC Bank',
                'type' => 'asset',
                'category' => 'bank',
                'parent_code' => '1300',
                'is_postable' => true,
                'description' => 'Bank: AFC Bank | Acc No: AFC-001 | Branch: Main',
                'bank_keyword' => 'afc',
                'sort_order' => 35,
            ],
            [
                'code' => '1322',
                'name' => 'Cash at CABS Bank',
                'type' => 'asset',
                'category' => 'bank',
                'parent_code' => '1300',
                'is_postable' => true,
                'description' => 'Bank: CABS Bank | Acc No: CABS-001 | Branch: Main',
                'bank_keyword' => 'cabs',
                'sort_order' => 36,
            ],
            [
                'code' => '1323',
                'name' => 'Cash at ZB Bank',
                'type' => 'asset',
                'category' => 'bank',
                'parent_code' => '1300',
                'is_postable' => true,
                'description' => 'Bank: ZB Bank | Acc No: ZB-001 | Branch: Main',
                'bank_keyword' => 'zb',
                'sort_order' => 37,
            ],
            // Inventories
            [
                'code' => '1503',
                'name' => 'Kiosk Merchandise Inventory',
                'type' => 'asset',
                'category' => 'current_asset',
                'parent_code' => '1500',
                'is_postable' => true,
                'description' => 'Kiosk tuckshop merchandise stocktake inventory',
                'sort_order' => 53,
            ],
            [
                'code' => '1504',
                'name' => 'Boarding Food Inventory',
                'type' => 'asset',
                'category' => 'current_asset',
                'parent_code' => '1500',
                'is_postable' => true,
                'description' => 'Boarding food provisions and ration inventory',
                'sort_order' => 54,
            ],
            // Fixed Assets & Infrastructure Projects
            [
                'code' => '2210',
                'name' => 'Hostels and Construction in Progress',
                'type' => 'asset',
                'category' => 'fixed_asset',
                'parent_code' => '2000',
                'is_postable' => true,
                'description' => 'Capital expenditure project register - dormitories & hostels CIP',
                'sort_order' => 23,
            ],
            [
                'code' => '2220',
                'name' => 'Dining Hall and Improvements',
                'type' => 'asset',
                'category' => 'fixed_asset',
                'parent_code' => '2000',
                'is_postable' => true,
                'description' => 'Capital expenditure project register - dining hall improvements',
                'sort_order' => 24,
            ],
            [
                'code' => '2310',
                'name' => 'Furniture and Equipment',
                'type' => 'asset',
                'category' => 'fixed_asset',
                'parent_code' => '2000',
                'is_postable' => true,
                'description' => 'Desks, chairs, lab benches, school furniture',
                'sort_order' => 33,
            ],
            [
                'code' => '2320',
                'name' => 'Computer and ICT Equipment',
                'type' => 'asset',
                'category' => 'fixed_asset',
                'parent_code' => '2000',
                'is_postable' => true,
                'description' => 'ICT computer lab, servers, networking hardware',
                'sort_order' => 34,
            ],
            // Liabilities & Statutory Obligations
            [
                'code' => '3210',
                'name' => 'Accrued Payroll and Statutory Deductions',
                'type' => 'liability',
                'category' => 'payable',
                'parent_code' => '3000',
                'is_postable' => true,
                'description' => 'Payroll clearing: PAYE (ZIMRA), NSSA, ZIMDEF statutory deductions',
                'sort_order' => 22,
            ],
            [
                'code' => '3410',
                'name' => 'Exam and Restricted Levy Payable',
                'type' => 'liability',
                'category' => 'current_liability',
                'parent_code' => '3000',
                'is_postable' => true,
                'description' => 'ZIMSEC exams and restricted remittances held in trust',
                'sort_order' => 42,
            ],
            [
                'code' => '3510',
                'name' => 'Loans - Hostel AFC',
                'type' => 'liability',
                'category' => 'long_term_liability',
                'parent_code' => '3500',
                'is_postable' => true,
                'description' => 'AFC Land Bank long term loan - hostel development',
                'sort_order' => 52,
            ],
            [
                'code' => '3511',
                'name' => 'Loans - School Bus AFC',
                'type' => 'liability',
                'category' => 'long_term_liability',
                'parent_code' => '3500',
                'is_postable' => true,
                'description' => 'AFC Land Bank asset finance loan - school bus',
                'sort_order' => 53,
            ],
            [
                'code' => '3520',
                'name' => 'Other Loans and Interest Payable',
                'type' => 'liability',
                'category' => 'long_term_liability',
                'parent_code' => '3500',
                'is_postable' => true,
                'description' => 'Institutional loans and accrued interest statements',
                'sort_order' => 54,
            ],
            // Equity
            [
                'code' => '4400',
                'name' => 'Restricted Capital Funds and Grants',
                'type' => 'equity',
                'category' => 'equity',
                'parent_code' => '4000',
                'is_postable' => true,
                'description' => 'Donor restrictions, SDA capital development fund, CDF grants',
                'sort_order' => 40,
            ],
            // Income & Statutory MoPSE Levies
            [
                'code' => '5110',
                'name' => 'School Levies',
                'type' => 'revenue',
                'category' => 'tuition_revenue',
                'parent_code' => '5000',
                'is_postable' => true,
                'description' => 'School Development Committee (SDC) general levy',
                'sort_order' => 14,
            ],
            [
                'code' => '5120',
                'name' => 'Building Fund Levy',
                'type' => 'revenue',
                'category' => 'tuition_revenue',
                'parent_code' => '5000',
                'is_postable' => true,
                'description' => 'Restricted building & infrastructure development levy',
                'sort_order' => 15,
            ],
            [
                'code' => '5130',
                'name' => 'Sports Levy',
                'type' => 'revenue',
                'category' => 'tuition_revenue',
                'parent_code' => '5000',
                'is_postable' => true,
                'description' => 'Sports & athletics co-curricular levy',
                'sort_order' => 16,
            ],
            [
                'code' => '5140',
                'name' => 'ICT Levy',
                'type' => 'revenue',
                'category' => 'tuition_revenue',
                'parent_code' => '5000',
                'is_postable' => true,
                'description' => 'ICT, computer lab and connectivity development levy',
                'sort_order' => 17,
            ],
            [
                'code' => '5150',
                'name' => 'Scholarship / BEAM Funding',
                'type' => 'revenue',
                'category' => 'tuition_revenue',
                'parent_code' => '5000',
                'is_postable' => true,
                'description' => 'Basic Education Assistance Module (BEAM) funding accrual',
                'sort_order' => 18,
            ],
            [
                'code' => '5160',
                'name' => 'Practical Levy',
                'type' => 'revenue',
                'category' => 'tuition_revenue',
                'parent_code' => '5000',
                'is_postable' => true,
                'description' => 'Science, technical, food & nutrition practical subjects levy',
                'sort_order' => 19,
            ],
            [
                'code' => '5170',
                'name' => 'General Purpose Fund',
                'type' => 'revenue',
                'category' => 'tuition_revenue',
                'parent_code' => '5000',
                'is_postable' => true,
                'description' => 'Statutory General Purpose Fund (GPF) allocation',
                'sort_order' => 20,
            ],
            [
                'code' => '5810',
                'name' => 'Rental Income',
                'type' => 'revenue',
                'category' => 'other_revenue',
                'parent_code' => '5000',
                'is_postable' => true,
                'description' => 'Staff cottages, grounds and facility hiring',
                'sort_order' => 85,
            ],
            [
                'code' => '5820',
                'name' => 'Bus and Truck Hire Income',
                'type' => 'revenue',
                'category' => 'other_revenue',
                'parent_code' => '5000',
                'is_postable' => true,
                'description' => 'School transport hire and logistics income',
                'sort_order' => 86,
            ],
            // Cost of Sales & Direct Costs
            [
                'code' => '6050',
                'name' => 'Cost of Sales',
                'type' => 'expense',
                'category' => 'supply_expense',
                'parent_code' => '6000',
                'is_postable' => false,
                'description' => 'Direct inventory and trading movements header',
                'sort_order' => 5,
            ],
            [
                'code' => '6051',
                'name' => 'Kiosk Purchases / Cost of Sales',
                'type' => 'expense',
                'category' => 'supply_expense',
                'parent_code' => '6050',
                'is_postable' => true,
                'description' => 'Direct tuckshop retail cost of sales',
                'sort_order' => 6,
            ],
            [
                'code' => '6052',
                'name' => 'Uniform Cost of Sales',
                'type' => 'expense',
                'category' => 'supply_expense',
                'parent_code' => '6050',
                'is_postable' => true,
                'description' => 'Direct uniform inventory cost of sales',
                'sort_order' => 7,
            ],
            [
                'code' => '6053',
                'name' => 'Boarding Food and Catering',
                'type' => 'expense',
                'category' => 'supply_expense',
                'parent_code' => '6050',
                'is_postable' => true,
                'description' => 'Boarding kitchen rations, butchery and grocery consumables',
                'sort_order' => 8,
            ],
            // Operating Expenses
            [
                'code' => '6203',
                'name' => 'NSSA Employer Costs',
                'type' => 'expense',
                'category' => 'salary_expense',
                'parent_code' => '6200',
                'is_postable' => true,
                'description' => 'Statutory NSSA employer contribution costs',
                'sort_order' => 23,
            ],
            [
                'code' => '6404',
                'name' => 'Vehicle Running Costs',
                'type' => 'expense',
                'category' => 'maintenance_expense',
                'parent_code' => '6400',
                'is_postable' => true,
                'description' => 'Bus fuel, lubricants, toll fees, vehicle licensing and road permits',
                'sort_order' => 44,
            ],
            [
                'code' => '6510',
                'name' => 'Educational Programmes',
                'type' => 'expense',
                'category' => 'supply_expense',
                'parent_code' => '6500',
                'is_postable' => true,
                'description' => 'Curriculum, sports trips, prize giving, clubs and co-curricular',
                'sort_order' => 54,
            ],
            [
                'code' => '6520',
                'name' => 'Examination and Practical Expenses',
                'type' => 'expense',
                'category' => 'supply_expense',
                'parent_code' => '6500',
                'is_postable' => true,
                'description' => 'ZIMSEC exams, science chemicals, consumables and practical supplies',
                'sort_order' => 55,
            ],
            [
                'code' => '6550',
                'name' => 'Office and Administration',
                'type' => 'expense',
                'category' => 'supply_expense',
                'parent_code' => '6500',
                'is_postable' => true,
                'description' => 'General administration, postage, printing and office supplies',
                'sort_order' => 56,
            ],
        ];

        DB::transaction(function () use ($accountsToEnsure) {
            $schools = School::all();

            foreach ($schools as $school) {
                // Ensure parent headers like 6050 are created first
                foreach ($accountsToEnsure as $accData) {
                    $bankKeyword = $accData['bank_keyword'] ?? null;

                    // Duplicate check: code OR exact name OR bank keyword
                    $existing = Account::where('school_id', $school->id)
                        ->where(function ($query) use ($accData, $bankKeyword) {
                            $query->where('code', $accData['code'])
                                  ->orWhereRaw('LOWER(name) = ?', [strtolower($accData['name'])]);

                            if ($bankKeyword) {
                                $query->orWhereRaw('LOWER(name) LIKE ?', ['%' . $bankKeyword . '%'])
                                      ->orWhereRaw('LOWER(description) LIKE ?', ['%' . $bankKeyword . '%']);
                            }
                        })
                        ->first();

                    if ($existing) {
                        // Already exists on live production or local database; skip to prevent duplication
                        continue;
                    }

                    // Resolve parent_id from parent_code
                    $parentId = null;
                    if (!empty($accData['parent_code'])) {
                        $parent = Account::where('school_id', $school->id)
                            ->where('code', $accData['parent_code'])
                            ->first();
                        $parentId = $parent?->id;
                    }

                    // Check if code was taken by an unrelated account
                    $code = $accData['code'];
                    if (Account::where('school_id', $school->id)->where('code', $code)->exists()) {
                        $prefix = substr($code, 0, 2);
                        $num = 1;
                        while (Account::where('school_id', $school->id)->where('code', sprintf('%s%02d', $prefix, $num))->exists()) {
                            $num++;
                        }
                        $code = sprintf('%s%02d', $prefix, $num);
                    }

                    Account::create([
                        'school_id' => $school->id,
                        'code' => $code,
                        'name' => $accData['name'],
                        'type' => $accData['type'],
                        'category' => $accData['category'],
                        'parent_id' => $parentId,
                        'opening_balance' => 0.00,
                        'currency' => 'USD',
                        'is_active' => true,
                        'is_postable' => $accData['is_postable'] ?? true,
                        'description' => $accData['description'] ?? null,
                        'sort_order' => $accData['sort_order'] ?? 50,
                    ]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe down: do not delete accounts that may have journal entries
    }
};
