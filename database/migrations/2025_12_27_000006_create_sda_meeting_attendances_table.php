<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sda_meeting_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sda_meeting_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('attendance_status', ['present', 'absent', 'apologized', 'late'])->default('present');
            $table->time('arrival_time')->nullable();
            $table->text('apology_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sda_meeting_attendances');
    }
};
