<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createIfNotExists('school_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('asset_code')->nullable();
            $table->string('category')->comment('textbook, uniform, device, stationery, other');
            $table->text('description')->nullable();
            $table->integer('total_quantity')->default(0);
            $table->integer('available_quantity')->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'asset_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_assets');
    }
};
