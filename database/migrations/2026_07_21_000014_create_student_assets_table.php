<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createIfNotExists('student_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_asset_id')->constrained('school_assets')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->date('allocated_date');
            $table->date('returned_date')->nullable();
            $table->string('condition_at_issue')->nullable()->comment('new, good, fair, poor');
            $table->string('condition_at_return')->nullable()->comment('new, good, fair, poor, damaged, lost');
            $table->decimal('replacement_cost', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('allocated')->comment('allocated, returned, lost, damaged');
            $table->timestamps();

            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_assets');
    }
};
