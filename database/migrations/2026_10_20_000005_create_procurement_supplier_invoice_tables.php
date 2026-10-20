<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('goods_received_voucher_id')->constrained()->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->foreignId('bill_id')->nullable()->unique()->constrained('bills')->nullOnDelete();
            $table->string('invoice_number', 100);
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('status', 30)->default('pending_review');
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'vendor_id', 'invoice_number'], 'proc_supplier_invoice_number_unique');
            $table->index(['school_id', 'status', 'invoice_date']);
        });

        Schema::create('procurement_supplier_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_supplier_invoice_id')
                ->constrained('procurement_supplier_invoices')
                ->cascadeOnDelete();
            $table->foreignId('goods_received_voucher_item_id')
                ->constrained('goods_received_voucher_items')
                ->restrictOnDelete();
            $table->decimal('quantity_invoiced', 14, 4);
            $table->decimal('unit_price', 14, 4);
            $table->decimal('line_total', 14, 2);
            $table->decimal('stock_cost_basis', 14, 2);
            $table->timestamps();

            $table->unique(
                ['procurement_supplier_invoice_id', 'goods_received_voucher_item_id'],
                'proc_supplier_invoice_grv_item_unique'
            );
        });

        Schema::create('procurement_supplier_invoice_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_supplier_invoice_id')
                ->constrained('procurement_supplier_invoices')
                ->cascadeOnDelete();
            $table->foreignId('reviewed_by')->constrained('users')->restrictOnDelete();
            $table->string('decision', 30);
            $table->text('comments')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();

            $table->index(
                ['procurement_supplier_invoice_id', 'reviewed_at'],
                'proc_supplier_invoice_review_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_supplier_invoice_reviews');
        Schema::dropIfExists('procurement_supplier_invoice_items');
        Schema::dropIfExists('procurement_supplier_invoices');
    }
};
