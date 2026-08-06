<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createIfNotExists('sda_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sda_committee_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('meeting_date');
            $table->string('venue')->nullable();
            $table->enum('meeting_type', ['regular', 'emergency', 'annual', 'special'])->default('regular');
            $table->text('agenda')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->integer('duration_minutes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sda_meetings');
    }
};
