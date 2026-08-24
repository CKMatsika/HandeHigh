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
        Schema::create('teacher_absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->json('affected_period_ids')->nullable(); // null means whole day, or list of period IDs
            $table->string('reason'); // sick, personal, emergency, training, official_duty, other
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'resolved', 'cancelled'])->default('active');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'teacher_id', 'start_date', 'end_date']);
            $table->index(['school_id', 'status', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_absences');
    }
};
