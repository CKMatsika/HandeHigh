<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\School;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = School::all();
        
        foreach ($schools as $school) {
            $this->seedAccountsForSchool($school);
        }
    }
    
    /**
     * Seed accounts for a specific school
     */
    public function seedAccountsForSchool(School $school): void
    {
        // Check if accounts already exist
        if (Account::where('school_id', $school->id)->exists()) {
            $this->command?->warn("Accounts already exist for school: {$school->name}. Skipping...");
            return;
        }
        
        $accounts = [
            // ASSETS
            ['code' => '1000', 'name' => 'ASSETS', 'type' => 'asset', 'category' => 'current_asset', 'parent_id' => null, 'sort_order' => 1],
            
            // Current Assets
            ['code' => '1100', 'name' => 'Cash and Cash Equivalents', 'type' => 'asset', 'category' => 'cash', 'parent_code' => '1000', 'sort_order' => 10],
            ['code' => '1101', 'name' => 'Cash on Hand', 'type' => 'asset', 'category' => 'cash', 'parent_code' => '1100', 'sort_order' => 11],
            ['code' => '1102', 'name' => 'Petty Cash', 'type' => 'asset', 'category' => 'cash', 'parent_code' => '1100', 'sort_order' => 12],
            
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'category' => 'receivable', 'parent_code' => '1000', 'sort_order' => 20],
            ['code' => '1201', 'name' => 'Student Fees Receivable', 'type' => 'asset', 'category' => 'receivable', 'parent_code' => '1200', 'sort_order' => 21],
            ['code' => '1202', 'name' => 'Other Receivables', 'type' => 'asset', 'category' => 'receivable', 'parent_code' => '1200', 'sort_order' => 22],
            
            ['code' => '1300', 'name' => 'Bank Accounts', 'type' => 'asset', 'category' => 'bank', 'parent_code' => '1000', 'sort_order' => 30],
            ['code' => '1301', 'name' => 'Main Operating Account', 'type' => 'asset', 'category' => 'bank', 'parent_code' => '1300', 'sort_order' => 31],
            ['code' => '1302', 'name' => 'Savings Account', 'type' => 'asset', 'category' => 'bank', 'parent_code' => '1300', 'sort_order' => 32],
            ['code' => '1303', 'name' => 'Mobile Money Account', 'type' => 'asset', 'category' => 'bank', 'parent_code' => '1300', 'sort_order' => 33],
            
            ['code' => '1400', 'name' => 'Prepaid Expenses', 'type' => 'asset', 'category' => 'current_asset', 'parent_code' => '1000', 'sort_order' => 40],
            ['code' => '1500', 'name' => 'Inventory', 'type' => 'asset', 'category' => 'current_asset', 'parent_code' => '1000', 'sort_order' => 50],
            ['code' => '1501', 'name' => 'Books and Supplies', 'type' => 'asset', 'category' => 'current_asset', 'parent_code' => '1500', 'sort_order' => 51],
            ['code' => '1502', 'name' => 'Uniforms', 'type' => 'asset', 'category' => 'current_asset', 'parent_code' => '1500', 'sort_order' => 52],
            
            // Fixed Assets
            ['code' => '2000', 'name' => 'FIXED ASSETS', 'type' => 'asset', 'category' => 'fixed_asset', 'parent_id' => null, 'sort_order' => 2],
            ['code' => '2100', 'name' => 'Land', 'type' => 'asset', 'category' => 'fixed_asset', 'parent_code' => '2000', 'sort_order' => 10],
            ['code' => '2200', 'name' => 'Buildings', 'type' => 'asset', 'category' => 'fixed_asset', 'parent_code' => '2000', 'sort_order' => 20],
            ['code' => '2201', 'name' => 'Buildings - Cost', 'type' => 'asset', 'category' => 'fixed_asset', 'parent_code' => '2200', 'sort_order' => 21],
            ['code' => '2202', 'name' => 'Accumulated Depreciation - Buildings', 'type' => 'asset', 'category' => 'fixed_asset', 'parent_code' => '2200', 'sort_order' => 22],
            ['code' => '2300', 'name' => 'Equipment', 'type' => 'asset', 'category' => 'fixed_asset', 'parent_code' => '2000', 'sort_order' => 30],
            ['code' => '2301', 'name' => 'Equipment - Cost', 'type' => 'asset', 'category' => 'fixed_asset', 'parent_code' => '2300', 'sort_order' => 31],
            ['code' => '2302', 'name' => 'Accumulated Depreciation - Equipment', 'type' => 'asset', 'category' => 'fixed_asset', 'parent_code' => '2300', 'sort_order' => 32],
            ['code' => '2400', 'name' => 'Vehicles', 'type' => 'asset', 'category' => 'fixed_asset', 'parent_code' => '2000', 'sort_order' => 40],
            ['code' => '2401', 'name' => 'Vehicles - Cost', 'type' => 'asset', 'category' => 'fixed_asset', 'parent_code' => '2400', 'sort_order' => 41],
            ['code' => '2402', 'name' => 'Accumulated Depreciation - Vehicles', 'type' => 'asset', 'category' => 'fixed_asset', 'parent_code' => '2400', 'sort_order' => 42],
            
            // LIABILITIES
            ['code' => '3000', 'name' => 'LIABILITIES', 'type' => 'liability', 'category' => 'current_liability', 'parent_id' => null, 'sort_order' => 3],
            
            // Current Liabilities
            ['code' => '3100', 'name' => 'Accounts Payable', 'type' => 'liability', 'category' => 'payable', 'parent_code' => '3000', 'sort_order' => 10],
            ['code' => '3101', 'name' => 'Trade Payables', 'type' => 'liability', 'category' => 'payable', 'parent_code' => '3100', 'sort_order' => 11],
            ['code' => '3102', 'name' => 'Accrued Expenses', 'type' => 'liability', 'category' => 'payable', 'parent_code' => '3100', 'sort_order' => 12],
            
            ['code' => '3200', 'name' => 'Salaries Payable', 'type' => 'liability', 'category' => 'payable', 'parent_code' => '3000', 'sort_order' => 20],
            ['code' => '3300', 'name' => 'Tax Payable', 'type' => 'liability', 'category' => 'payable', 'parent_code' => '3000', 'sort_order' => 30],
            ['code' => '3400', 'name' => 'Deferred Revenue', 'type' => 'liability', 'category' => 'current_liability', 'parent_code' => '3000', 'sort_order' => 40],
            ['code' => '3401', 'name' => 'Prepaid Tuition', 'type' => 'liability', 'category' => 'current_liability', 'parent_code' => '3400', 'sort_order' => 41],
            
            // Long-term Liabilities
            ['code' => '3500', 'name' => 'Long-term Debt', 'type' => 'liability', 'category' => 'long_term_liability', 'parent_code' => '3000', 'sort_order' => 50],
            ['code' => '3501', 'name' => 'Bank Loans', 'type' => 'liability', 'category' => 'long_term_liability', 'parent_code' => '3500', 'sort_order' => 51],
            
            // EQUITY
            ['code' => '4000', 'name' => 'EQUITY', 'type' => 'equity', 'category' => 'equity', 'parent_id' => null, 'sort_order' => 4],
            ['code' => '4100', 'name' => 'Capital', 'type' => 'equity', 'category' => 'equity', 'parent_code' => '4000', 'sort_order' => 10],
            ['code' => '4200', 'name' => 'Retained Earnings', 'type' => 'equity', 'category' => 'retained_earnings', 'parent_code' => '4000', 'sort_order' => 20],
            ['code' => '4300', 'name' => 'Current Year Earnings', 'type' => 'equity', 'category' => 'equity', 'parent_code' => '4000', 'sort_order' => 30],
            
            // REVENUE
            ['code' => '5000', 'name' => 'REVENUE', 'type' => 'revenue', 'category' => 'tuition_revenue', 'parent_id' => null, 'sort_order' => 5],
            
            // Tuition Revenue
            ['code' => '5100', 'name' => 'Tuition Fees', 'type' => 'revenue', 'category' => 'tuition_revenue', 'parent_code' => '5000', 'sort_order' => 10],
            ['code' => '5101', 'name' => 'Tuition - Term 1', 'type' => 'revenue', 'category' => 'tuition_revenue', 'parent_code' => '5100', 'sort_order' => 11],
            ['code' => '5102', 'name' => 'Tuition - Term 2', 'type' => 'revenue', 'category' => 'tuition_revenue', 'parent_code' => '5100', 'sort_order' => 12],
            ['code' => '5103', 'name' => 'Tuition - Term 3', 'type' => 'revenue', 'category' => 'tuition_revenue', 'parent_code' => '5100', 'sort_order' => 13],
            
            ['code' => '5200', 'name' => 'Boarding Fees', 'type' => 'revenue', 'category' => 'tuition_revenue', 'parent_code' => '5000', 'sort_order' => 20],
            ['code' => '5300', 'name' => 'Transport Fees', 'type' => 'revenue', 'category' => 'tuition_revenue', 'parent_code' => '5000', 'sort_order' => 30],
            ['code' => '5400', 'name' => 'Registration Fees', 'type' => 'revenue', 'category' => 'tuition_revenue', 'parent_code' => '5000', 'sort_order' => 40],
            ['code' => '5500', 'name' => 'Examination Fees', 'type' => 'revenue', 'category' => 'tuition_revenue', 'parent_code' => '5000', 'sort_order' => 50],
            ['code' => '5600', 'name' => 'Library Fees', 'type' => 'revenue', 'category' => 'tuition_revenue', 'parent_code' => '5000', 'sort_order' => 60],
            ['code' => '5700', 'name' => 'Other Fees', 'type' => 'revenue', 'category' => 'tuition_revenue', 'parent_code' => '5000', 'sort_order' => 70],
            
            // Other Revenue
            ['code' => '5800', 'name' => 'Other Revenue', 'type' => 'revenue', 'category' => 'other_revenue', 'parent_code' => '5000', 'sort_order' => 80],
            ['code' => '5801', 'name' => 'Book Sales', 'type' => 'revenue', 'category' => 'other_revenue', 'parent_code' => '5800', 'sort_order' => 81],
            ['code' => '5802', 'name' => 'Uniform Sales', 'type' => 'revenue', 'category' => 'other_revenue', 'parent_code' => '5800', 'sort_order' => 82],
            ['code' => '5803', 'name' => 'Canteen Sales', 'type' => 'revenue', 'category' => 'other_revenue', 'parent_code' => '5800', 'sort_order' => 83],
            ['code' => '5804', 'name' => 'Event Revenue', 'type' => 'revenue', 'category' => 'other_revenue', 'parent_code' => '5800', 'sort_order' => 84],
            
            // Grants and Donations
            ['code' => '5900', 'name' => 'Grants and Donations', 'type' => 'revenue', 'category' => 'grants', 'parent_code' => '5000', 'sort_order' => 90],
            ['code' => '5901', 'name' => 'Government Grants', 'type' => 'revenue', 'category' => 'grants', 'parent_code' => '5900', 'sort_order' => 91],
            ['code' => '5902', 'name' => 'Private Donations', 'type' => 'revenue', 'category' => 'grants', 'parent_code' => '5900', 'sort_order' => 92],
            
            // EXPENSES
            ['code' => '6000', 'name' => 'EXPENSES', 'type' => 'expense', 'category' => 'other_expense', 'parent_id' => null, 'sort_order' => 6],
            
            // Salary Expenses
            ['code' => '6100', 'name' => 'Salaries and Wages', 'type' => 'expense', 'category' => 'salary_expense', 'parent_code' => '6000', 'sort_order' => 10],
            ['code' => '6101', 'name' => 'Teaching Staff Salaries', 'type' => 'expense', 'category' => 'salary_expense', 'parent_code' => '6100', 'sort_order' => 11],
            ['code' => '6102', 'name' => 'Administrative Staff Salaries', 'type' => 'expense', 'category' => 'salary_expense', 'parent_code' => '6100', 'sort_order' => 12],
            ['code' => '6103', 'name' => 'Support Staff Salaries', 'type' => 'expense', 'category' => 'salary_expense', 'parent_code' => '6100', 'sort_order' => 13],
            ['code' => '6104', 'name' => 'Overtime Pay', 'type' => 'expense', 'category' => 'salary_expense', 'parent_code' => '6100', 'sort_order' => 14],
            
            ['code' => '6200', 'name' => 'Benefits and Allowances', 'type' => 'expense', 'category' => 'salary_expense', 'parent_code' => '6000', 'sort_order' => 20],
            ['code' => '6201', 'name' => 'Health Insurance', 'type' => 'expense', 'category' => 'salary_expense', 'parent_code' => '6200', 'sort_order' => 21],
            ['code' => '6202', 'name' => 'Pension Contributions', 'type' => 'expense', 'category' => 'salary_expense', 'parent_code' => '6200', 'sort_order' => 22],
            
            // Utility Expenses
            ['code' => '6300', 'name' => 'Utilities', 'type' => 'expense', 'category' => 'utility_expense', 'parent_code' => '6000', 'sort_order' => 30],
            ['code' => '6301', 'name' => 'Electricity', 'type' => 'expense', 'category' => 'utility_expense', 'parent_code' => '6300', 'sort_order' => 31],
            ['code' => '6302', 'name' => 'Water', 'type' => 'expense', 'category' => 'utility_expense', 'parent_code' => '6300', 'sort_order' => 32],
            ['code' => '6303', 'name' => 'Internet and Phone', 'type' => 'expense', 'category' => 'utility_expense', 'parent_code' => '6300', 'sort_order' => 33],
            
            // Maintenance Expenses
            ['code' => '6400', 'name' => 'Maintenance and Repairs', 'type' => 'expense', 'category' => 'maintenance_expense', 'parent_code' => '6000', 'sort_order' => 40],
            ['code' => '6401', 'name' => 'Building Maintenance', 'type' => 'expense', 'category' => 'maintenance_expense', 'parent_code' => '6400', 'sort_order' => 41],
            ['code' => '6402', 'name' => 'Equipment Maintenance', 'type' => 'expense', 'category' => 'maintenance_expense', 'parent_code' => '6400', 'sort_order' => 42],
            ['code' => '6403', 'name' => 'Vehicle Maintenance', 'type' => 'expense', 'category' => 'maintenance_expense', 'parent_code' => '6400', 'sort_order' => 43],
            
            // Supply Expenses
            ['code' => '6500', 'name' => 'Supplies', 'type' => 'expense', 'category' => 'supply_expense', 'parent_code' => '6000', 'sort_order' => 50],
            ['code' => '6501', 'name' => 'Office Supplies', 'type' => 'expense', 'category' => 'supply_expense', 'parent_code' => '6500', 'sort_order' => 51],
            ['code' => '6502', 'name' => 'Teaching Materials', 'type' => 'expense', 'category' => 'supply_expense', 'parent_code' => '6500', 'sort_order' => 52],
            ['code' => '6503', 'name' => 'Cleaning Supplies', 'type' => 'expense', 'category' => 'supply_expense', 'parent_code' => '6500', 'sort_order' => 53],
            
            // Other Expenses
            ['code' => '6600', 'name' => 'Rent', 'type' => 'expense', 'category' => 'other_expense', 'parent_code' => '6000', 'sort_order' => 60],
            ['code' => '6700', 'name' => 'Insurance', 'type' => 'expense', 'category' => 'other_expense', 'parent_code' => '6000', 'sort_order' => 70],
            ['code' => '6800', 'name' => 'Professional Services', 'type' => 'expense', 'category' => 'other_expense', 'parent_code' => '6000', 'sort_order' => 80],
            ['code' => '6801', 'name' => 'Legal Fees', 'type' => 'expense', 'category' => 'other_expense', 'parent_code' => '6800', 'sort_order' => 81],
            ['code' => '6802', 'name' => 'Audit Fees', 'type' => 'expense', 'category' => 'other_expense', 'parent_code' => '6800', 'sort_order' => 82],
            ['code' => '6803', 'name' => 'Consulting Fees', 'type' => 'expense', 'category' => 'other_expense', 'parent_code' => '6800', 'sort_order' => 83],
            
            ['code' => '6900', 'name' => 'Marketing and Advertising', 'type' => 'expense', 'category' => 'other_expense', 'parent_code' => '6000', 'sort_order' => 90],
            ['code' => '7000', 'name' => 'Depreciation', 'type' => 'expense', 'category' => 'other_expense', 'parent_code' => '6000', 'sort_order' => 100],
            ['code' => '7100', 'name' => 'Bank Charges', 'type' => 'expense', 'category' => 'other_expense', 'parent_code' => '6000', 'sort_order' => 110],
            ['code' => '7200', 'name' => 'Miscellaneous Expenses', 'type' => 'expense', 'category' => 'other_expense', 'parent_code' => '6000', 'sort_order' => 120],
        ];
        
        $parentMap = [];
        
        $nonPostableCodes = ['1000', '2000', '3000', '4000', '5000', '6000'];

        foreach ($accounts as $accountData) {
            $parentId = null;
            
            if (isset($accountData['parent_code'])) {
                $parentId = $parentMap[$accountData['parent_code']] ?? null;
            }
            
            $isPostable = $accountData['is_postable'] ?? (! in_array($accountData['code'], $nonPostableCodes));

            $account = Account::create([
                'school_id' => $school->id,
                'code' => $accountData['code'],
                'name' => $accountData['name'],
                'type' => $accountData['type'],
                'category' => $accountData['category'],
                'parent_id' => $parentId,
                'opening_balance' => 0,
                'currency' => 'USD',
                'is_active' => true,
                'is_postable' => $isPostable,
                'sort_order' => $accountData['sort_order'],
            ]);
            
            $parentMap[$accountData['code']] = $account->id;
        }
        
        $this->command?->info("Chart of Accounts created for school: {$school->name}");
    }
}
