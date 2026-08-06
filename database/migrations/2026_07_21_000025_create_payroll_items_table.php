<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createIfNotExists('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // Earnings
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('housing_allowance', 12, 2)->default(0);
            $table->decimal('transport_allowance', 12, 2)->default(0);
            $table->decimal('communication_allowance', 12, 2)->default(0);
            $table->decimal('education_allowance', 12, 2)->default(0);
            $table->decimal('leave_allowance', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('overtime', 12, 2)->default(0);
            $table->decimal('other_earnings', 12, 2)->default(0);
            $table->string('other_earnings_desc')->nullable();

            // School top-up allowances (outside government)
            $table->decimal('school_top_up', 12, 2)->default(0);

            $table->decimal('gross_pay', 12, 2)->default(0);

            // Statutory Deductions
            $table->decimal('paye', 12, 2)->default(0)->comment('ZIMRA PAYE income tax');
            $table->decimal('aids_levy', 12, 2)->default(0)->comment('3% of PAYE');
            $table->decimal('nssa_employee', 12, 2)->default(0)->comment('NSSA employee contribution');
            $table->decimal('nssa_employer', 12, 2)->default(0)->comment('NSSA employer contribution');

            // Other Deductions
            $table->decimal('trade_union', 12, 2)->default(0);
            $table->decimal('nec', 12, 2)->default(0);
            $table->decimal('loan_repayment', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->string('other_deductions_desc')->nullable();

            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);

            $table->string('payment_method')->default('bank_transfer');
            $table->string('bank_account')->nullable();
            $table->string('status')->default('active')->comment('active, paid, cancelled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payroll_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
