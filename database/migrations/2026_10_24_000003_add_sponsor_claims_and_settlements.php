<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funding_sponsors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->string('category', 30); // government, company, ngo, donor, individual
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'code']);
        });
        Schema::table('student_scholarships', function (Blueprint $table) {
            $table->foreignId('funding_sponsor_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('academic_year', 20)->nullable();
            $table->string('funding_source', 20)->nullable(); // explicit internal/external for new awards
            $table->text('recognition_evidence')->nullable();
        });
        Schema::create('sponsor_claim_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('funding_sponsor_id')->constrained()->restrictOnDelete();
            $table->string('academic_year', 20);
            $table->string('reference', 100);
            $table->foreignId('migration_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('submitted_on')->nullable();
            $table->string('status', 30)->default('pending_verification');
            $table->string('supporting_reference')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('approval_reference')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'reference']);
        });
        Schema::create('sponsor_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('funding_sponsor_id')->constrained()->restrictOnDelete();
            $table->foreignId('sponsor_claim_batch_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('migration_staging_row_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('academic_year', 20);
            $table->string('reference', 100);
            $table->decimal('approved_amount', 15, 2);
            $table->string('active_identity', 64)->nullable()->unique();
            $table->foreignId('supersedes_claim_id')->nullable()->constrained('sponsor_claims')->restrictOnDelete();
            $table->decimal('recognised_amount', 15, 2)->default(0);
            $table->decimal('received_before_cutover', 15, 2)->default(0);
            $table->decimal('opening_outstanding', 15, 2)->default(0);
            $table->decimal('disputed_amount', 15, 2)->default(0);
            $table->decimal('rejected_amount', 15, 2)->default(0);
            $table->date('submitted_on')->nullable();
            $table->string('currency', 3)->nullable();
            $table->json('fee_votes');
            $table->json('allocated_lines')->nullable();
            $table->string('classification', 40);
            $table->string('opening_inclusion', 40);
            $table->string('status', 30)->default('pending');
            $table->date('accounting_date')->nullable();
            $table->foreignId('receivable_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('journal_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('opening_journal_batch_id')->nullable()->constrained('journal_batches')->restrictOnDelete();
            $table->foreignId('reversal_batch_id')->nullable()->constrained('journal_batches')->restrictOnDelete();
            $table->date('reversed_on')->nullable();
            $table->text('recognition_evidence')->nullable();
            $table->string('supporting_reference');
            $table->foreignId('recognition_prepared_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('recognition_approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('recognition_approval_reference')->nullable();
            $table->timestamps();
            // One consolidated beneficiary claim per sponsor/year; amendments retain its history.
            // The active identity is released only by a checked reversal; historical revisions remain immutable.
            $table->unique('migration_staging_row_id');
        });
        Schema::create('sponsor_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('funding_sponsor_id')->constrained()->restrictOnDelete();
            $table->string('reference', 100);
            $table->date('received_on');
            $table->decimal('amount', 15, 2);
            $table->foreignId('bank_account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('currency', 3);
            $table->string('status', 20)->default('pending');
            $table->boolean('cash_received')->default(false);
            $table->string('supporting_reference');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('approval_reference')->nullable();
            $table->foreignId('journal_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('reversal_batch_id')->nullable()->constrained('journal_batches')->restrictOnDelete();
            $table->date('reversed_on')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'reference']);
        });
        Schema::create('sponsor_settlement_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('sponsor_settlement_id')->constrained()->restrictOnDelete();
            $table->string('target_type', 20); // claim or scholarship
            $table->unsignedBigInteger('target_id');
            $table->decimal('amount', 15, 2);
            $table->foreignId('receivable_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['sponsor_settlement_id', 'target_type', 'target_id'], 'unique_settlement_target');
        });
        Schema::create('scholarship_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('action', 40);
            $table->string('subject_type', 100);
            $table->unsignedBigInteger('subject_id');
            $table->json('payload');
            $table->string('status', 20)->default('pending');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('approval_reference')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Sponsor financial history requires a reviewed data-preservation rollback plan.');
    }
};
