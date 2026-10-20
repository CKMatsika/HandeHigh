<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_requisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('requisition_number', 50);
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('delivery_warehouse_id')->nullable()->constrained('inventory_warehouses')->nullOnDelete();
            $table->date('needed_by')->nullable();
            $table->text('purpose');
            $table->string('status', 30)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'requisition_number']);
            $table->index(['school_id', 'status', 'created_at']);
            $table->index(['school_id', 'requested_by', 'status']);
        });

        Schema::create('procurement_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_requisition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['procurement_requisition_id', 'inventory_item_id'], 'proc_req_item_unique');
        });

        Schema::create('procurement_requisition_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_requisition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewed_by')->constrained('users')->restrictOnDelete();
            $table->string('decision', 30);
            $table->text('comments')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();

            $table->index(['procurement_requisition_id', 'reviewed_at'], 'proc_req_review_date_idx');
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('procurement_requisition_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->string('purchase_order_number', 50);
            $table->string('status', 30)->default('draft');
            $table->date('order_date')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'purchase_order_number']);
            $table->index(['school_id', 'status', 'created_at']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('procurement_requisition_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->decimal('quantity_ordered', 14, 4);
            $table->decimal('unit_cost', 14, 4);
            $table->decimal('line_total', 14, 2);
            $table->timestamps();

            $table->unique(['purchase_order_id', 'procurement_requisition_item_id'], 'po_req_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('procurement_requisition_reviews');
        Schema::dropIfExists('procurement_requisition_items');
        Schema::dropIfExists('procurement_requisitions');
    }
};
