<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Additive, non-destructive migration supporting dynamic account mapping,
     * historical migration staging, and enhanced manual cashbook accounting.
     */
    public function up(): void
    {
        // 1. Dynamic Account Mappings Table
        if (!Schema::hasTable('account_mappings')) {
            Schema::create('account_mappings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->onDelete('cascade');
                $table->string('mapping_type', 50)->index(); // e.g., control_account, payment_method, fee_vote, expenditure_category, bank_account, fund
                $table->string('source_key', 100)->index();   // e.g., accounts_receivable, accounts_payable, TUITION, BUILDING_FUND, cash, ecocash
                $table->foreignId('account_id')->constrained('accounts')->onDelete('restrict');
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['school_id', 'mapping_type', 'source_key'], 'uniq_school_mapping_key');
            });
        }

        // 2. Enhance Cashbook Table for Manual Historical Accounting
        if (Schema::hasTable('cashbook')) {
            Schema::table('cashbook', function (Blueprint $table) {
                if (!Schema::hasColumn('cashbook', 'classification')) {
                    $table->string('classification', 50)->nullable()->after('transaction_type')->index();
                }
                if (!Schema::hasColumn('cashbook', 'student_id')) {
                    $table->unsignedBigInteger('student_id')->nullable()->after('related_payment_id')->index();
                }
                if (!Schema::hasColumn('cashbook', 'vendor_id')) {
                    $table->unsignedBigInteger('vendor_id')->nullable()->after('student_id')->index();
                }
                if (!Schema::hasColumn('cashbook', 'counterparty_name')) {
                    $table->string('counterparty_name')->nullable()->after('vendor_id');
                }
                if (!Schema::hasColumn('cashbook', 'vote_code')) {
                    $table->string('vote_code', 50)->nullable()->after('category')->index();
                }
                if (!Schema::hasColumn('cashbook', 'fund_code')) {
                    $table->string('fund_code', 50)->nullable()->after('vote_code')->index();
                }
                if (!Schema::hasColumn('cashbook', 'offset_account_id')) {
                    $table->unsignedBigInteger('offset_account_id')->nullable()->after('account_id')->index();
                }
                if (!Schema::hasColumn('cashbook', 'supporting_document_ref')) {
                    $table->string('supporting_document_ref')->nullable()->after('voucher_number');
                }
                if (!Schema::hasColumn('cashbook', 'journal_batch_id')) {
                    $table->unsignedBigInteger('journal_batch_id')->nullable()->after('reversal_batch_id')->index();
                }
                if (!Schema::hasColumn('cashbook', 'currency')) {
                    $table->string('currency', 10)->default('USD')->after('amount');
                }
            });
        }

        // 3. Historical Migration Batches Table
        if (!Schema::hasTable('migration_batches')) {
            Schema::create('migration_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained()->onDelete('cascade');
                $table->string('batch_number', 50)->unique();
                $table->string('import_type', 50)->index(); // opening_balances, historical_invoices, bank_cashbook, student_receipts, supplier_bills, journal_adjustments
                $table->enum('status', ['uploaded', 'validated', 'ready_for_approval', 'posted', 'partially_posted', 'failed', 'rolled_back'])->default('uploaded');
                $table->string('file_name');
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('valid_rows')->default(0);
                $table->unsignedInteger('error_rows')->default(0);
                $table->unsignedInteger('posted_rows')->default(0);
                $table->decimal('total_debit', 15, 2)->default(0);
                $table->decimal('total_credit', 15, 2)->default(0);
                $table->boolean('is_balanced')->default(true);
                $table->json('metadata')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();

                $table->index(['school_id', 'import_type', 'status']);
            });
        }

        // 4. Migration Staging Rows Table
        if (!Schema::hasTable('migration_staging_rows')) {
            Schema::create('migration_staging_rows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('migration_batch_id')->constrained('migration_batches')->onDelete('cascade');
                $table->foreignId('school_id')->constrained()->onDelete('cascade');
                $table->unsignedInteger('row_number');
                $table->date('accounting_date')->index();
                $table->string('reference_number', 100)->nullable();
                $table->string('description');
                $table->decimal('amount', 15, 2);
                $table->enum('direction', ['debit', 'credit']);
                $table->string('classification', 50)->nullable(); // invoice, receipt, expense, transfer, opening_balance
                $table->string('account_code', 50)->nullable();
                $table->string('vote_code', 50)->nullable();
                $table->string('student_admission_number', 100)->nullable();
                $table->string('vendor_name', 255)->nullable();
                $table->string('bank_identifier', 100)->nullable();
                $table->json('raw_data')->nullable();
                $table->json('parsed_data')->nullable();
                $table->string('matched_entity_type', 50)->nullable();
                $table->unsignedBigInteger('matched_entity_id')->nullable();
                $table->enum('status', ['pending', 'validated', 'unresolved', 'posted', 'failed', 'ignored'])->default('pending');
                $table->json('validation_errors')->nullable();
                $table->unsignedBigInteger('journal_batch_id')->nullable()->index();
                $table->string('idempotency_key', 64)->nullable()->index();
                $table->timestamps();

                $table->index(['migration_batch_id', 'status']);
                $table->index(['school_id', 'accounting_date']);
                $table->index(['school_id', 'idempotency_key']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('migration_staging_rows');
        Schema::dropIfExists('migration_batches');

        if (Schema::hasTable('cashbook')) {
            Schema::table('cashbook', function (Blueprint $table) {
                $columns = [
                    'classification', 'student_id', 'vendor_id', 'counterparty_name',
                    'vote_code', 'fund_code', 'offset_account_id', 'supporting_document_ref',
                    'journal_batch_id', 'currency'
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('cashbook', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('account_mappings');
    }
};
