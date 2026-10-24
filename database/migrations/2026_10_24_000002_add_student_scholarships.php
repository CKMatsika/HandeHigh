<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('funding_status', 20)->default('regular')->index();
        });
        Schema::create('student_scholarships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->string('reference', 100);
            $table->string('name');
            $table->string('sponsor')->nullable();
            $table->string('coverage_type', 20); // full or partial, within selected votes only
            $table->string('coverage_basis', 20); // percentage or amount (lifetime award cap)
            $table->decimal('coverage_value', 15, 2);
            $table->json('covered_votes');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 20)->default('pending');
            $table->string('accounting_treatment', 30)->default('unconfigured');
            $table->string('supporting_reference')->nullable();
            $table->text('approval_reference')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'student_id', 'reference'], 'student_scholarship_reference');
        });
        Schema::create('scholarship_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_scholarship_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->date('accounting_date');
            $table->decimal('amount', 15, 2);
            $table->json('lines'); // immutable item IDs, vote codes and covered amounts
            $table->json('award_snapshot');
            $table->string('status', 20)->default('posted');
            $table->foreignId('journal_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('reversal_batch_id')->nullable()->constrained('journal_batches')->restrictOnDelete();
            $table->date('reversed_on')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();
            $table->unique(['student_scholarship_id', 'invoice_id'], 'scholarship_invoice_once');
        });
        Schema::create('scholarship_sponsor_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('scholarship_application_id')->constrained()->restrictOnDelete();
            $table->string('reference', 100);
            $table->date('received_on');
            $table->decimal('amount', 15, 2);
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->foreignId('journal_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('reversal_batch_id')->nullable()->constrained('journal_batches')->restrictOnDelete();
            $table->date('reversed_on')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['school_id', 'reference'], 'sponsor_receipt_reference');
        });
    }

    public function down(): void
    {
        // Operational award and journal history must be preserved. Rollback is manual.
        throw new RuntimeException('Scholarship rollback requires a reviewed data-preservation plan.');
    }
};
