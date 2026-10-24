<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Additive, non-destructive migration to stabilise financial postings,
     * establish consistent bank account mappings, and support safe reversals.
     */
    public function up(): void
    {
        // 1. Bank Accounts: add account_id linking to GL chart of accounts
        if (Schema::hasTable('bank_accounts')) {
            Schema::table('bank_accounts', function (Blueprint $table) {
                if (!Schema::hasColumn('bank_accounts', 'account_id')) {
                    $table->unsignedBigInteger('account_id')->nullable()->after('school_id')->index();
                }
            });
        }

        // 2. Bill Payments: add account_id and journal_batch_id
        if (Schema::hasTable('bill_payments')) {
            Schema::table('bill_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('bill_payments', 'account_id')) {
                    $table->unsignedBigInteger('account_id')->nullable()->after('bank_account_id')->index();
                }
                if (!Schema::hasColumn('bill_payments', 'journal_batch_id')) {
                    $table->unsignedBigInteger('journal_batch_id')->nullable()->after('created_by')->index();
                }
            });
        }

        // 3. Cashbook: add status, reversal fields, and voucher_number
        if (Schema::hasTable('cashbook')) {
            Schema::table('cashbook', function (Blueprint $table) {
                if (!Schema::hasColumn('cashbook', 'status')) {
                    $table->string('status', 30)->default('active')->after('payment_method')->index();
                }
                if (!Schema::hasColumn('cashbook', 'voucher_number')) {
                    $table->string('voucher_number', 100)->nullable()->after('reference_number')->index();
                }
                if (!Schema::hasColumn('cashbook', 'reversal_batch_id')) {
                    $table->unsignedBigInteger('reversal_batch_id')->nullable()->after('notes')->index();
                }
                if (!Schema::hasColumn('cashbook', 'reversed_at')) {
                    $table->timestamp('reversed_at')->nullable()->after('reversal_batch_id');
                }
                if (!Schema::hasColumn('cashbook', 'reversed_by')) {
                    $table->unsignedBigInteger('reversed_by')->nullable()->after('reversed_at');
                }
            });
        }

        // 4. Cashbook Transactions: add reversal tracking
        if (Schema::hasTable('cashbook_transactions')) {
            Schema::table('cashbook_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('cashbook_transactions', 'reversal_entry_id')) {
                    $table->unsignedBigInteger('reversal_entry_id')->nullable()->after('journal_entry_id')->index();
                }
            });
        }

        // 5. Receipt Items: add account_id for item-level GL distribution
        if (Schema::hasTable('receipt_items')) {
            Schema::table('receipt_items', function (Blueprint $table) {
                if (!Schema::hasColumn('receipt_items', 'account_id')) {
                    $table->unsignedBigInteger('account_id')->nullable()->after('description')->index();
                }
            });
        }

        // 6. Safe backfill of bank_accounts.account_id from GL accounts
        $this->backfillBankAccountMappings();
    }

    /**
     * Backfill bank_accounts.account_id based on bank name or type
     */
    protected function backfillBankAccountMappings(): void
    {
        if (!Schema::hasTable('bank_accounts') || !Schema::hasTable('accounts')) {
            return;
        }

        $bankAccounts = DB::table('bank_accounts')->whereNull('account_id')->get();
        foreach ($bankAccounts as $bank) {
            $bankNameLower = strtolower($bank->bank_name ?? $bank->account_name ?? '');

            $targetCode = match(true) {
                str_contains($bankNameLower, 'afc') => '1321',
                str_contains($bankNameLower, 'cabs') => '1322',
                str_contains($bankNameLower, 'zb') => '1323',
                str_contains($bankNameLower, 'cash') || str_contains($bankNameLower, 'petty') => '1102',
                default => null,
            };

            $glAccount = $targetCode
                ? DB::table('accounts')
                    ->where('school_id', $bank->school_id)
                    ->where('code', $targetCode)
                    ->first()
                : null;

            if ($glAccount) {
                DB::table('bank_accounts')
                    ->where('id', $bank->id)
                    ->update(['account_id' => $glAccount->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bank_accounts') && Schema::hasColumn('bank_accounts', 'account_id')) {
            Schema::table('bank_accounts', function (Blueprint $table) {
                $table->dropColumn('account_id');
            });
        }

        if (Schema::hasTable('bill_payments')) {
            Schema::table('bill_payments', function (Blueprint $table) {
                if (Schema::hasColumn('bill_payments', 'account_id')) {
                    $table->dropColumn('account_id');
                }
                if (Schema::hasColumn('bill_payments', 'journal_batch_id')) {
                    $table->dropColumn('journal_batch_id');
                }
            });
        }

        if (Schema::hasTable('cashbook')) {
            Schema::table('cashbook', function (Blueprint $table) {
                $columns = ['status', 'voucher_number', 'reversal_batch_id', 'reversed_at', 'reversed_by'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('cashbook', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('cashbook_transactions') && Schema::hasColumn('cashbook_transactions', 'reversal_entry_id')) {
            Schema::table('cashbook_transactions', function (Blueprint $table) {
                $table->dropColumn('reversal_entry_id');
            });
        }

        if (Schema::hasTable('receipt_items') && Schema::hasColumn('receipt_items', 'account_id')) {
            Schema::table('receipt_items', function (Blueprint $table) {
                $table->dropColumn('account_id');
            });
        }
    }
};
