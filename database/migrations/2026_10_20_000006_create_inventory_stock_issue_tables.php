<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_categories') && ! Schema::hasColumn('inventory_categories', 'issue_expense_account_id')) {
            Schema::table('inventory_categories', function (Blueprint $table) {
                $table->foreignId('issue_expense_account_id')
                    ->nullable()
                    ->after('description')
                    ->constrained('accounts')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('inventory_stock_requests')) {
            Schema::create('inventory_stock_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->string('request_number', 50);
                $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->date('needed_by')->nullable();
                $table->text('purpose');
                $table->string('status', 30)->default('pending_review');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();

                $table->unique(['school_id', 'request_number']);
                $table->index(['school_id', 'status', 'submitted_at']);
            });
        }

        if (! Schema::hasTable('inventory_stock_request_items')) {
            Schema::create('inventory_stock_request_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_stock_request_id')
                    ->constrained('inventory_stock_requests', 'id', 'isri_request_id_fk')
                    ->cascadeOnDelete();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
                $table->decimal('quantity_requested', 14, 4);
                $table->decimal('quantity_issued', 14, 4)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['inventory_stock_request_id', 'inventory_item_id'], 'isri_request_item_unique');
            });
        }

        if (! Schema::hasTable('inventory_stock_issues')) {
            Schema::create('inventory_stock_issues', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('inventory_stock_request_id')->constrained()->restrictOnDelete();
                $table->foreignId('inventory_warehouse_id')->constrained('inventory_warehouses')->restrictOnDelete();
                $table->string('issue_number', 50);
                $table->date('issued_date');
                $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
                $table->text('notes')->nullable();
                $table->timestamp('posted_at');
                $table->timestamps();

                $table->unique(['school_id', 'issue_number']);
                $table->index(['school_id', 'inventory_stock_request_id', 'issued_date'], 'isi_school_req_date_idx');
            });
        }

        if (! Schema::hasTable('inventory_stock_issue_items')) {
            Schema::create('inventory_stock_issue_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_stock_issue_id')->constrained()->cascadeOnDelete();
                $table->foreignId('inventory_stock_request_item_id')
                    ->constrained('inventory_stock_request_items', 'id', 'isii_request_item_id_fk')
                    ->restrictOnDelete();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
                $table->decimal('quantity_issued', 14, 4);
                $table->decimal('unit_cost', 14, 4);
                $table->decimal('line_total', 14, 2);
                $table->timestamps();

                $table->unique(['inventory_stock_issue_id', 'inventory_stock_request_item_id'], 'stock_issue_request_item_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_issue_items');
        Schema::dropIfExists('inventory_stock_issues');
        Schema::dropIfExists('inventory_stock_request_items');
        Schema::dropIfExists('inventory_stock_requests');

        if (Schema::hasTable('inventory_categories') && Schema::hasColumn('inventory_categories', 'issue_expense_account_id')) {
            Schema::table('inventory_categories', function (Blueprint $table) {
                $table->dropConstrainedForeignId('issue_expense_account_id');
            });
        }
    }
};
