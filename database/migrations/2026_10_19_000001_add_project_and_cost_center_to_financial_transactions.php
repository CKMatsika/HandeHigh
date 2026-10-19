<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Fee Structures
        if (Schema::hasTable('fee_structures')) {
            Schema::table('fee_structures', function (Blueprint $table) {
                if (!Schema::hasColumn('fee_structures', 'project_id')) {
                    $table->foreignId('project_id')->nullable()->after('revenue_account_id')->constrained('projects')->nullOnDelete();
                }
                if (!Schema::hasColumn('fee_structures', 'cost_center_id')) {
                    $table->foreignId('cost_center_id')->nullable()->after('project_id')->constrained('departments')->nullOnDelete();
                }
            });
        }

        // 2. Invoices
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('invoices', 'project_id')) {
                    $table->foreignId('project_id')->nullable()->after('school_id')->constrained('projects')->nullOnDelete();
                }
                if (!Schema::hasColumn('invoices', 'cost_center_id')) {
                    $table->foreignId('cost_center_id')->nullable()->after('project_id')->constrained('departments')->nullOnDelete();
                }
            });
        }

        // 3. Invoice Items
        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                if (!Schema::hasColumn('invoice_items', 'project_id')) {
                    $table->foreignId('project_id')->nullable()->after('fee_structure_id')->constrained('projects')->nullOnDelete();
                }
                if (!Schema::hasColumn('invoice_items', 'cost_center_id')) {
                    $table->foreignId('cost_center_id')->nullable()->after('project_id')->constrained('departments')->nullOnDelete();
                }
            });
        }

        // 4. Bills
        if (Schema::hasTable('bills')) {
            Schema::table('bills', function (Blueprint $table) {
                if (!Schema::hasColumn('bills', 'project_id')) {
                    $table->foreignId('project_id')->nullable()->after('expense_account_id')->constrained('projects')->nullOnDelete();
                }
                if (!Schema::hasColumn('bills', 'cost_center_id')) {
                    $table->foreignId('cost_center_id')->nullable()->after('project_id')->constrained('departments')->nullOnDelete();
                }
            });
        }

        // 5. Bill Items
        if (Schema::hasTable('bill_items')) {
            Schema::table('bill_items', function (Blueprint $table) {
                if (!Schema::hasColumn('bill_items', 'project_id')) {
                    $table->foreignId('project_id')->nullable()->after('expense_account_id')->constrained('projects')->nullOnDelete();
                }
                if (!Schema::hasColumn('bill_items', 'cost_center_id')) {
                    $table->foreignId('cost_center_id')->nullable()->after('project_id')->constrained('departments')->nullOnDelete();
                }
            });
        }

        // 6. Receipts
        if (Schema::hasTable('receipts')) {
            Schema::table('receipts', function (Blueprint $table) {
                if (!Schema::hasColumn('receipts', 'project_id')) {
                    $table->foreignId('project_id')->nullable()->after('bank_account_id')->constrained('projects')->nullOnDelete();
                }
                if (!Schema::hasColumn('receipts', 'cost_center_id')) {
                    $table->foreignId('cost_center_id')->nullable()->after('project_id')->constrained('departments')->nullOnDelete();
                }
            });
        }

        // 7. Receipt Items
        if (Schema::hasTable('receipt_items')) {
            Schema::table('receipt_items', function (Blueprint $table) {
                if (!Schema::hasColumn('receipt_items', 'project_id')) {
                    $table->foreignId('project_id')->nullable()->after('category')->constrained('projects')->nullOnDelete();
                }
                if (!Schema::hasColumn('receipt_items', 'cost_center_id')) {
                    $table->foreignId('cost_center_id')->nullable()->after('project_id')->constrained('departments')->nullOnDelete();
                }
            });
        }

        // 8. Cashbook
        if (Schema::hasTable('cashbook')) {
            Schema::table('cashbook', function (Blueprint $table) {
                if (!Schema::hasColumn('cashbook', 'project_id')) {
                    $table->foreignId('project_id')->nullable()->after('account_id')->constrained('projects')->nullOnDelete();
                }
                if (!Schema::hasColumn('cashbook', 'cost_center_id')) {
                    $table->foreignId('cost_center_id')->nullable()->after('project_id')->constrained('departments')->nullOnDelete();
                }
            });
        }

        // 9. Kiosk Sales
        if (Schema::hasTable('kiosk_sales')) {
            Schema::table('kiosk_sales', function (Blueprint $table) {
                if (!Schema::hasColumn('kiosk_sales', 'project_id')) {
                    $table->foreignId('project_id')->nullable()->after('bank_account_id')->constrained('projects')->nullOnDelete();
                }
                if (!Schema::hasColumn('kiosk_sales', 'cost_center_id')) {
                    $table->foreignId('cost_center_id')->nullable()->after('project_id')->constrained('departments')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'kiosk_sales' => ['project_id', 'cost_center_id'],
            'cashbook' => ['project_id', 'cost_center_id'],
            'receipt_items' => ['project_id', 'cost_center_id'],
            'receipts' => ['project_id', 'cost_center_id'],
            'bill_items' => ['project_id', 'cost_center_id'],
            'bills' => ['project_id', 'cost_center_id'],
            'invoice_items' => ['project_id', 'cost_center_id'],
            'invoices' => ['project_id', 'cost_center_id'],
            'fee_structures' => ['project_id', 'cost_center_id'],
        ];

        foreach ($tables as $table => $columns) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $blueprint) use ($columns, $table) {
                    foreach ($columns as $column) {
                        if (Schema::hasColumn($table, $column)) {
                            $blueprint->dropConstrainedForeignId($column);
                        }
                    }
                });
            }
        }
    }
};
