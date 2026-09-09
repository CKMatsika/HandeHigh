<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('school_class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->string('academic_year', 20);
            $table->string('term', 20);
            $table->foreignId('grade_scheme_id')->nullable()->constrained('grade_schemes')->nullOnDelete();
            
            // Lifecycle Status
            $table->string('status', 40)->default('NOT_AVAILABLE');
            $table->string('version', 10)->default('1.0');
            $table->boolean('is_locked')->default(false);

            // Academic Aggregates
            $table->decimal('term_average', 5, 2)->nullable();
            $table->string('overall_grade', 10)->nullable();
            $table->integer('total_subjects')->default(0);
            $table->integer('subjects_passed')->default(0);
            $table->integer('subjects_failed')->default(0);
            $table->string('overall_status', 50)->nullable(); // e.g. "Distinction", "Pass", "At Risk / Needs Support"

            // Headmaster Comments & Digital Signature
            $table->text('headmaster_comment')->nullable();
            $table->json('headmaster_formatting')->nullable();
            $table->foreignId('headmaster_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('headmaster_signed_at')->nullable();
            $table->string('headmaster_signature_path')->nullable();
            $table->json('headmaster_signature_meta')->nullable();

            // Deputy Headmaster Comments & Digital Signature
            $table->text('deputy_comment')->nullable();
            $table->json('deputy_formatting')->nullable();
            $table->foreignId('deputy_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deputy_signed_at')->nullable();
            $table->string('deputy_signature_path')->nullable();
            $table->json('deputy_signature_meta')->nullable();

            // Digital Stamp
            $table->timestamp('stamp_applied_at')->nullable();
            $table->foreignId('stamp_applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stamp_path')->nullable();
            $table->json('stamp_meta')->nullable();

            // Finalization & Locks
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->unique(['school_id', 'student_id', 'academic_year', 'term', 'version'], 'perf_report_unique');
            $table->index(['school_id', 'academic_year', 'term', 'status']);
            $table->index(['school_class_id', 'academic_year', 'term']);
        });

        Schema::create('performance_report_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_report_id')->constrained('performance_reports')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('curriculum_id')->nullable()->constrained('curricula')->nullOnDelete();
            $table->foreignId('assigned_teacher_id')->nullable()->constrained('users')->nullOnDelete();

            // Marks & Evaluation
            $table->decimal('mark_obtained', 6, 2)->nullable();
            $table->decimal('max_mark', 6, 2)->default(100.00);
            $table->decimal('percentage', 6, 2)->nullable();
            $table->string('grade', 10)->nullable();
            $table->boolean('is_pass')->nullable();
            $table->string('result_status', 20)->default('present'); // present, absent, no_result, withheld, cancelled

            // Teacher Subject Comment & Typography
            $table->text('comment')->nullable();
            $table->string('comment_font', 50)->default('Arial');
            $table->integer('comment_font_size')->default(11);
            $table->json('comment_formatting')->nullable();

            // Completion & Responsibility
            $table->string('status', 30)->default('not_started'); // not_started, draft, complete, locked
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->unique(['performance_report_id', 'subject_id'], 'perf_report_subject_unique');
            $table->index(['school_id', 'assigned_teacher_id', 'status']);
        });

        Schema::create('performance_report_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('performance_report_id')->constrained('performance_reports')->cascadeOnDelete();
            $table->foreignId('performance_report_subject_id')->nullable()->constrained('performance_report_subjects')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50); // e.g. MARK_ENTERED, COMMENT_UPDATED, SUBJECT_COMPLETED, SIGNATURE_APPLIED, STAMP_APPLIED, REPORT_FINALIZED
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['performance_report_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_report_audits');
        Schema::dropIfExists('performance_report_subjects');
        Schema::dropIfExists('performance_reports');
    }
};
