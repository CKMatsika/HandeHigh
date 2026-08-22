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
        Schema::table('timetable_slots', function (Blueprint $table) {
            $table->foreignId('school_period_id')->nullable()->after('room_id')->constrained('school_periods')->nullOnDelete();
            $table->boolean('is_locked')->default(false)->after('status');
            $table->enum('slot_type', ['lesson', 'activity', 'exam'])->default('lesson')->after('is_locked');
            $table->string('activity_name')->nullable()->after('slot_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timetable_slots', function (Blueprint $table) {
            $table->dropForeign(['school_period_id']);
            $table->dropColumn(['school_period_id', 'is_locked', 'slot_type', 'activity_name']);
        });
    }
};
