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
        Schema::createIfNotExists('student_sports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->string('sport_name');
            $table->string('sport_category'); // team_sport, individual_sport, athletics, etc.
            $table->string('position')->nullable(); // captain, player, reserve, etc.
            $table->string('team_level')->nullable(); // varsity, junior, senior, etc.
            $table->text('achievements')->nullable();
            $table->date('started_date');
            $table->date('ended_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['student_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_sports');
    }
};
