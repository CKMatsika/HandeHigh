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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('other_names')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();

            $table->string('admission_number')->nullable();
            $table->string('registration_number')->nullable();

            $table->string('grade')->nullable();
            $table->string('class_name')->nullable();

            $table->boolean('is_boarding')->default(false);
            $table->boolean('has_transport')->default(false);

            $table->string('status')->default('active');

            $table->timestamps();

            $table->unique(['school_id', 'admission_number']);
            $table->unique(['school_id', 'registration_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
