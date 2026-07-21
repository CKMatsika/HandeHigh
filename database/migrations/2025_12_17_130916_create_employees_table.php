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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->string('employee_id')->unique();
            $table->string('position');
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern']);
            $table->enum('employment_status', ['active', 'terminated', 'resigned', 'on_leave']);
            $table->date('date_of_birth');
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();
            $table->decimal('salary', 12, 2);
            $table->text('work_schedule')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->string('profile_photo')->nullable();
            $table->timestamps();
            
            $table->index(['school_id', 'employment_status']);
            $table->index(['school_id', 'department_id']);
            $table->unique(['school_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
