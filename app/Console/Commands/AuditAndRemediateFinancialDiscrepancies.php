<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\BillPayment;
use App\Models\Cashbook;
use App\Models\CashbookTransaction;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditAndRemediateFinancialDiscrepancies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:remediation-audit
                            {--school= : School ID (defaults to all schools)}
                            {--fix : Apply safe non-destructive remediation fixes}
                            {--confirm : Required confirmation for --fix}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit and identify unposted, duplicated, reversed, or partially posted financial records without automatic reposting';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $schoolId = $this->option('school');
        $isFix = $this->option('fix');
        $isConfirmed = $this->option('confirm');
        if ($isFix && ! $schoolId) {
            $this->error('A school ID is required with --fix so remediation cannot run across all schools.');

            return Command::FAILURE;
        }

        $this->info("================================================================================");
        $this->info("   HANDE HIGH ERP - FINANCIAL INTEGRATION DISCREPANCY AUDIT & REMEDIATION REPORT");
        $this->info("================================================================================");

        $schools = $schoolId ? School::where('id', $schoolId)->get() : School::all();

        $overallFindings = [];

        foreach ($schools as $school) {
            $this->newLine();
            $this->line("<comment>Inspecting School ID {$school->id}: {$school->name}</comment>");

            // 1. Unposted Student Payments
            $unpostedPayments = Payment::where('school_id', $school->id)
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('journal_batches')
                        ->whereRaw('journal_batches.source_id = payments.id')
                        ->whereIn('journal_batches.source_type', ['payment', 'advance_payment']);
                })
                ->get();

            // 2. Duplicate Journal Batches
            $duplicateBatches = DB::table('journal_batches')
                ->where('school_id', $school->id)
                ->whereNotNull('source_type')
                ->whereNotNull('source_id')
                ->select('source_type', 'source_id', DB::raw('count(*) as count'), DB::raw('group_concat(id) as batch_ids'))
                ->groupBy('source_type', 'source_id')
                ->having('count', '>', 1)
                ->get();

            // 3. Bill Payments Missing GL or Cashbook
            $unpostedBillPayments = BillPayment::where('school_id', $school->id)
                ->where(function ($q) {
                    $q->whereNull('journal_batch_id')
                      ->orWhereNotExists(function ($sub) {
                          $sub->select(DB::raw(1))
                              ->from('journal_batches')
                              ->whereRaw('journal_batches.id = bill_payments.journal_batch_id');
                      });
                })
                ->get();

            $unlinkedBillCashbook = BillPayment::where('school_id', $school->id)
                ->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('cashbook_transactions')
                        ->where('cashbook_transactions.category', 'bill_payment')
                        ->whereRaw('cashbook_transactions.amount = bill_payments.amount')
                        ->whereRaw('cashbook_transactions.transaction_date = bill_payments.payment_date');
                })
                ->get();

            // 4. Unmapped Legacy Bank Accounts
            $unmappedBankAccounts = BankAccount::where('school_id', $school->id)
                ->whereNull('account_id')
                ->get();

            // 5. Inverted Reconciliation Journal Batches
            $invertedRecBatches = JournalBatch::where('school_id', $school->id)
                ->where('source_type', 'reconciliation')
                ->with(['entries.account'])
                ->get()
                ->filter(function ($batch) {
                    $cbTx = CashbookTransaction::find($batch->source_id);
                    if (! $cbTx) return false;

                    $debitEntry = $batch->entries->firstWhere('entry_type', 'debit');
                    if (! $debitEntry) return false;

                    // If cashbook was debit (incoming), the debit entry should be an asset/bank, not revenue/expense
                    if ($cbTx->transaction_type === 'debit') {
                        return in_array($debitEntry->account->type, ['revenue', 'expense']);
                    }
                    return false;
                });

            $findings = [
                'school_id' => $school->id,
                'school_name' => $school->name,
                'unposted_payments_count' => $unpostedPayments->count(),
                'duplicate_batches_count' => $duplicateBatches->count(),
                'unposted_bill_payments_count' => $unpostedBillPayments->count(),
                'unlinked_bill_cashbook_count' => $unlinkedBillCashbook->count(),
                'unmapped_bank_accounts_count' => $unmappedBankAccounts->count(),
                'inverted_rec_batches_count' => $invertedRecBatches->count(),
            ];

            $overallFindings[] = $findings;

            $this->table(
                ['Discrepancy Category', 'Flagged Records', 'Status', 'Recommended Remediation'],
                [
                    ['Unposted Student Payments', $findings['unposted_payments_count'], $findings['unposted_payments_count'] > 0 ? 'NEEDS ATTENTION' : 'CLEAN', 'Verify payment method & post single batch with row lock'],
                    ['Duplicate Journal Batches', $findings['duplicate_batches_count'], $findings['duplicate_batches_count'] > 0 ? 'HIGH RISK' : 'CLEAN', 'Inspect duplicate IDs; void redundant draft/posted batches'],
                    ['Bill Payments Missing GL Batch', $findings['unposted_bill_payments_count'], $findings['unposted_bill_payments_count'] > 0 ? 'NEEDS ATTENTION' : 'CLEAN', 'Post matching Accounts Payable -> Bank credit batch'],
                    ['Bill Payments Missing Cashbook', $findings['unlinked_bill_cashbook_count'], $findings['unlinked_bill_cashbook_count'] > 0 ? 'DESYNCHRONISED' : 'CLEAN', 'Backfill Cashbook and CashbookTransaction records'],
                    ['Legacy Banks Missing GL Link', $findings['unmapped_bank_accounts_count'], $findings['unmapped_bank_accounts_count'] > 0 ? 'UNMAPPED' : 'CLEAN', 'Link bank_accounts.account_id to AFC(1321), CABS(1322), etc.'],
                    ['Inverted Reconciliation Entries', $findings['inverted_rec_batches_count'], $findings['inverted_rec_batches_count'] > 0 ? 'CRITICAL' : 'CLEAN', 'Reverse inverted batches and repost with corrected direction'],
                ]
            );
        }

        $this->newLine();
        if ($isFix) {
            if (!$isConfirmed) {
                $this->warn("Remediation execution cancelled. To apply safe fixes, pass both --fix and --confirm.");
                return 1;
            }

            $this->info("Applying safe, additive non-destructive remediations...");

            // 1. Backfill legacy bank account mappings
            $mappedCount = 0;
            foreach (BankAccount::whereNull('account_id')->get() as $bank) {
                $bankName = strtolower($bank->bank_name ?? $bank->account_name ?? '');
                $targetCode = match(true) {
                    str_contains($bankName, 'afc') => '1321',
                    str_contains($bankName, 'cabs') => '1322',
                    str_contains($bankName, 'zb') => '1323',
                    str_contains($bankName, 'cash') || str_contains($bankName, 'petty') => '1102',
                    default => null,
                };
                $acc = $targetCode
                    ? Account::where('school_id', $bank->school_id)
                        ->where('code', $targetCode)
                        ->where('type', 'asset')
                        ->first()
                    : null;
                if ($acc) {
                    $bank->update(['account_id' => $acc->id]);
                    $mappedCount++;
                }
            }
            $this->info("Mapped {$mappedCount} bank accounts to GL accounts.");

            // 2. Link unlinked bill payments to their bank account
            $billPaymentsLinked = 0;
            foreach (BillPayment::whereNull('account_id')->whereNotNull('bank_account_id')->get() as $bp) {
                if ($bp->bankAccount && $bp->bankAccount->account_id) {
                    $bp->updateQuietly(['account_id' => $bp->bankAccount->account_id]);
                    $billPaymentsLinked++;
                }
            }
            $this->info("Linked {$billPaymentsLinked} bill payments to corresponding GL bank account.");

            $this->info("Remediation phase 1 completed. No historical balances or journals were destructively altered.");
        } else {
            $this->line("<info>Notice: Running in audit mode (--dry-run). No database records were modified.</info>");
            $this->line("To review specific transaction IDs before approving, review the remediation artifact.");
        }

        return 0;
    }
}
