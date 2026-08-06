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
        Schema::createIfNotExists('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->enum('type', ['teacher', 'non_teaching']);
            $table->string('department')->nullable();
            $table->string('position')->nullable();
            $table->string('employee_id')->unique();
            $table->date('hire_date');
            $table->enum('status', ['active', 'inactive', 'terminated'])->default('active');
            $table->decimal('salary', 10, 2)->nullable();
            $table->timestamps();
            
            $table->index(['school_id', 'type']);
            $table->index(['school_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
