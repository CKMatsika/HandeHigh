<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createIfNotExists('bed_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bed_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year');
            $table->string('term');
            $table->date('assigned_date');
            $table->date('released_date')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->index(['bed_id', 'is_current']);
            $table->index(['student_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bed_assignments');
    }
};
