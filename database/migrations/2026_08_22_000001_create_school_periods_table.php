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
        Schema::create('school_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g. "Period 1", "Morning Break", "Assembly"
            $table->integer('period_sequence'); // 1, 2, 3...
            $table->string('day_of_week')->nullable(); // Monday, Tuesday... or null if daily template
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('period_type', [
                'lesson',
                'break',
                'lunch',
                'assembly',
                'chapel',
                'sport',
                'club',
                'other',
            ])->default('lesson');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'day_of_week', 'period_sequence']);
            $table->index(['school_id', 'day_of_week', 'is_active']);
            $table->index(['school_id', 'start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_periods');
    }
};
