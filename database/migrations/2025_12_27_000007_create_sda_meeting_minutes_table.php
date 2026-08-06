<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createIfNotExists('sda_meeting_minutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sda_meeting_id')->constrained()->onDelete('cascade');
            $table->foreignId('recorded_by')->constrained('users')->onDelete('cascade');
            $table->text('opening_remarks')->nullable();
            $table->text('previous_minutes_summary')->nullable();
            $table->text('matters_arising')->nullable();
            $table->text('new_business')->nullable();
            $table->text('other_business')->nullable();
            $table->text('closing_remarks')->nullable();
            $table->dateTime('next_meeting_date')->nullable();
            $table->enum('status', ['draft', 'review', 'approved', 'published'])->default('draft');
            $table->text('chairman_review_notes')->nullable();
            $table->timestamp('chairman_reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sda_meeting_minutes');
    }
};
