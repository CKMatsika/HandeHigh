<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add delivery note (D/Note) tracking to goods received vouchers
        if (Schema::hasTable('goods_received_vouchers')) {
            Schema::table('goods_received_vouchers', function (Blueprint $table) {
                if (! Schema::hasColumn('goods_received_vouchers', 'delivery_note_number')) {
                    $table->string('delivery_note_number', 100)->nullable()->after('voucher_number');
                }
                if (! Schema::hasColumn('goods_received_vouchers', 'delivery_note_date')) {
                    $table->date('delivery_note_date')->nullable()->after('delivery_note_number');
                }
                if (! Schema::hasColumn('goods_received_vouchers', 'carrier_name')) {
                    $table->string('carrier_name', 150)->nullable()->after('delivery_note_date');
                }
            });
        }

        // 2. Procurement RFQs (Request for Quotations)
        if (! Schema::hasTable('procurement_rfqs')) {
            Schema::create('procurement_rfqs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('procurement_requisition_id')->nullable()->constrained('procurement_requisitions')->nullOnDelete();
                $table->string('rfq_number', 50);
                $table->string('title', 255);
                $table->date('submission_deadline');
                $table->text('terms_and_conditions')->nullable();
                $table->string('status', 30)->default('draft'); // draft, published, closed, awarded, cancelled
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();

                $table->unique(['school_id', 'rfq_number']);
                $table->index(['school_id', 'status', 'submission_deadline']);
            });
        }

        // 3. RFQ Items
        if (! Schema::hasTable('procurement_rfq_items')) {
            Schema::create('procurement_rfq_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('procurement_rfq_id')->constrained('procurement_rfqs', 'id', 'rfqi_rfq_id_fk')->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
                $table->decimal('quantity', 14, 4);
                $table->text('specifications')->nullable();
                $table->timestamps();
            });
        }

        // 4. RFQ Invited Vendors
        if (! Schema::hasTable('procurement_rfq_vendors')) {
            Schema::create('procurement_rfq_vendors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('procurement_rfq_id')->constrained('procurement_rfqs', 'id', 'rfqv_rfq_id_fk')->cascadeOnDelete();
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
                $table->timestamp('invited_at')->nullable();
                $table->timestamps();

                $table->unique(['procurement_rfq_id', 'vendor_id'], 'rfq_vendor_unique');
            });
        }

        // 5. Vendor Quotations
        if (! Schema::hasTable('procurement_quotations')) {
            Schema::create('procurement_quotations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('procurement_rfq_id')->constrained('procurement_rfqs', 'id', 'pq_rfq_id_fk')->cascadeOnDelete();
                $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
                $table->string('quotation_number', 50);
                $table->date('quotation_date');
                $table->date('valid_until')->nullable();
                $table->integer('lead_time_days')->default(0);
                $table->string('payment_terms', 100)->nullable();
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->string('status', 30)->default('received'); // received, under_review, selected, rejected
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamps();

                $table->unique(['school_id', 'procurement_rfq_id', 'vendor_id'], 'rfq_vendor_quote_unique');
                $table->index(['school_id', 'status']);
            });
        }

        // 6. Quotation Items
        if (! Schema::hasTable('procurement_quotation_items')) {
            Schema::create('procurement_quotation_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('procurement_quotation_id')->constrained('procurement_quotations', 'id', 'pqi_quote_id_fk')->cascadeOnDelete();
                $table->foreignId('procurement_rfq_item_id')->constrained('procurement_rfq_items', 'id', 'pqi_rfqi_id_fk')->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
                $table->decimal('quantity', 14, 4);
                $table->decimal('unit_price', 14, 4);
                $table->decimal('total_price', 14, 2);
                $table->string('remarks', 255)->nullable();
                $table->timestamps();
            });
        }

        // 7. Quotation Comparison Reports & Selection
        if (! Schema::hasTable('procurement_comparisons')) {
            Schema::create('procurement_comparisons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('procurement_rfq_id')->constrained('procurement_rfqs', 'id', 'pc_rfq_id_fk')->cascadeOnDelete();
                $table->string('comparison_number', 50);
                $table->date('evaluation_date');
                $table->foreignId('winning_quotation_id')->nullable()->constrained('procurement_quotations', 'id', 'pc_win_quote_fk')->nullOnDelete();
                $table->foreignId('winning_vendor_id')->nullable()->constrained('vendors', 'id', 'pc_win_vendor_fk')->nullOnDelete();
                $table->text('selection_justification')->nullable();
                $table->foreignId('evaluated_by')->constrained('users')->restrictOnDelete();
                $table->string('status', 30)->default('draft'); // draft, submitted, approved, rejected
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
                $table->timestamps();

                $table->unique(['school_id', 'comparison_number']);
                $table->index(['school_id', 'status']);
            });
        }

        // 8. Purchase Returns (Goods Return to Vendor / Debit Note)
        if (! Schema::hasTable('purchase_returns')) {
            Schema::create('purchase_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->string('return_number', 50);
                $table->foreignId('goods_received_voucher_id')->constrained('goods_received_vouchers', 'id', 'pr_grv_fk')->restrictOnDelete();
                $table->foreignId('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete();
                $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
                $table->foreignId('inventory_warehouse_id')->constrained('inventory_warehouses')->restrictOnDelete();
                $table->date('return_date');
                $table->string('reason', 100); // damaged, defective, wrong_specification, excess_delivery, expired, other
                $table->text('reason_details')->nullable();
                $table->string('debit_note_number', 50)->nullable();
                $table->string('status', 30)->default('draft'); // draft, posted, cancelled
                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();

                $table->unique(['school_id', 'return_number']);
                $table->index(['school_id', 'status', 'return_date']);
            });
        }

        // 9. Purchase Return Items
        if (! Schema::hasTable('purchase_return_items')) {
            Schema::create('purchase_return_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_return_id')->constrained('purchase_returns', 'id', 'pri_ret_id_fk')->cascadeOnDelete();
                $table->foreignId('goods_received_voucher_item_id')->constrained('goods_received_voucher_items', 'id', 'pri_grvi_fk')->restrictOnDelete();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
                $table->decimal('quantity_returned', 14, 4);
                $table->decimal('unit_cost', 14, 4);
                $table->decimal('total_cost', 14, 2);
                $table->string('remarks', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('procurement_comparisons');
        Schema::dropIfExists('procurement_quotation_items');
        Schema::dropIfExists('procurement_quotations');
        Schema::dropIfExists('procurement_rfq_vendors');
        Schema::dropIfExists('procurement_rfq_items');
        Schema::dropIfExists('procurement_rfqs');

        if (Schema::hasTable('goods_received_vouchers')) {
            Schema::table('goods_received_vouchers', function (Blueprint $table) {
                $table->dropColumn(['delivery_note_number', 'delivery_note_date', 'carrier_name']);
            });
        }
    }
};
