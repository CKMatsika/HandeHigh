<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createIfNotExists('career_paths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('typical_subjects')->nullable();
            $table->json('subject_requirements')->nullable();
            $table->string('education_level')->nullable();
            $table->text('skills')->nullable();
            $table->text('outlook')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::createIfNotExists('student_career_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('desired_career')->nullable();
            $table->foreignId('career_path_id')->nullable()->constrained('career_paths')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->json('preferred_subjects')->nullable();
            $table->string('hobbies')->nullable();
            $table->timestamps();
            $table->unique(['student_id']);
        });

        Schema::createIfNotExists('career_guidance_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('assessment_date');
            $table->json('subject_performance')->nullable();
            $table->json('strengths')->nullable();
            $table->json('areas_for_improvement')->nullable();
            $table->json('suggested_careers')->nullable();
            $table->json('improvement_tips')->nullable();
            $table->text('overall_feedback')->nullable();
            $table->text('student_response')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'assessment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_guidance_assessments');
        Schema::dropIfExists('student_career_interests');
        Schema::dropIfExists('career_paths');
    }
};
