<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\Cashbook;
use App\Models\CashbookTransaction;
use App\Models\Invoice;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class FinancialReconciliationService
{
    public function __construct(
        protected FinancialAccountResolver $accountResolver
    ) {}

    /**
     * Reconcile the General Ledger trial balance for a school.
     * Verifies that total debits equal total credits across all posted journal entries.
     */
    public function reconcileGeneralLedgerBalance(School|int $school, ?string $asOfDate = null): array
    {
        $schoolId = $school instanceof School ? $school->id : $school;

        $query = JournalEntry::whereHas('journalBatch', function ($q) use ($schoolId, $asOfDate) {
            $q->where('school_id', $schoolId)
              ->where('status', 'posted');
            if ($asOfDate) {
                $q->where('transaction_date', '<=', $asOfDate);
            }
        });

        $totalDebits = (float) (clone $query)->where('entry_type', 'debit')->sum('amount');
        $totalCredits = (float) (clone $query)->where('entry_type', 'credit')->sum('amount');
        $diff = round($totalDebits - $totalCredits, 2);

        return [
            'school_id' => $schoolId,
            'as_of_date' => $asOfDate ?? now()->toDateString(),
            'total_debits' => round($totalDebits, 2),
            'total_credits' => round($totalCredits, 2),
            'difference' => $diff,
            'is_balanced' => ($diff === 0.0),
        ];
    }

    /**
     * Reconcile Student Debtors Control account against individual student statements and outstanding invoices.
     */
    public function reconcileDebtorsControl(School|int $school, ?string $asOfDate = null): array
    {
        $schoolId = $school instanceof School ? $school->id : $school;
        $arAccount = $this->accountResolver->getReceivableAccount($schoolId);

        // 1. Calculate GL AR Balance (Debits - Credits)
        $glEntries = JournalEntry::where('account_id', $arAccount->id)
            ->whereHas('journalBatch', function ($q) use ($schoolId, $asOfDate) {
                $q->where('school_id', $schoolId)
                  ->where('status', 'posted');
                if ($asOfDate) {
                    $q->where('transaction_date', '<=', $asOfDate);
                }
            });

        $glDebits = (float) (clone $glEntries)->where('entry_type', 'debit')->sum('amount');
        $glCredits = (float) (clone $glEntries)->where('entry_type', 'credit')->sum('amount');
        $glArBalance = round($glDebits - $glCredits, 2);

        // 2. Calculate Subledger Debtors Balance (Outstanding invoices balance)
        $invoiceQuery = Invoice::where('school_id', $schoolId);
        if ($asOfDate) {
            $invoiceQuery->where('issued_at', '<=', $asOfDate);
        }
        $subledgerBalance = round((float) (clone $invoiceQuery)->sum('balance'), 2);

        $difference = round($glArBalance - $subledgerBalance, 2);

        return [
            'ar_account_code' => $arAccount->code,
            'ar_account_name' => $arAccount->name,
            'gl_ar_balance' => $glArBalance,
            'subledger_debtors_balance' => $subledgerBalance,
            'difference' => $difference,
            'is_reconciled' => ($difference === 0.0),
        ];
    }

    /**
     * Reconcile Bank Accounts to General Ledger and Cashbook.
     */
    public function reconcileBankAndCashbook(School|int $school, ?string $asOfDate = null): array
    {
        $schoolId = $school instanceof School ? $school->id : $school;
        $bankAccounts = BankAccount::where('school_id', $schoolId)->where('is_active', true)->get();

        $results = [];

        foreach ($bankAccounts as $bank) {
            $glAccount = $this->accountResolver->getBankAccountGLAccount($bank);

            // Journal batches are the authoritative GL source; never include drafts.
            $glEntries = JournalEntry::where('account_id', $glAccount->id)
                ->whereHas('journalBatch', function ($q) use ($schoolId, $asOfDate) {
                    $q->where('school_id', $schoolId)
                      ->where('status', 'posted');
                    if ($asOfDate) {
                        $q->whereDate('transaction_date', '<=', $asOfDate);
                    }
                });

            $glDebits = (float) (clone $glEntries)->where('entry_type', 'debit')->sum('amount');
            $glCredits = (float) (clone $glEntries)->where('entry_type', 'credit')->sum('amount');
            $glBalance = round($glDebits - $glCredits, 2);

            // Cashbook is the movement source; cashbook_transactions is a duplicate
            // reconciliation feed and must not be added to this balance.
            $cbQuery = Cashbook::where('school_id', $schoolId)
                ->where('account_id', $glAccount->id)
                ->whereIn('status', ['active', 'posted', 'reversed']);
            if ($asOfDate) {
                $cbQuery->whereDate('transaction_date', '<=', $asOfDate);
            }
            $cashbookRows = $cbQuery->get();
            $openingRows = $cashbookRows->filter(fn (Cashbook $row) => $row->category === 'opening_balance'
                || $row->classification === 'opening_balance');
            $openingBalance = $openingRows->isNotEmpty()
                ? round($openingRows->sum(fn (Cashbook $row) => match (strtolower((string) $row->transaction_type)) {
                    'income', 'debit' => (float) $row->amount,
                    'expense', 'credit' => -(float) $row->amount,
                    default => 0.0,
                }), 2)
                : (float) $bank->opening_balance;

            $movements = $cashbookRows->reject(fn (Cashbook $row) => $row->category === 'opening_balance'
                || $row->classification === 'opening_balance');
            $cbIncome = 0.0;
            $cbExpense = 0.0;
            $unclassifiedCount = $openingRows->filter(fn (Cashbook $row) => ! in_array(
                strtolower((string) $row->transaction_type),
                ['income', 'debit', 'expense', 'credit'],
                true
            ))->count();
            foreach ($movements as $movement) {
                if (in_array(strtolower((string) $movement->transaction_type), ['income', 'debit'], true)) {
                    $cbIncome += (float) $movement->amount;
                } elseif (in_array(strtolower((string) $movement->transaction_type), ['expense', 'credit'], true)) {
                    $cbExpense += (float) $movement->amount;
                } else {
                    $unclassifiedCount++;
                }
            }
            $cbIncome = round($cbIncome, 2);
            $cbExpense = round($cbExpense, 2);
            $cbBalance = round($openingBalance + $cbIncome - $cbExpense, 2);

            $diff = round($glBalance - $cbBalance, 2);

            $results[] = [
                'bank_id' => $bank->id,
                'bank_name' => $bank->bank_name,
                'account_number' => $bank->account_number,
                'gl_account_code' => $glAccount->code,
                'opening_balance' => round($openingBalance, 2),
                'cashbook_inflows' => $cbIncome,
                'cashbook_outflows' => $cbExpense,
                'gl_balance' => $glBalance,
                'cashbook_balance' => $cbBalance,
                'difference' => $diff,
                'unclassified_cashbook_movements' => $unclassifiedCount,
                'is_reconciled' => ($diff === 0.0 && $unclassifiedCount === 0),
            ];
        }

        return $results;
    }

    /**
     * Reconcile Trade Creditors / Accounts Payable Control Account against supplier bills.
     */
    public function reconcileCreditorsControl(School|int $school, ?string $asOfDate = null): array
    {
        $schoolId = $school instanceof School ? $school->id : $school;
        $apAccount = $this->accountResolver->getPayableAccount($schoolId);

        // GL AP Balance (Credits - Debits)
        $glEntries = JournalEntry::where('account_id', $apAccount->id)
            ->whereHas('journalBatch', function ($q) use ($schoolId, $asOfDate) {
                $q->where('school_id', $schoolId)
                  ->where('status', 'posted');
                if ($asOfDate) {
                    $q->where('transaction_date', '<=', $asOfDate);
                }
            });

        $glDebits = (float) (clone $glEntries)->where('entry_type', 'debit')->sum('amount');
        $glCredits = (float) (clone $glEntries)->where('entry_type', 'credit')->sum('amount');
        $glApBalance = round($glCredits - $glDebits, 2);

        // Subledger Supplier Bills (Bill totals minus paid amounts)
        $billsQuery = Bill::where('school_id', $schoolId);
        if ($asOfDate) {
            $billsQuery->where('bill_date', '<=', $asOfDate);
        }
        $totalBilled = (float) (clone $billsQuery)->sum('total_amount');
        $totalPaid = (float) (clone $billsQuery)->sum('paid_amount');
        $subledgerApBalance = round($totalBilled - $totalPaid, 2);

        $diff = round($glApBalance - $subledgerApBalance, 2);

        return [
            'ap_account_code' => $apAccount->code,
            'ap_account_name' => $apAccount->name,
            'gl_ap_balance' => $glApBalance,
            'subledger_creditors_balance' => $subledgerApBalance,
            'difference' => $diff,
            'is_reconciled' => ($diff === 0.0),
        ];
    }

    /**
     * Generate Comprehensive Financial Discrepancy Report.
     */
    public function generateDiscrepancyReport(School|int $school): array
    {
        $schoolId = $school instanceof School ? $school->id : $school;

        $unbalancedBatches = [];
        $batches = JournalBatch::where('school_id', $schoolId)->where('status', 'posted')->with('entries')->get();
        foreach ($batches as $b) {
            $deb = (float) $b->entries->where('entry_type', 'debit')->sum('amount');
            $crd = (float) $b->entries->where('entry_type', 'credit')->sum('amount');
            if (round($deb - $crd, 2) !== 0.0) {
                $unbalancedBatches[] = [
                    'batch_id' => $b->id,
                    'reference' => $b->reference_number,
                    'debits' => $deb,
                    'credits' => $crd,
                    'diff' => round($deb - $crd, 2),
                ];
            }
        }

        $duplicateBatches = DB::table('journal_batches')
            ->where('school_id', $schoolId)
            ->whereNotNull('source_type')
            ->whereNotNull('source_id')
            ->select('source_type', 'source_id', DB::raw('count(*) as count'), DB::raw('group_concat(id) as batch_ids'))
            ->groupBy('source_type', 'source_id')
            ->having('count', '>', 1)
            ->get()
            ->toArray();

        $unpostedPayments = Payment::where('school_id', $schoolId)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('journal_batches')
                    ->whereRaw('journal_batches.source_id = payments.id')
                    ->whereIn('journal_batches.source_type', ['payment', 'advance_payment']);
            })
            ->count();

        $glBalance = $this->reconcileGeneralLedgerBalance($schoolId);
        $debtorsControl = $this->reconcileDebtorsControl($schoolId);
        $bankReconciliation = $this->reconcileBankAndCashbook($schoolId);
        $creditorsControl = $this->reconcileCreditorsControl($schoolId);

        return [
            'school_id' => $schoolId,
            'general_ledger' => $glBalance,
            'debtors_control' => $debtorsControl,
            'creditors_control' => $creditorsControl,
            'bank_reconciliation' => $bankReconciliation,
            'unbalanced_journal_batches' => $unbalancedBatches,
            'duplicate_journal_batches' => $duplicateBatches,
            'unposted_payments_count' => $unpostedPayments,
            'has_critical_discrepancies' => (
                !$glBalance['is_balanced'] ||
                !$debtorsControl['is_reconciled'] ||
                !$creditorsControl['is_reconciled'] ||
                !empty($unbalancedBatches) ||
                !empty($duplicateBatches) ||
                $unpostedPayments > 0
            ),
        ];
    }
}
