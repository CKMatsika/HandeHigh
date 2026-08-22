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
        Schema::create('timetable_fixed_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('timetable_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name'); // e.g. "Monday Morning Assembly", "Staff Meeting", "Friday Sport"
            $table->enum('activity_type', [
                'assembly',
                'chapel',
                'sport',
                'club',
                'staff_meeting',
                'other',
            ])->default('assembly');
            $table->string('day_of_week'); // Monday, Tuesday, etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('school_period_id')->nullable()->constrained('school_periods')->nullOnDelete();
            $table->foreignId('school_class_id')->nullable()->constrained('classes')->cascadeOnDelete(); // null = all classes
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->cascadeOnDelete(); // null = all teachers/general
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->boolean('is_locked')->default(true); // Hard lock against ordinary lesson scheduling
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'day_of_week', 'start_time']);
            $table->index(['timetable_id', 'day_of_week']);
            $table->index(['school_id', 'activity_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable_fixed_activities');
    }
};
