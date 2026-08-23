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
        Schema::create('timetable_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('timetable_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('school_class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->unsignedInteger('weekly_periods')->default(1);
            $table->json('preferred_days')->nullable();
            $table->json('preferred_periods')->nullable();
            $table->unsignedInteger('max_daily_lessons')->default(2);
            $table->boolean('is_double_period_allowed')->default(false);
            $table->unsignedInteger('priority')->default(1); // 1 = normal, 5 = high, 10 = specialist/critical
            $table->timestamps();

            $table->index(['school_id', 'school_class_id']);
            $table->index(['school_id', 'subject_id']);
            $table->index(['school_id', 'timetable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable_requirements');
    }
};
