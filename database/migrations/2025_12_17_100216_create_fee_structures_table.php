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
        Schema::createIfNotExists('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();

            $table->string('academic_year', 20);
            $table->string('term', 20);
            $table->string('grade')->nullable();

            $table->string('category', 50);
            $table->string('code', 50);
            $table->string('label');

            $table->decimal('amount', 12, 2);
            $table->boolean('is_optional')->default(false);

            $table->string('subject_name')->nullable();
            $table->string('service_type')->nullable();

            $table->timestamps();

            $table->unique(['school_id', 'academic_year', 'term', 'grade', 'code'], 'fee_structures_unique_per_grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
