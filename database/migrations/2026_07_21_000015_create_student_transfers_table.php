<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createIfNotExists('student_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('transfer_type')->comment('transfer_out, inter_school');
            $table->string('destination_school')->nullable();
            $table->string('destination_address')->nullable();
            $table->string('destination_contact')->nullable();
            $table->date('transfer_date');
            $table->string('reason')->nullable();
            $table->string('academic_year');
            $table->string('term');
            $table->string('grade_at_transfer')->nullable();
            $table->string('class_at_transfer')->nullable();
            $table->text('conduct_remarks')->nullable();
            $table->text('academic_remarks')->nullable();
            $table->string('status')->default('pending')->comment('pending, approved, completed, rejected');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_transfers');
    }
};
