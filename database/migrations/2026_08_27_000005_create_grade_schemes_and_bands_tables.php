<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_schemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->cascadeOnDelete();
            $table->string('name'); // e.g. "ZIMSEC O-Level Standard", "ZIMSEC A-Level", "Junior Secondary"
            $table->string('level')->default('O-Level'); // Primary, Junior, O-Level, A-Level, General
            $table->text('description')->nullable();
            $table->string('effective_from')->default('2020');
            $table->string('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['school_id', 'level', 'is_active']);
        });

        Schema::create('grade_bands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_scheme_id')->constrained('grade_schemes')->cascadeOnDelete();
            $table->string('grade', 10); // e.g. 'A', 'B', 'C', 'D', 'E', 'U'
            $table->decimal('min_percentage', 5, 2); // e.g. 75.00
            $table->decimal('max_percentage', 5, 2); // e.g. 100.00
            $table->string('description')->nullable(); // Distinction, Merit, Credit, Pass, Fail
            $table->boolean('is_pass')->default(true);
            $table->integer('display_order')->default(1);
            $table->timestamps();

            $table->index(['grade_scheme_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_bands');
        Schema::dropIfExists('grade_schemes');
    }
};
