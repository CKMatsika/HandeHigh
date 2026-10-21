<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_categories') && ! Schema::hasColumn('inventory_categories', 'asset_account_id')) {
            Schema::table('inventory_categories', function (Blueprint $table) {
                $table->foreignId('asset_account_id')
                    ->nullable()
                    ->after('issue_expense_account_id')
                    ->constrained('accounts')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('school_fixed_assets')) {
            Schema::create('school_fixed_assets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
                $table->foreignId('goods_received_voucher_item_id')->constrained('goods_received_voucher_items')->restrictOnDelete();
                $table->foreignId('supplier_invoice_item_id')->nullable()
                    ->constrained('procurement_supplier_invoice_items')->nullOnDelete();
                $table->foreignId('inventory_warehouse_id')->nullable()
                    ->constrained('inventory_warehouses')->nullOnDelete();
                $table->foreignId('asset_account_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->foreignId('custodian_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('asset_number', 50);
                $table->string('serial_number', 100)->nullable();
                $table->string('status', 30)->default('in_store');
                $table->boolean('is_capitalized')->default(false);
                $table->decimal('purchase_cost', 14, 2)->default(0);
                $table->decimal('capitalized_cost', 14, 2)->default(0);
                $table->date('acquired_date');
                $table->string('location')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['school_id', 'asset_number']);
                $table->unique(['school_id', 'serial_number']);
                $table->index(['school_id', 'status', 'is_capitalized']);
                $table->index(['school_id', 'inventory_item_id', 'status']);
                $table->index('goods_received_voucher_item_id');
            });
        }

        if (! Schema::hasTable('school_fixed_asset_assignments')) {
            Schema::create('school_fixed_asset_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_fixed_asset_id')->constrained()->cascadeOnDelete();
                $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
                $table->string('action', 30);
                $table->string('from_location')->nullable();
                $table->string('to_location')->nullable();
                $table->decimal('unit_cost', 14, 4)->default(0);
                $table->text('notes')->nullable();
                $table->timestamp('occurred_at');
                $table->timestamps();

                $table->index(['school_fixed_asset_id', 'occurred_at'], 'fixed_asset_assignment_date_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_fixed_asset_assignments');
        Schema::dropIfExists('school_fixed_assets');

        if (Schema::hasTable('inventory_categories') && Schema::hasColumn('inventory_categories', 'asset_account_id')) {
            Schema::table('inventory_categories', function (Blueprint $table) {
                $table->dropConstrainedForeignId('asset_account_id');
            });
        }
    }
};
