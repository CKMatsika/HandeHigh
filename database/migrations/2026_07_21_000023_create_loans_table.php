<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createIfNotExists('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('loan_type')->comment('school, bank, sacco, other');
            $table->string('loan_provider')->nullable();
            $table->decimal('loan_amount', 14, 2);
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->integer('repayment_period_months');
            $table->decimal('monthly_installment', 12, 2);
            $table->decimal('total_paid', 14, 2)->default(0);
            $table->decimal('balance', 14, 2);
            $table->string('status')->default('active')->comment('active, settled, defaulted, written_off');
            $table->date('disbursed_date');
            $table->date('first_payment_date')->nullable();
            $table->date('settled_date')->nullable();
            $table->text('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
