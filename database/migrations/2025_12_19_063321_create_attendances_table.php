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
        Schema::createIfNotExists('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('attendable_type'); // Student, Staff
            $table->foreignId('attendable_id'); // ID of Student or Staff
            $table->date('attendance_date');
            $table->enum('status', ['present', 'absent', 'late', 'excused', 'sick_leave', 'vacation']);
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['attendable_type', 'attendable_id']);
            $table->index(['school_id', 'attendance_date']);
            $table->unique(['attendable_type', 'attendable_id', 'attendance_date'], 'attendance_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
