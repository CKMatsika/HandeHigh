<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sda_resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sda_meeting_id')->constrained()->onDelete('cascade');
            $table->foreignId('proposed_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('seconded_by')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->text('background')->nullable();
            $table->text('implementation_plan')->nullable();
            $table->enum('resolution_type', ['policy', 'budget', 'procurement', 'appointment', 'general'])->default('general');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['proposed', 'seconded', 'debated', 'voted', 'passed', 'rejected', 'implemented', 'cancelled'])->default('proposed');
            $table->integer('votes_for')->default(0);
            $table->integer('votes_against')->default(0);
            $table->integer('votes_abstained')->default(0);
            $table->date('implementation_deadline')->nullable();
            $table->text('implementation_notes')->nullable();
            $table->date('implemented_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sda_resolutions');
    }
};
