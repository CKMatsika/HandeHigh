<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schemes_of_work', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('school_class_id')->constrained('classes')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('academic_year');
            $table->string('term');
            $table->enum('status', ['draft', 'preview', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['teacher_id', 'status']);
            $table->index(['school_id', 'academic_year', 'term']);
        });

        Schema::create('scheme_of_work_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_of_work_id')->constrained('schemes_of_work')->onDelete('cascade');
            $table->integer('week_number');
            $table->string('day_of_week');
            $table->string('topic');
            $table->string('sub_topic')->nullable();
            $table->text('objectives');
            $table->text('teaching_methods')->nullable();
            $table->text('resources')->nullable();
            $table->text('assessment')->nullable();
            $table->text('remarks')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['scheme_of_work_id', 'week_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheme_of_work_items');
        Schema::dropIfExists('schemes_of_work');
    }
};
