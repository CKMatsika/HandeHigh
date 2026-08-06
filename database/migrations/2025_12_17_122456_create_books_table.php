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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('author');
            $table->string('isbn')->unique();
            $table->string('category');
            $table->string('publisher')->nullable();
            $table->integer('publication_year')->nullable();
            $table->integer('total_copies');
            $table->integer('available_copies');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['school_id', 'title']);
            $table->index(['school_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
