<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createIfNotExists('employee_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('allowance_type_id')->constrained('allowance_types')->cascadeOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->date('effective_date')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'allowance_type_id'], 'emp_allowance_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_allowances');
    }
};
