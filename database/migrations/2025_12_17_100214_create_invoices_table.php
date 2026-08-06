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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('guardian_id')->nullable()->constrained('guardians')->nullOnDelete();
            $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();

            $table->string('number');
            $table->string('type', 30)->default('fees');

            $table->string('academic_year', 20)->nullable();
            $table->string('term', 20)->nullable();

            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);

            $table->date('issued_at')->nullable();
            $table->date('due_date')->nullable();

            $table->string('status', 20)->default('unpaid');

            $table->timestamps();

            $table->unique(['school_id', 'number']);
            $table->index(['school_id', 'academic_year', 'term']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
