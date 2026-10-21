<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('school_fixed_assets') && ! Schema::hasColumn('school_fixed_assets', 'depreciation_method')) {
            Schema::table('school_fixed_assets', function (Blueprint $table) {
                $table->string('depreciation_method', 50)->default('straight_line')->after('capitalized_cost');
                $table->decimal('useful_life_years', 5, 2)->default(5.00)->after('depreciation_method');
                $table->decimal('salvage_value', 14, 2)->default(0.00)->after('useful_life_years');
                $table->decimal('depreciation_rate', 5, 2)->nullable()->after('salvage_value');
                $table->decimal('accumulated_depreciation', 14, 2)->default(0.00)->after('depreciation_rate');
                $table->decimal('book_value', 14, 2)->default(0.00)->after('accumulated_depreciation');
                $table->date('last_depreciation_date')->nullable()->after('book_value');
                $table->foreignId('accumulated_depreciation_account_id')->nullable()->after('asset_account_id')->constrained('accounts')->nullOnDelete();
                $table->foreignId('depreciation_expense_account_id')->nullable()->after('accumulated_depreciation_account_id')->constrained('accounts')->nullOnDelete();
                $table->date('disposal_date')->nullable()->after('location');
                $table->string('disposal_type', 50)->nullable()->after('disposal_date');
                $table->decimal('disposal_proceeds', 14, 2)->default(0.00)->after('disposal_type');
                $table->text('disposal_notes')->nullable()->after('disposal_proceeds');
                $table->foreignId('disposal_journal_batch_id')->nullable()->after('disposal_notes')->constrained('journal_batches')->nullOnDelete();
                $table->foreignId('disposed_by')->nullable()->after('disposal_journal_batch_id')->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('school_fixed_asset_depreciations')) {
            Schema::create('school_fixed_asset_depreciations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('school_fixed_asset_id')
                    ->constrained('school_fixed_assets', 'id', 'sfad_asset_id_fk')
                    ->cascadeOnDelete();
                $table->foreignId('journal_batch_id')->nullable()->constrained('journal_batches')->nullOnDelete();
                $table->date('depreciation_date');
                $table->string('period_name', 100);
                $table->string('method', 50)->default('straight_line');
                $table->decimal('depreciation_amount', 14, 2);
                $table->decimal('book_value_before', 14, 2);
                $table->decimal('book_value_after', 14, 2);
                $table->decimal('accumulated_depreciation_after', 14, 2);
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['school_id', 'depreciation_date'], 'sfad_school_date_idx');
                $table->index(['school_fixed_asset_id', 'depreciation_date'], 'asset_deprec_date_idx');
            });
        }

        if (! Schema::hasTable('school_fixed_asset_maintenances')) {
            Schema::create('school_fixed_asset_maintenances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->cascadeOnDelete();
                $table->foreignId('school_fixed_asset_id')
                    ->constrained('school_fixed_assets', 'id', 'sfam_asset_id_fk')
                    ->cascadeOnDelete();
                $table->foreignId('journal_batch_id')->nullable()->constrained('journal_batches')->nullOnDelete();
                $table->string('title');
                $table->string('maintenance_type', 50)->default('preventive');
                $table->string('priority', 30)->default('routine');
                $table->string('status', 30)->default('scheduled');
                $table->string('service_provider')->nullable();
                $table->decimal('cost', 14, 2)->default(0.00);
                $table->date('scheduled_date')->nullable();
                $table->date('completed_date')->nullable();
                $table->date('next_service_due')->nullable();
                $table->text('description')->nullable();
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('expense_account_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->timestamps();

                $table->index(['school_id', 'status']);
                $table->index(['school_fixed_asset_id', 'status'], 'sfam_asset_status_idx');
                $table->index(['school_id', 'next_service_due']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_fixed_asset_maintenances');
        Schema::dropIfExists('school_fixed_asset_depreciations');

        if (Schema::hasTable('school_fixed_assets') && Schema::hasColumn('school_fixed_assets', 'disposed_by')) {
            Schema::table('school_fixed_assets', function (Blueprint $table) {
                $table->dropConstrainedForeignId('disposed_by');
            });
        }
        if (Schema::hasTable('school_fixed_assets') && Schema::hasColumn('school_fixed_assets', 'disposal_journal_batch_id')) {
            Schema::table('school_fixed_assets', function (Blueprint $table) {
                $table->dropConstrainedForeignId('disposal_journal_batch_id');
            });
        }
        if (Schema::hasTable('school_fixed_assets') && Schema::hasColumn('school_fixed_assets', 'disposal_notes')) {
            Schema::table('school_fixed_assets', function (Blueprint $table) {
                $table->dropColumn(['disposal_notes', 'disposal_proceeds', 'disposal_type', 'disposal_date']);
            });
        }
        if (Schema::hasTable('school_fixed_assets') && Schema::hasColumn('school_fixed_assets', 'depreciation_expense_account_id')) {
            Schema::table('school_fixed_assets', function (Blueprint $table) {
                $table->dropConstrainedForeignId('depreciation_expense_account_id');
            });
        }
        if (Schema::hasTable('school_fixed_assets') && Schema::hasColumn('school_fixed_assets', 'accumulated_depreciation_account_id')) {
            Schema::table('school_fixed_assets', function (Blueprint $table) {
                $table->dropConstrainedForeignId('accumulated_depreciation_account_id');
            });
        }
        if (Schema::hasTable('school_fixed_assets') && Schema::hasColumn('school_fixed_assets', 'last_depreciation_date')) {
            Schema::table('school_fixed_assets', function (Blueprint $table) {
                $table->dropColumn([
                    'last_depreciation_date',
                    'book_value',
                    'accumulated_depreciation',
                    'depreciation_rate',
                    'salvage_value',
                    'useful_life_years',
                    'depreciation_method',
                ]);
            });
        }
    }
};
