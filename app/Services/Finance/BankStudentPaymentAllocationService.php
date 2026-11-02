<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\BankTransaction;
use App\Models\CashbookTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Student;
use App\Services\AccountingService;
use App\Services\BankReconciliationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BankStudentPaymentAllocationService
{
    public const DESCRIPTION_PREFIX = 'Synthetic register import; Student ';

    private const TERM_ONE_VALUES = ['Term 1', 'Term1', 'First Term', '1'];

    public function pendingQuery(int $schoolId, int $accountId)
    {
        return BankTransaction::query()
            ->where('school_id', $schoolId)
            ->where('account_id', $accountId)
            ->where('source', 'bank_statement')
            ->where('transaction_type', 'credit')
            ->where('status', 'unmatched')
            ->where('description', 'like', self::DESCRIPTION_PREFIX . '%')
            ->orderBy('transaction_date')
            ->orderBy('id');
    }

    public function suggest(BankTransaction $transaction, int $referenceCount = 1): array
    {
        $source = $this->parseSourceDescription($transaction->description);
        $candidates = $source ? $this->findStudentCandidates((int) $transaction->school_id, $source) : collect();
        $student = $candidates->count() === 1 ? $candidates->first() : null;
        $numberMatched = $student && $source && $source['student_number'] !== '' && $source['student_number'] !== '[number blank]'
            && in_array($source['student_number'], [$student->admission_number, $student->registration_number], true);

        $reason = null;
        if (! $source) {
            $reason = 'The imported line does not contain the expected student and source-row details.';
        } elseif ($candidates->isEmpty()) {
            $reason = 'No school student matched the imported admission or registration number, or exact name.';
        } elseif ($candidates->count() > 1) {
            $reason = 'More than one student matches. Choose the correct student manually.';
        }

        $reference = trim((string) $transaction->reference_number);
        $referenceNeedsReview = $reference === '' || $referenceCount > 1;

        return [
            'source' => $source,
            'candidates' => $candidates,
            'student' => $student,
            'invoice' => null,
            'reason' => $reason,
            'match_basis' => $source && $student
                ? ($numberMatched ? 'ERP admission/registration number match' : 'Exact name match; verify')
                : null,
            'reference_needs_review' => $referenceNeedsReview,
            'reference_count' => $referenceCount,
            'ready' => false,
        ];
    }

    public function searchStudents(int $schoolId, string $search)
    {
        $search = trim($search);
        if (mb_strlen($search) < 2) {
            return collect();
        }

        $like = '%' . addcslashes($search, '%_\\') . '%';

        return Student::query()
            ->select(['id', 'school_id', 'first_name', 'last_name', 'other_names', 'admission_number', 'registration_number'])
            ->where('school_id', $schoolId)
            ->where(function ($query) use ($like) {
                $query->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('other_names', 'like', $like)
                    ->orWhere('admission_number', 'like', $like)
                    ->orWhere('registration_number', 'like', $like);
            })
            ->orderBy('last_name')->orderBy('first_name')->limit(15)->get();
    }

    public function openTermOneInvoices(int $schoolId, int $studentId)
    {
        return Invoice::query()
            ->select(['id', 'school_id', 'student_id', 'number', 'academic_year', 'term', 'balance', 'status', 'issued_at'])
            ->where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->where('academic_year', '2026')
            ->whereIn('term', self::TERM_ONE_VALUES)
            ->whereNotIn('status', ['cancelled', 'void', 'deleted'])
            ->where('balance', '>', 0)
            ->orderBy('issued_at')->orderBy('id')->get();
    }

    public function post(BankTransaction $transaction, int $studentId, int $invoiceId, bool $referenceReviewed, int $userId): Payment
    {
        return DB::transaction(function () use ($transaction, $studentId, $invoiceId, $referenceReviewed, $userId) {
            $transaction = BankTransaction::where('school_id', $transaction->school_id)
                ->lockForUpdate()->findOrFail($transaction->id);

            if ($transaction->source !== 'bank_statement'
                || ! str_starts_with((string) $transaction->description, self::DESCRIPTION_PREFIX)
                || $transaction->transaction_type !== 'credit') {
                throw ValidationException::withMessages(['transaction' => 'Only incoming student-register bank lines can be posted here.']);
            }
            if ($transaction->status !== 'unmatched' || $this->cents($transaction->matched_amount) !== 0) {
                throw ValidationException::withMessages(['transaction' => 'This bank line is already matched or partly processed. Refresh the review before continuing.']);
            }
            if ($this->cents($transaction->amount) <= 0) {
                throw ValidationException::withMessages(['transaction' => 'The imported amount must be greater than zero.']);
            }

            $student = Student::where('school_id', $transaction->school_id)->findOrFail($studentId);
            $invoice = Invoice::where('school_id', $transaction->school_id)
                ->where('student_id', $student->id)->lockForUpdate()->findOrFail($invoiceId);

            if ((string) $invoice->academic_year !== '2026' || ! in_array($invoice->term, self::TERM_ONE_VALUES, true)) {
                throw ValidationException::withMessages(['invoice' => 'Only the selected student’s Term 1 2026 invoice can be used.']);
            }
            if (in_array(strtolower((string) $invoice->status), ['cancelled', 'void', 'deleted'], true) || $this->cents($invoice->balance) <= 0) {
                throw ValidationException::withMessages(['invoice' => 'The selected invoice is not open for payment.']);
            }
            if ($this->cents($transaction->amount) > $this->cents($invoice->balance)) {
                throw ValidationException::withMessages(['amount' => 'This payment exceeds the invoice balance. Select an appropriate invoice or leave the line on hold.']);
            }

            $period = app(AccountingPeriodService::class)->getPeriodForDate($transaction->transaction_date, (int) $transaction->school_id);
            if (! $period || ! $period->isOpen()) {
                throw ValidationException::withMessages(['period' => 'An explicitly open accounting period covering ' . $transaction->transaction_date->format('Y-m-d') . ' is required before posting.']);
            }

            $account = Account::where('school_id', $transaction->school_id)
                ->whereKey($transaction->account_id)->where('type', 'asset')->active()->postable()->first();
            if (! $account) {
                throw ValidationException::withMessages(['account' => 'The selected bank account is not active and postable for this school.']);
            }

            $reference = trim((string) $transaction->reference_number);
            $referenceCount = BankTransaction::where('school_id', $transaction->school_id)
                ->where('account_id', $transaction->account_id)
                ->where('source', 'bank_statement')
                ->where('transaction_type', 'credit')
                ->where('description', 'like', self::DESCRIPTION_PREFIX . '%')
                ->when($reference === '', fn ($query) => $query->whereNull('reference_number'), fn ($query) => $query->where('reference_number', $reference))
                ->count();
            if (($reference === '' || $referenceCount > 1) && ! $referenceReviewed) {
                throw ValidationException::withMessages(['reference' => 'Confirm that you reviewed the blank or repeated source reference before posting.']);
            }

            $amount = round((float) $transaction->amount, 2);
            $allocations = $this->fifoItemAllocations($invoice, $amount);
            $allocations = app(ScholarshipService::class)->paymentAllocations($invoice, $allocations, $amount);

            $payment = Payment::create([
                'school_id' => $transaction->school_id,
                'student_id' => $student->id,
                'guardian_id' => $invoice->guardian_id,
                'created_by' => $userId,
                'method' => 'bank',
                'account_id' => $account->id,
                'reference' => $reference !== '' ? $reference : null,
                'mopse_receipt_number' => null,
                'amount' => $amount,
                'paid_at' => $transaction->transaction_date,
                'status' => 'completed',
                'is_advance' => false,
                'advance_balance' => 0,
            ]);

            if ($allocations) {
                $items = $invoice->items()->with('feeStructure')->whereIn('id', array_keys($allocations))->get()->keyBy('id');
                foreach ($allocations as $itemId => $allocatedAmount) {
                    $item = $items->get($itemId);
                    PaymentAllocation::create([
                        'payment_id' => $payment->id,
                        'invoice_id' => $invoice->id,
                        'invoice_item_id' => $itemId,
                        'vote_code' => $item?->effective_vote_code,
                        'amount' => $allocatedAmount,
                    ]);
                }
            } else {
                PaymentAllocation::create(['payment_id' => $payment->id, 'invoice_id' => $invoice->id, 'amount' => $amount]);
            }

            $balance = max(0, $this->cents($invoice->balance) - $this->cents($amount)) / 100;
            $invoice->update(['balance' => $balance, 'status' => $balance <= 0 ? 'paid' : 'partial']);

            $journal = app(AccountingService::class)->postPayment($payment, $invoice, $account->id);
            $bankEntry = $journal->entries->firstWhere('account_id', $account->id);
            $cashbookTransaction = $bankEntry
                ? CashbookTransaction::where('school_id', $transaction->school_id)
                    ->where('account_id', $account->id)
                    ->where('journal_entry_id', $bankEntry->id)
                    ->lockForUpdate()->first()
                : null;

            if (! $cashbookTransaction) {
                throw ValidationException::withMessages(['cashbook' => 'The payment did not create its matching bank cashbook entry; all changes were rolled back.']);
            }

            app(BankReconciliationService::class)->createMatch($transaction, $cashbookTransaction, $amount, 'manual');

            AuditLog::create([
                'school_id' => $transaction->school_id,
                'user_id' => $userId,
                'action' => 'student_bank_payment_posted',
                'model_type' => Payment::class,
                'model_id' => $payment->id,
                'module' => 'finance',
                'new_values' => [
                    'bank_transaction_id' => $transaction->id,
                    'student_id' => $student->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $amount,
                    'paid_at' => $transaction->transaction_date->toDateString(),
                    'reference' => $reference !== '' ? $reference : null,
                    'journal_batch_id' => $journal->id,
                ],
                'description' => 'Student bank payment accepted from imported bank transaction.',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $payment->refresh();
        });
    }

    private function parseSourceDescription(string $description): ?array
    {
        if (! preg_match('/^Synthetic register import; Student ([^;]*); (.*?); source row (\d+); payment (\d+)$/', $description, $matches)) {
            return null;
        }

        $studentName = trim($matches[2]);
        $nameParts = preg_split('/\s+/', $studentName, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return [
            'student_number' => trim($matches[1]),
            'student_name' => $studentName,
            'source_row' => (int) $matches[3],
            'installment' => (int) $matches[4],
            'given_names' => count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 0, -1)) : '',
            'surname' => count($nameParts) > 1 ? end($nameParts) : '',
        ];
    }

    private function findStudentCandidates(int $schoolId, array $source)
    {
        $number = $source['student_number'];
        if ($number !== '' && $number !== '[number blank]') {
            $byNumber = Student::where('school_id', $schoolId)
                ->where(fn ($query) => $query->where('admission_number', $number)->orWhere('registration_number', $number))
                ->orderBy('id')->get();
            if ($byNumber->isNotEmpty()) {
                return $byNumber;
            }
        }

        if ($source['given_names'] === '' || $source['surname'] === '') {
            return collect();
        }

        $surname = mb_strtolower($source['surname']);
        $normalizedGivenNames = $this->normalizeName($source['given_names']);

        return Student::where('school_id', $schoolId)
            ->whereRaw('LOWER(last_name) = ?', [$surname])
            ->orderBy('id')->get()
            ->filter(fn (Student $student) => $this->normalizeName(trim(implode(' ', array_filter([
                $student->first_name,
                $student->other_names,
            ]))) ) === $normalizedGivenNames)
            ->values();
    }

    private function fifoItemAllocations(Invoice $invoice, float $amount): array
    {
        $items = $invoice->items()->with('feeStructure')->get();
        if ($items->isEmpty()) {
            return [];
        }

        $standardVotes = HandeFeeVotesService::getStandardVotes();
        $items = $items->sortBy(fn ($item) => (int) ($item->feeStructure?->allocation_priority
            ?? $standardVotes[$item->effective_vote_code]['priority'] ?? 10));
        $remaining = $this->cents($amount);
        $allocations = [];

        foreach ($items as $item) {
            $balance = $this->cents($item->balance);
            $take = min($remaining, $balance);
            if ($take > 0) {
                $allocations[$item->id] = $take / 100;
                $remaining -= $take;
            }
            if ($remaining === 0) {
                break;
            }
        }

        if ($remaining > 0) {
            throw ValidationException::withMessages(['allocations' => 'The invoice vote balances do not cover this payment. Review the invoice allocation before posting.']);
        }

        return $allocations;
    }

    private function normalizeName(string $value): string
    {
        return mb_strtolower(preg_replace('/[^\pL\pN]+/u', '', trim($value)) ?? '');
    }

    private function cents(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
