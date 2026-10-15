<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Cashbook;
use App\Models\CashbookTransaction;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Safely and non-destructively backfills existing cashbook records into cashbook_transactions
     * so existing payments immediately become reconcilable in Bank Reconciliation.
     */
    public function up(): void
    {
        if (! class_exists(Cashbook::class) || ! class_exists(CashbookTransaction::class)) {
            return;
        }

        try {
            Cashbook::chunk(100, function ($records) {
                foreach ($records as $record) {
                    $ref = $record->reference_number ?? ('CB-' . $record->id);

                    $exists = CashbookTransaction::where('school_id', $record->school_id)
                        ->where('account_id', $record->account_id)
                        ->where('amount', $record->amount)
                        ->where('reference_number', $ref)
                        ->exists();

                    if (! $exists) {
                        $isDebit = ($record->transaction_type === 'income');

                        $validCategory = match($record->category) {
                            'income', 'expense', 'transfer', 'bank_charge', 'interest', 'other' => $record->category,
                            'fees', 'tuition', 'kiosk_sales', 'other_revenue', 'opening_balance' => 'income',
                            'salaries', 'supplies', 'maintenance', 'utilities', 'rent' => 'expense',
                            default => $isDebit ? 'income' : 'other',
                        };

                        CashbookTransaction::create([
                            'school_id' => $record->school_id,
                            'account_id' => $record->account_id,
                            'transaction_date' => $record->transaction_date,
                            'reference_number' => $ref,
                            'description' => $record->description,
                            'amount' => $record->amount,
                            'transaction_type' => $isDebit ? 'debit' : 'credit',
                            'category' => $validCategory,
                            'status' => 'unmatched',
                            'created_by' => $record->created_by,
                            'notes' => $record->notes,
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Cashbook historical backfill skipped: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive: Preserve existing data on live cloud deployments
    }
};
