<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createIfNotExists('staff_position_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_position_id')->constrained()->cascadeOnDelete();
            $table->morphs('assignable');
            $table->nullableMorphs('target');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['staff_position_id', 'assignable_id', 'assignable_type', 'target_id', 'target_type'], 'staff_pos_assign_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_position_assignments');
    }
};
