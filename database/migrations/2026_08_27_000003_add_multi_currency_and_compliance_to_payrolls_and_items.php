<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            // Dual currency aggregated totals
            $table->decimal('total_gross_usd', 14, 2)->default(0)->after('status');
            $table->decimal('total_gross_zwg', 14, 2)->default(0)->after('total_gross_usd');
            $table->decimal('total_deductions_usd', 14, 2)->default(0)->after('total_gross_zwg');
            $table->decimal('total_deductions_zwg', 14, 2)->default(0)->after('total_deductions_usd');
            $table->decimal('total_net_usd', 14, 2)->default(0)->after('total_deductions_zwg');
            $table->decimal('total_net_zwg', 14, 2)->default(0)->after('total_net_usd');
            
            // Statutory totals breakdown
            $table->decimal('total_paye_usd', 14, 2)->default(0)->after('total_net_zwg');
            $table->decimal('total_paye_zwg', 14, 2)->default(0)->after('total_paye_usd');
            $table->decimal('total_aids_levy_usd', 14, 2)->default(0)->after('total_paye_zwg');
            $table->decimal('total_aids_levy_zwg', 14, 2)->default(0)->after('total_aids_levy_usd');
            $table->decimal('total_employer_nssa_usd', 12, 2)->default(0)->after('total_employer_nssa');
            $table->decimal('total_employer_nssa_zwg', 12, 2)->default(0)->after('total_employer_nssa_usd');
            $table->decimal('total_employer_nec_usd', 12, 2)->default(0)->after('total_employer_nssa_zwg');
            $table->decimal('total_employer_nec_zwg', 12, 2)->default(0)->after('total_employer_nec_usd');
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->string('currency_mode', 10)->default('USD')->after('employee_id')->comment('USD, ZWG, DUAL');

            // USD Specific Breakdown
            $table->decimal('basic_salary_usd', 12, 2)->default(0)->after('basic_salary');
            $table->decimal('basic_salary_zwg', 12, 2)->default(0)->after('basic_salary_usd');
            
            $table->decimal('allowances_usd', 12, 2)->default(0)->after('school_top_up');
            $table->decimal('allowances_zwg', 12, 2)->default(0)->after('allowances_usd');
            $table->decimal('bonus_usd', 12, 2)->default(0)->after('allowances_zwg');
            $table->decimal('bonus_zwg', 12, 2)->default(0)->after('bonus_usd');
            $table->decimal('overtime_usd', 12, 2)->default(0)->after('bonus_zwg');
            $table->decimal('overtime_zwg', 12, 2)->default(0)->after('overtime_usd');

            $table->decimal('gross_usd', 12, 2)->default(0)->after('gross_pay');
            $table->decimal('gross_zwg', 12, 2)->default(0)->after('gross_usd');

            // Dual Statutory Deductions
            $table->decimal('paye_usd', 12, 2)->default(0)->after('paye');
            $table->decimal('paye_zwg', 12, 2)->default(0)->after('paye_usd');
            $table->decimal('aids_levy_usd', 12, 2)->default(0)->after('aids_levy');
            $table->decimal('aids_levy_zwg', 12, 2)->default(0)->after('aids_levy_usd');

            $table->decimal('nssa_employee_usd', 12, 2)->default(0)->after('nssa_employee');
            $table->decimal('nssa_employee_zwg', 12, 2)->default(0)->after('nssa_employee_usd');
            $table->decimal('nssa_employer_usd', 12, 2)->default(0)->after('nssa_employer');
            $table->decimal('nssa_employer_zwg', 12, 2)->default(0)->after('nssa_employer_usd');

            $table->decimal('nec_employee_usd', 12, 2)->default(0)->after('nec');
            $table->decimal('nec_employee_zwg', 12, 2)->default(0)->after('nec_employee_usd');
            $table->decimal('nec_employer_usd', 12, 2)->default(0)->after('nec_employee_zwg');
            $table->decimal('nec_employer_zwg', 12, 2)->default(0)->after('nec_employer_usd');

            $table->decimal('trade_union_usd', 12, 2)->default(0)->after('trade_union');
            $table->decimal('trade_union_zwg', 12, 2)->default(0)->after('trade_union_usd');

            // Medical Aid & Tax Credits
            $table->decimal('medical_aid_usd', 12, 2)->default(0)->after('trade_union_zwg');
            $table->decimal('medical_aid_zwg', 12, 2)->default(0)->after('medical_aid_usd');
            $table->decimal('medical_aid_tax_credit_usd', 12, 2)->default(0)->after('medical_aid_zwg')->comment('50% of employee medical aid in USD');
            $table->decimal('medical_aid_tax_credit_zwg', 12, 2)->default(0)->after('medical_aid_tax_credit_usd')->comment('50% of employee medical aid in ZWG');

            // Loan and Other Deductions Dual
            $table->decimal('loan_repayment_usd', 12, 2)->default(0)->after('loan_repayment');
            $table->decimal('loan_repayment_zwg', 12, 2)->default(0)->after('loan_repayment_usd');
            $table->decimal('other_deductions_usd', 12, 2)->default(0)->after('other_deductions');
            $table->decimal('other_deductions_zwg', 12, 2)->default(0)->after('other_deductions_usd');

            $table->decimal('total_deductions_usd', 12, 2)->default(0)->after('total_deductions');
            $table->decimal('total_deductions_zwg', 12, 2)->default(0)->after('total_deductions_usd');

            $table->decimal('net_pay_usd', 12, 2)->default(0)->after('net_pay');
            $table->decimal('net_pay_zwg', 12, 2)->default(0)->after('net_pay_usd');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'total_gross_usd',
                'total_gross_zwg',
                'total_deductions_usd',
                'total_deductions_zwg',
                'total_net_usd',
                'total_net_zwg',
                'total_paye_usd',
                'total_paye_zwg',
                'total_aids_levy_usd',
                'total_aids_levy_zwg',
                'total_employer_nssa_usd',
                'total_employer_nssa_zwg',
                'total_employer_nec_usd',
                'total_employer_nec_zwg',
            ]);
        });

        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn([
                'currency_mode',
                'basic_salary_usd',
                'basic_salary_zwg',
                'allowances_usd',
                'allowances_zwg',
                'bonus_usd',
                'bonus_zwg',
                'overtime_usd',
                'overtime_zwg',
                'gross_usd',
                'gross_zwg',
                'paye_usd',
                'paye_zwg',
                'aids_levy_usd',
                'aids_levy_zwg',
                'nssa_employee_usd',
                'nssa_employee_zwg',
                'nssa_employer_usd',
                'nssa_employer_zwg',
                'nec_employee_usd',
                'nec_employee_zwg',
                'nec_employer_usd',
                'nec_employer_zwg',
                'trade_union_usd',
                'trade_union_zwg',
                'medical_aid_usd',
                'medical_aid_zwg',
                'medical_aid_tax_credit_usd',
                'medical_aid_tax_credit_zwg',
                'loan_repayment_usd',
                'loan_repayment_zwg',
                'other_deductions_usd',
                'other_deductions_zwg',
                'total_deductions_usd',
                'total_deductions_zwg',
                'net_pay_usd',
                'net_pay_zwg',
            ]);
        });
    }
};
