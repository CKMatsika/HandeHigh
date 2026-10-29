<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kiosk_till_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('till_label', 80);
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('open');
            $table->string('active_key', 20)->nullable();
            $table->decimal('opening_float', 12, 2)->default(0);
            $table->decimal('cash_sales', 12, 2)->default(0);
            $table->decimal('mobile_money_sales', 12, 2)->default(0);
            $table->decimal('bank_transfer_sales', 12, 2)->default(0);
            $table->decimal('card_sales', 12, 2)->default(0);
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->unsignedInteger('sales_count')->default(0);
            $table->decimal('expected_cash', 12, 2)->nullable();
            $table->decimal('counted_cash', 12, 2)->nullable();
            $table->decimal('cash_variance', 12, 2)->nullable();
            $table->string('z_reading_number', 50)->nullable();
            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'cashier_id', 'active_key'], 'kiosk_open_till_per_cashier_unique');
            $table->unique(['school_id', 'z_reading_number'], 'kiosk_z_reading_number_unique');
            $table->index(['school_id', 'status', 'opened_at']);
            $table->index(['school_id', 'cashier_id', 'closed_at']);
        });

        Schema::create('kiosk_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kiosk_product_id')->constrained('kiosk_products')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('movement_type', 40);
            $table->decimal('quantity_delta', 14, 4);
            $table->decimal('quantity_before', 14, 4);
            $table->decimal('quantity_after', 14, 4);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'kiosk_product_id', 'created_at'], 'kiosk_stock_product_date_idx');
            $table->index(['reference_type', 'reference_id'], 'kiosk_stock_reference_idx');
        });

        Schema::create('kiosk_stock_takes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
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

        Schema::create('kiosk_stock_take_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kiosk_stock_take_id')->constrained('kiosk_stock_takes')->cascadeOnDelete();
            $table->foreignId('kiosk_product_id')->constrained('kiosk_products')->restrictOnDelete();
            $table->decimal('system_quantity', 14, 4);
            $table->decimal('counted_quantity', 14, 4);
            $table->timestamps();

            $table->unique(['kiosk_stock_take_id', 'kiosk_product_id'], 'kiosk_stock_take_item_unique');
        });

        Schema::create('kiosk_stock_take_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kiosk_stock_take_id')->constrained('kiosk_stock_takes')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->constrained('users')->restrictOnDelete();
            $table->string('decision', 30);
            $table->text('comments')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();
            $table->index(['kiosk_stock_take_id', 'reviewed_at'], 'kiosk_stock_review_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kiosk_stock_take_reviews');
        Schema::dropIfExists('kiosk_stock_take_items');
        Schema::dropIfExists('kiosk_stock_takes');
        Schema::dropIfExists('kiosk_stock_movements');
        Schema::dropIfExists('kiosk_till_sessions');
    }
};
