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
        Schema::createIfNotExists('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', ['classroom', 'lab', 'library', 'hall', 'office', 'sports', 'other']);
            $table->integer('capacity');
            $table->text('description')->nullable();
            $table->json('equipment')->nullable(); // Available equipment
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['school_id', 'type']);
            $table->unique(['school_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
