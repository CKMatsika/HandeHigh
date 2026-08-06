<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dormitory_id')->constrained()->cascadeOnDelete();
            $table->string('bed_number');
            $table->string('description')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unique(['dormitory_id', 'bed_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beds');
    }
};
