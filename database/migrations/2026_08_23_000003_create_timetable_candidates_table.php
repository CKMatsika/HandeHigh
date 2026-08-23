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
        Schema::create('timetable_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('generation_run_id')->constrained('timetable_generation_runs')->cascadeOnDelete();
            $table->foreignId('timetable_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('candidate_number')->default(1);
            $table->decimal('score', 5, 2)->default(0.00);
            $table->unsignedInteger('hard_conflicts_count')->default(0);
            $table->unsignedInteger('soft_warnings_count')->default(0);
            $table->json('score_breakdown')->nullable();
            $table->json('allocations')->nullable();
            $table->json('unallocated_requirements')->nullable();
            $table->json('metrics')->nullable();
            $table->boolean('is_applied')->default(false);
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'timetable_id']);
            $table->index(['school_id', 'generation_run_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable_candidates');
    }
};
