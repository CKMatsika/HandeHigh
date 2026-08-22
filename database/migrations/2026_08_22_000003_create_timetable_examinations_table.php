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
        Schema::create('timetable_examinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('timetable_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('exam_id')->nullable()->constrained('exams')->nullOnDelete();
            $table->string('title'); // e.g. "ZIMSEC / Cambridge O-Level Mathematics Paper 1"
            $table->enum('exam_type', [
                'internal',
                'mock',
                'national',
                'other',
            ])->default('internal');
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('school_class_id')->nullable()->constrained('classes')->cascadeOnDelete(); // null = all or general
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->foreignId('supervisor_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->date('exam_date')->nullable();
            $table->string('day_of_week'); // Monday, Tuesday, etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_locked')->default(true); // National exams and locked exams cannot be overwritten
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'day_of_week', 'start_time']);
            $table->index(['timetable_id', 'day_of_week']);
            $table->index(['school_id', 'exam_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable_examinations');
    }
};
