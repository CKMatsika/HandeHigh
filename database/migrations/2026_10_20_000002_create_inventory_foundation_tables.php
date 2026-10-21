<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_settings')) {
            Schema::create('inventory_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('valuation_method')->default('weighted_average');
                $table->decimal('asset_capitalization_threshold', 14, 2)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('inventory_categories')) {
            Schema::create('inventory_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->string('code', 50);
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['school_id', 'code']);
                $table->index(['school_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('inventory_items')) {
            Schema::create('inventory_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('inventory_category_id')->constrained('inventory_categories')->restrictOnDelete();
                $table->string('code', 50);
                $table->string('name');
                $table->string('item_type', 30)->default('consumable');
                $table->string('unit_of_measure', 30);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['school_id', 'code']);
                $table->index(['school_id', 'inventory_category_id', 'is_active']);
                $table->index(['school_id', 'item_type']);
            });
        }

        if (! Schema::hasTable('inventory_warehouses')) {
            Schema::create('inventory_warehouses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->string('code', 50);
                $table->string('name');
                $table->string('location')->nullable();
                $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['school_id', 'code']);
                $table->index(['school_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('inventory_balances')) {
            Schema::create('inventory_balances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
                $table->foreignId('inventory_warehouse_id')->constrained('inventory_warehouses')->restrictOnDelete();
                $table->decimal('quantity_on_hand', 14, 4)->default(0);
                $table->decimal('average_unit_cost', 14, 4)->default(0);
                $table->decimal('reorder_level', 14, 4)->default(0);
                $table->decimal('reorder_quantity', 14, 4)->nullable();
                $table->timestamps();

                $table->unique(['inventory_item_id', 'inventory_warehouse_id'], 'inv_bal_item_wh_unique');
                $table->index(['school_id', 'inventory_warehouse_id']);
            });
        }

        if (! Schema::hasTable('inventory_transactions')) {
            Schema::create('inventory_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
                $table->foreignId('inventory_warehouse_id')->constrained('inventory_warehouses')->restrictOnDelete();
                $table->string('movement_type', 30);
                $table->decimal('quantity_delta', 14, 4);
                $table->decimal('unit_cost', 14, 4)->default(0);
                $table->decimal('quantity_after', 14, 4);
                $table->decimal('average_unit_cost_after', 14, 4)->default(0);
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->uuid('transaction_group_id')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('occurred_at');
                $table->timestamps();

                $table->index(['school_id', 'inventory_item_id', 'occurred_at'], 'inventory_transactions_item_date_idx');
                $table->index(['school_id', 'inventory_warehouse_id', 'occurred_at'], 'inventory_transactions_warehouse_date_idx');
                $table->index(['reference_type', 'reference_id']);
                $table->index('transaction_group_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventory_balances');
        Schema::dropIfExists('inventory_warehouses');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_categories');
        Schema::dropIfExists('inventory_settings');
    }
};
