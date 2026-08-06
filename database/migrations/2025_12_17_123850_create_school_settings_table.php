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
        Schema::createIfNotExists('school_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('key');
            $table->text('value');
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->string('category')->default('general'); // general, fees, academic, security
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false); // Whether setting is visible to parents/students
            $table->timestamps();
            
            $table->unique(['school_id', 'key']);
            $table->index(['school_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_settings');
    }
};
