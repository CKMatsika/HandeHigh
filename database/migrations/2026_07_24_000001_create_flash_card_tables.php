<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flash_card_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('school_class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->string('source_type')->default('manual');
            $table->timestamps();
        });

        Schema::create('flash_card_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_card_set_id')->constrained()->cascadeOnDelete();
            $table->text('front_text');
            $table->text('back_text');
            $table->string('hint')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('flash_card_study_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flash_card_set_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('cards_studied')->default(0);
            $table->integer('cards_confident')->default(0);
            $table->timestamps();
        });

        Schema::create('flash_card_item_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_session_id')->constrained('flash_card_study_sessions')->cascadeOnDelete();
            $table->foreignId('flash_card_item_id')->constrained()->cascadeOnDelete();
            $table->string('confidence'); // know, unsure, dont_know
            $table->timestamp('reviewed_at');
            $table->timestamps();
            $table->unique(['study_session_id', 'flash_card_item_id'], 'fc_results_session_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_card_item_results');
        Schema::dropIfExists('flash_card_study_sessions');
        Schema::dropIfExists('flash_card_items');
        Schema::dropIfExists('flash_card_sets');
    }
};
