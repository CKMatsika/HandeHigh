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
        Schema::create('timetable_substitutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('timetable_id')->constrained()->onDelete('cascade');
            $table->foreignId('timetable_slot_id')->constrained('timetable_slots')->onDelete('cascade');
            $table->foreignId('teacher_absence_id')->nullable()->constrained('teacher_absences')->nullOnDelete();
            $table->foreignId('original_teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('substitute_teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->date('date');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->decimal('score', 5, 2)->nullable();
            $table->json('score_breakdown')->nullable();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'date', 'status']);
            $table->index(['timetable_slot_id', 'date']);
            $table->index(['substitute_teacher_id', 'date', 'status']);
            $table->index(['original_teacher_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable_substitutions');
    }
};
