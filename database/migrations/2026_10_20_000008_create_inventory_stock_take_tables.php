<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_stock_takes')) {
            Schema::create('inventory_stock_takes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('inventory_warehouse_id')->constrained('inventory_warehouses')->restrictOnDelete();
                $table->string('stock_take_number', 50);
                $table->string('status', 30)->default('pending_review');
                $table->foreignId('taken_by')->constrained('users')->restrictOnDelete();
                $table->date('stock_take_date');
                $table->text('notes')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();

                $table->unique(['school_id', 'stock_take_number']);
                $table->index(['school_id', 'status', 'submitted_at']);
            });
        }

        if (! Schema::hasTable('inventory_stock_take_items')) {
            Schema::create('inventory_stock_take_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_stock_take_id')->constrained()->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
                $table->decimal('system_quantity', 14, 4);
                $table->decimal('system_average_unit_cost', 14, 4);
                $table->decimal('counted_quantity', 14, 4);
                $table->timestamps();

                $table->unique(['inventory_stock_take_id', 'inventory_item_id'], 'stock_take_item_unique');
            });
        }

        if (! Schema::hasTable('inventory_stock_take_reviews')) {
            Schema::create('inventory_stock_take_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_stock_take_id')->constrained()->cascadeOnDelete();
                $table->foreignId('reviewed_by')->constrained('users')->restrictOnDelete();
                $table->string('decision', 30);
                $table->text('comments')->nullable();
                $table->timestamp('reviewed_at');
                $table->timestamps();

                $table->index(['inventory_stock_take_id', 'reviewed_at'], 'stock_take_review_date_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_take_reviews');
        Schema::dropIfExists('inventory_stock_take_items');
        Schema::dropIfExists('inventory_stock_takes');
    }
};
