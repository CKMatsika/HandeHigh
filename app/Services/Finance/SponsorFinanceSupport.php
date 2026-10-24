<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Cashbook;
use App\Models\CashbookTransaction;
use App\Models\FundingSponsor;
use App\Models\JournalBatch;
use Illuminate\Validation\ValidationException;

class SponsorFinanceSupport
{
    public static function fail(string $message, string $field = 'funding'): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    public static function actor(int $schoolId, bool $management = false): int
    {
        $user = auth()->user();
        abort_unless($user && (int) $user->school_id === $schoolId && $user->hasAnyRole($management
            ? ['super-admin', 'school-admin', 'headmaster']
            : ['super-admin', 'school-admin', 'headmaster', 'deputy-headmaster', 'bursar', 'accountant']), 403);

        return (int) $user->id;
    }

    public static function checker(int $schoolId, int $makerId, string $reference): int
    {
        $checker = self::actor($schoolId, true);
        if ($makerId <= 0 || $checker === $makerId || trim($reference) === '') {
            self::fail('A different authorised checker and an approval reference are required.', 'approval_reference');
        }

        return $checker;
    }

    public static function account(FundingSponsor $sponsor): Account
    {
        if (! $sponsor->is_active) {
            self::fail('The sponsor is inactive.');
        }
        $account = app(FinancialAccountResolver::class)->getMappedAccount($sponsor->school_id, 'sponsor_receivable', $sponsor->code, false);
        $studentAr = app(FinancialAccountResolver::class)->getReceivableAccount($sponsor->school_id);
        if ($account->type !== 'asset' || $account->category !== 'receivable' || $account->id === $studentAr->id) {
            self::fail('Configure a separate sponsor receivable asset account for this sponsor.');
        }

        return $account;
    }

    public static function audit($model, string $action, array $data): void
    {
        AuditLog::create(['school_id' => $model->school_id, 'user_id' => auth()->id(), 'action' => $action,
            'model_type' => $model::class, 'model_id' => $model->id, 'module' => 'scholarships',
            'description' => $action, 'new_values' => $data]);
    }

    public static function cashbook(JournalBatch $batch, int $bankId, float $amount, string $date, string $reference, bool $reversed = false): void
    {
        Cashbook::create(['school_id' => $batch->school_id, 'account_id' => $bankId, 'transaction_type' => $reversed ? 'expense' : 'income',
            'category' => 'sponsor_receipt', 'classification' => 'sponsor_receipt', 'description' => $batch->description, 'amount' => $amount,
            'balance_after' => 0, 'transaction_date' => $date, 'reference_number' => $reference, 'payment_method' => 'bank_transfer',
            'journal_batch_id' => $batch->id, 'status' => 'posted', 'created_by' => auth()->id()]);
        CashbookTransaction::create(['school_id' => $batch->school_id, 'account_id' => $bankId, 'transaction_type' => $reversed ? 'credit' : 'debit',
            'category' => 'other', 'description' => $batch->description, 'amount' => $amount, 'transaction_date' => $date,
            'reference_number' => $reference, 'journal_entry_id' => $batch->entries->firstWhere('account_id', $bankId)->id,
            'status' => 'unmatched', 'created_by' => auth()->id()]);
    }

    public static function cents($value): int
    {
        return (int) round((float) $value * 100);
    }
}
