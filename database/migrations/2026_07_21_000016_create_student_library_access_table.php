<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_library_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_member')->default(true);
            $table->date('membership_date')->nullable();
            $table->integer('max_books')->default(3);
            $table->integer('max_days')->default(14);
            $table->decimal('fine_per_day', 8, 2)->default(0.50);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_library_access');
    }
};
