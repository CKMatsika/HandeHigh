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
        Schema::createIfNotExists('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();

            $table->string('academic_year', 20);
            $table->string('term', 20);

            $table->string('grade')->nullable();
            $table->string('class_name')->nullable();

            $table->boolean('is_boarding')->default(false);
            $table->boolean('has_transport')->default(false);

            $table->date('enrollment_date');
            $table->string('status', 20)->default('active');

            $table->timestamps();

            $table->index(['school_id', 'academic_year', 'term']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
