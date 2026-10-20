<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_received_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_warehouse_id')->constrained('inventory_warehouses')->restrictOnDelete();
            $table->string('voucher_number', 50);
            $table->date('received_date');
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('posted_at');
            $table->timestamps();

            $table->unique(['school_id', 'voucher_number']);
            $table->index(['school_id', 'purchase_order_id', 'received_date']);
        });

        Schema::create('goods_received_voucher_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_received_voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->decimal('quantity_received', 14, 4);
            $table->decimal('unit_cost', 14, 4);
            $table->decimal('line_total', 14, 2);
            $table->timestamps();

            $table->unique(
                ['goods_received_voucher_id', 'purchase_order_item_id'],
                'grv_po_item_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_received_voucher_items');
        Schema::dropIfExists('goods_received_vouchers');
    }
};
