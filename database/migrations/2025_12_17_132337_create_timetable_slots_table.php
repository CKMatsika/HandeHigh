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
        Schema::createIfNotExists('timetable_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_id')->constrained()->onDelete('cascade');
            $table->foreignId('school_class_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_id')->nullable()->constrained()->onDelete('set null');
            $table->string('day_of_week'); // Monday, Tuesday, etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['scheduled', 'conflict', 'cancelled'])->default('scheduled');
            $table->json('conflicts')->nullable(); // Store conflict details
            $table->decimal('ai_score', 5, 2)->nullable(); // AI optimization score
            $table->timestamps();
            
            $table->index(['timetable_id', 'day_of_week', 'start_time']);
            $table->index(['teacher_id', 'day_of_week', 'start_time']);
            $table->index(['school_class_id', 'day_of_week', 'start_time']);
            $table->index(['room_id', 'day_of_week', 'start_time']);
            $table->index(['subject_id', 'day_of_week', 'start_time']);
            $table->unique(['timetable_id', 'school_class_id', 'day_of_week', 'start_time'], 'unique_class_time_slot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable_slots');
    }
};
