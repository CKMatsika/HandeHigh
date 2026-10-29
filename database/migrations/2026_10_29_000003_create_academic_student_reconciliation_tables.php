<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_reconciliation_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('academic_year', 20);
            $table->string('term', 30);
            $table->boolean('complete_register')->default(false);
            $table->boolean('complete_invoice_register')->default(false);
            $table->string('status', 24)->default('completed');
            $table->unsignedInteger('source_count')->default(0);
            $table->unsignedInteger('source_rows_count')->default(0);
            $table->unsignedInteger('matched_rows_count')->default(0);
            $table->unsignedInteger('open_findings_count')->default(0);
            $table->json('summary')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'academic_year', 'term', 'created_at'], 'student_recon_batch_scope_idx');
        });

        Schema::create('student_reconciliation_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('student_reconciliation_batches')->cascadeOnDelete();
            $table->string('source_type', 32);
            $table->string('original_filename', 255);
            $table->unsignedInteger('header_row')->nullable();
            $table->unsignedInteger('rows_read')->default(0);
            $table->json('recognized_columns')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'source_type'], 'student_recon_source_type_idx');
        });

        Schema::create('student_reconciliation_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('student_reconciliation_batches')->cascadeOnDelete();
            $table->foreignId('source_id')->constrained('student_reconciliation_sources')->cascadeOnDelete();
            $table->unsignedInteger('source_row_number');
            $table->json('normalized_data');
            $table->string('identity_key', 64)->nullable();
            $table->string('match_status', 32)->default('unmatched');
            $table->string('match_confidence', 20)->default('none');
            $table->foreignId('matched_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->json('candidate_student_ids')->nullable();
            $table->decimal('erp_invoice_total', 12, 2)->nullable();
            $table->json('erp_invoice_ids')->nullable();
            $table->timestamps();

            $table->unique(['source_id', 'source_row_number'], 'student_recon_source_row_unique');
            $table->index(['batch_id', 'matched_student_id'], 'student_recon_batch_student_idx');
            $table->index(['batch_id', 'match_status'], 'student_recon_batch_match_idx');
        });

        Schema::create('student_reconciliation_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('student_reconciliation_batches')->cascadeOnDelete();
            $table->foreignId('source_row_id')->nullable()->constrained('student_reconciliation_rows')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('code', 64);
            $table->string('severity', 16)->default('warning');
            $table->string('field', 64)->nullable();
            $table->text('source_value')->nullable();
            $table->text('erp_value')->nullable();
            $table->text('message');
            $table->string('status', 20)->default('open');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'status', 'severity'], 'student_recon_findings_status_idx');
            $table->index(['batch_id', 'code'], 'student_recon_findings_code_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_reconciliation_findings');
        Schema::dropIfExists('student_reconciliation_rows');
        Schema::dropIfExists('student_reconciliation_sources');
        Schema::dropIfExists('student_reconciliation_batches');
    }
};
