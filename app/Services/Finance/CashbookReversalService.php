<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\Cashbook;
use App\Models\CashbookTransaction;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashbookReversalService
{
    protected AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    /**
     * Safely reverse a posted cashbook transaction
     *
     * @param Cashbook $cashbook
     * @param string|null $reason
     * @return JournalBatch|null
     * @throws \Exception
     */
    public function reverse(Cashbook $cashbook, ?string $reason = null): ?JournalBatch
    {
        if ($cashbook->status === 'reversed') {
            throw new \InvalidArgumentException("Cashbook entry #{$cashbook->id} has already been reversed.");
        }

        return DB::transaction(function () use ($cashbook, $reason) {
            $school = $cashbook->school;
            $reasonText = $reason ?? 'Reversal of cashbook entry #' . $cashbook->id;

            // 1. Locate linked General Ledger Journal Batch
            $originalBatch = null;

            if ($cashbook->journal_batch_id) {
                $originalBatch = JournalBatch::find($cashbook->journal_batch_id);
            }

            // Look up by source_type / source_id
            if (! $originalBatch) {
                $originalBatch = JournalBatch::where('school_id', $school->id)
                    ->whereIn('source_type', ['cashbook', 'manual_cashbook', 'cashbook_transaction'])
                    ->where('source_id', $cashbook->id)
                    ->first();
            }

            // Fallback: look up via CashbookTransaction
            $cbTx = CashbookTransaction::where('school_id', $school->id)
                ->where('account_id', $cashbook->account_id)
                ->where('transaction_date', $cashbook->transaction_date)
                ->where('amount', $cashbook->amount)
                ->where('status', '!=', 'reversed')
                ->first();

            if (! $originalBatch && $cbTx && $cbTx->journal_entry_id) {
                $originalBatch = JournalEntry::find($cbTx->journal_entry_id)?->batch;
            }

            $reversalBatch = null;

            // 2. Create and post reversal Journal Batch in GL if original exists
            if ($originalBatch && $originalBatch->status === 'posted') {
                $reversalEntries = [];
                foreach ($originalBatch->entries as $entry) {
                    $reversalEntries[] = [
                        'account_id' => $entry->account_id,
                        'entry_type' => $entry->entry_type === 'debit' ? 'credit' : 'debit',
                        'amount' => (float) $entry->amount,
                        'memo' => 'Reversal: ' . ($entry->memo ?? $cashbook->description),
                        'cost_center_id' => $entry->cost_center_id,
                        'project_id' => $entry->project_id,
                    ];
                }

                $reversalBatch = $this->accountingService->createJournalBatch([
                    'school_id' => $school->id,
                    'transaction_date' => now()->toDateString(),
                    'reference_number' => 'REV-' . ($originalBatch->batch_number ?? $cashbook->id),
                    'description' => $reasonText,
                    'source_type' => 'cashbook_reversal',
                    'source_id' => $cashbook->id,
                    'status' => 'posted',
                    'created_by' => auth()->id(),
                    'entries' => $reversalEntries,
                ]);
            }

            // 3. Update original Cashbook entry status and audit trail
            $cashbook->update([
                'status' => 'reversed',
                'reversal_batch_id' => $reversalBatch?->id,
                'reversed_at' => now(),
                'reversed_by' => auth()->id(),
                'notes' => trim(($cashbook->notes ?? '') . " [REVERSED: {$reasonText}]"),
            ]);

            // 4. Mark linked CashbookTransaction as reversed
            if ($cbTx) {
                $reversalEntryId = $reversalBatch?->entries?->first()?->id;
                $cbTx->update([
                    'status' => 'disputed',
                    'reversal_entry_id' => $reversalEntryId,
                    'notes' => trim(($cbTx->notes ?? '') . " [REVERSED: {$reasonText}]"),
                ]);
            }

            // 5. Append offsetting Cashbook record to maintain continuous running balance
            $lastBalance = Cashbook::where('school_id', $school->id)
                ->where('account_id', $cashbook->account_id)
                ->where('status', 'active')
                ->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->value('balance_after') ?? 0;

            // Reversing an expense increases balance; reversing income decreases balance
            $offsetAmount = (float) $cashbook->amount;
            $newBalance = $cashbook->transaction_type === 'expense'
                ? $lastBalance + $offsetAmount
                : $lastBalance - $offsetAmount;

            Cashbook::create([
                'school_id' => $school->id,
                'account_id' => $cashbook->account_id,
                'transaction_type' => $cashbook->transaction_type === 'expense' ? 'income' : 'expense',
                'category' => 'reversal',
                'description' => 'REVERSAL: ' . $cashbook->description,
                'amount' => $offsetAmount,
                'balance_after' => $newBalance,
                'transaction_date' => now()->toDateString(),
                'reference_number' => 'REV-' . ($cashbook->reference_number ?? $cashbook->id),
                'voucher_number' => 'REV-' . ($cashbook->voucher_number ?? $cashbook->id),
                'payment_method' => $cashbook->payment_method ?? 'journal',
                'status' => 'active',
                'reversal_batch_id' => $reversalBatch?->id,
                'created_by' => auth()->id(),
                'notes' => $reasonText,
                'project_id' => $cashbook->project_id,
                'cost_center_id' => $cashbook->cost_center_id,
            ]);

            return $reversalBatch;
        });
    }

    /**
     * Reversal helper returning structured result array.
     */
    public function reverseCashbookTransaction(Cashbook $cashbook, ?string $reason = null, $user = null): array
    {
        $batch = $this->reverse($cashbook, $reason);

        return [
            'success' => true,
            'reversal_batch_id' => $batch?->id,
            'message' => 'Cashbook transaction reversed successfully.',
        ];
    }
}
