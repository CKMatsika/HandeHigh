<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year');
            $table->string('term')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['student_id', 'subject_id', 'academic_year'], 'student_subject_year_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_subjects');
    }
};
