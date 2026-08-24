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
        Schema::create('timetable_operational_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('timetable_id')->constrained()->onDelete('cascade');
            $table->foreignId('timetable_slot_id')->nullable()->constrained('timetable_slots')->nullOnDelete();
            $table->string('change_type'); // teacher_change, room_change, lesson_moved, lesson_cancelled, lesson_restored, substitute_assigned
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('revision');
            $table->timestamps();

            $table->index(['school_id', 'timetable_id', 'revision']);
            $table->index(['timetable_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable_operational_changes');
    }
};
