<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('year_end_process_id')->nullable()->constrained('year_end_processes')->nullOnDelete();
            $table->string('academic_year');
            $table->string('graduation_grade')->nullable(); // e.g., 'Form 4', 'Form 6', 'Grade 7'
            $table->string('exit_type')->default('graduated'); // graduated, transferred, withdrawn, expelled

            // Finance Clearance
            $table->string('finance_status')->default('pending'); // pending, cleared, waived
            $table->decimal('finance_balance', 15, 2)->default(0);
            $table->foreignId('finance_cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('finance_cleared_at')->nullable();
            $table->text('finance_remarks')->nullable();

            // Library Clearance
            $table->string('library_status')->default('pending'); // pending, cleared, waived
            $table->integer('unreturned_books_count')->default(0);
            $table->foreignId('library_cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('library_cleared_at')->nullable();
            $table->text('library_remarks')->nullable();

            // School Assets / Uniforms / Equipment Clearance
            $table->string('assets_status')->default('pending'); // pending, cleared, waived
            $table->integer('unreturned_assets_count')->default(0);
            $table->foreignId('assets_cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('assets_cleared_at')->nullable();
            $table->text('assets_remarks')->nullable();

            // Boarding / Hostel Clearance
            $table->string('boarding_status')->default('not_applicable'); // not_applicable, pending, cleared, waived
            $table->foreignId('boarding_cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('boarding_cleared_at')->nullable();
            $table->text('boarding_remarks')->nullable();

            // Overall Lifecycle Status
            $table->string('status')->default('pending_clearance'); // pending_clearance, fully_cleared, permanently_exited, rejected
            $table->string('certificate_number')->nullable()->unique();
            $table->dateTime('exited_at')->nullable();
            $table->foreignId('exited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('general_remarks')->nullable();

            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'academic_year']);
            $table->index(['student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_clearances');
    }
};
