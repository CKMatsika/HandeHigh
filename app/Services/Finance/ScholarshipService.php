<?php

namespace App\Services\Finance;

use App\Models\{Account, AuditLog, Invoice, JournalBatch, LedgerEntry, ScholarshipApplication, Student, StudentScholarship};
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ScholarshipService
{
    public function rules(): array
    {
        return [
            'reference' => 'required|string|max:100', 'name' => 'required|string|max:255',
            'sponsor' => 'nullable|string|max:255', 'coverage_type' => 'required|in:full,partial',
            'coverage_basis' => 'required|in:percentage,amount',
            'coverage_value' => 'required|numeric|gt:0|max:999999999999.99',
            'covered_votes' => 'required|array|min:1',
            'covered_votes.*' => ['required', 'string', 'max:50', 'distinct', 'regex:/^[A-Z0-9_]+$/'],
            'starts_on' => 'required|date', 'ends_on' => 'required|date|after_or_equal:starts_on',
            'accounting_treatment' => 'required|in:unconfigured,school_remission,sponsor_receivable',
            'supporting_reference' => 'nullable|string|max:255',
            'academic_year'=>'nullable|digits:4','funding_source'=>'nullable|in:internal,external',
            'funding_sponsor_id'=>'nullable|integer','recognition_evidence'=>'nullable|string|max:4000',
        ];
    }

    public function saveAward(Student $student, array $data, ?StudentScholarship $award = null): StudentScholarship
    {
        SponsorFinanceSupport::actor($student->school_id);
        $data = Validator::make($data, $this->rules())->validate();
        $data['academic_year'] = ($data['academic_year']??null) ?: substr($data['starts_on'],0,4);
        $data['funding_source'] = ($data['funding_source']??null) ?: ($data['accounting_treatment']==='sponsor_receivable'?'external':'internal');
        if (!empty($data['funding_sponsor_id'])) {
            $sponsor = \App\Models\FundingSponsor::where('school_id',$student->school_id)->where('is_active',true)->findOrFail($data['funding_sponsor_id']);
            $data['sponsor']=$sponsor->name;
        }
        if (($data['funding_source']==='internal' && $data['accounting_treatment']==='sponsor_receivable')
            || ($data['funding_source']==='external' && $data['accounting_treatment']==='school_remission')) {
            $this->fail('funding_source','Funding source conflicts with accounting treatment.');
        }
        $knownVotes = array_unique(array_merge(array_keys(HandeFeeVotesService::getStandardVotes()),
            \App\Models\FeeStructure::where('school_id', $student->school_id)->whereNotNull('vote_code')->pluck('vote_code')->all()));
        if (array_diff($data['covered_votes'], $knownVotes)) {
            $this->fail('covered_votes', 'Select fee votes configured for this school.');
        }
        if ($data['coverage_basis'] === 'percentage' && (float) $data['coverage_value'] > 100) {
            $this->fail('coverage_value', 'Percentage coverage cannot exceed 100%.');
        }
        if ($data['coverage_type'] === 'full' && ($data['coverage_basis'] !== 'percentage' || (float) $data['coverage_value'] !== 100.0)) {
            $this->fail('coverage_value', 'Full coverage requires 100% of the selected votes.');
        }
        if ($data['accounting_treatment'] === 'sponsor_receivable' && empty($data['sponsor'])) {
            $this->fail('sponsor', 'A sponsor is required for sponsor receivables.');
        }

        return DB::transaction(function () use ($student, $data, $award) {
            $student = Student::whereKey($student->id)->lockForUpdate()->firstOrFail();
            if ($award) {
                $award = $student->scholarships()->whereKey($award->id)->lockForUpdate()->firstOrFail();
                if ($award->status !== 'pending') {
                    $this->fail('scholarship', 'Approved award terms are frozen. Close the award and create a new reference for changes.');
                }
            }
            if ($student->scholarships()->where('reference', $data['reference'])->when($award, fn ($q) => $q->where('id', '!=', $award->id))->exists()) {
                $this->fail('reference', 'This scholarship reference already exists for this student.');
            }
            $before = $award?->toArray();
            $award ??= new StudentScholarship(['school_id' => $student->school_id, 'student_id' => $student->id, 'created_by' => auth()->id(), 'status' => 'pending']);
            $award->fill($data)->save();
            $student->update(['funding_status' => 'scholarship']);
            $this->event($award, $before ? 'updated' : 'created', ['before' => $before, 'after' => $award->toArray()]);
            return $award;
        });
    }

    public function approve(StudentScholarship $award, string $reference): StudentScholarship
    {
        if (trim($reference) === '') {
            $this->fail('approval_reference', 'Management approval reference is required.');
        }
        return DB::transaction(function () use ($award, $reference) {
            Student::whereKey($award->student_id)->lockForUpdate()->firstOrFail();
            $award = StudentScholarship::whereKey($award->id)->lockForUpdate()->firstOrFail();
            SponsorFinanceSupport::checker($award->school_id,(int)$award->created_by,$reference);
            if ($award->status !== 'pending') {
                $this->fail('scholarship', 'Only pending awards can be approved.');
            }
            $this->offsetAccount($award); // No guessed policy or fallback accounts.
            if ($award->accounting_treatment==='sponsor_receivable' && empty($award->recognition_evidence)) {
                $this->fail('recognition_evidence','Document the approved, enforceable sponsor obligation before recognising a receivable.');
            }
            $overlaps = StudentScholarship::where('student_id', $award->student_id)
                ->where('school_id', $award->school_id)->where('status', 'approved')
                ->whereDate('starts_on', '<=', $award->ends_on)->whereDate('ends_on', '>=', $award->starts_on)->get();
            foreach ($award->covered_votes as $vote) {
                $percentage = $award->coverage_basis==='percentage'?(float)$award->coverage_value:0;
                foreach ($overlaps as $other) {
                    if (in_array($vote,$other->covered_votes,true) && $other->coverage_basis==='percentage') { $percentage+=(float)$other->coverage_value; }
                }
                if ($percentage>100.001) { $this->fail('covered_votes','Combined approved percentages exceed 100% for '.$vote); }
            }
            $award->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now(), 'approval_reference' => $reference]);
            $this->event($award, 'approved', ['approval_reference' => $reference]);
            return $award;
        });
    }

    public function close(StudentScholarship $award, string $reason): void
    {
        if (trim($reason) === '') { $this->fail('reason', 'A closure reason is required.'); }
        DB::transaction(function () use ($award, $reason) {
            Student::whereKey($award->student_id)->lockForUpdate()->firstOrFail();
            $award = StudentScholarship::whereKey($award->id)->lockForUpdate()->firstOrFail();
            if ($award->status === 'closed') { return; }
            $award->update(['status' => 'closed']);
            $this->event($award, 'closed', ['reason' => $reason]);
        });
    }

    public function applyEligible(Invoice $invoice): void
    {
        $awards = StudentScholarship::where('school_id', $invoice->school_id)->where('student_id', $invoice->student_id)
            ->where('status', 'approved')->whereDate('starts_on', '<=', $invoice->issued_at)
            ->whereDate('ends_on', '>=', $invoice->issued_at)->orderBy('id')->get();
        foreach ($awards as $award) { $this->apply($award, $invoice, $invoice->issued_at->toDateString()); }
    }

    public function apply(StudentScholarship $award, Invoice $invoice, string $date): ?ScholarshipApplication
    {
        if ($award->school_id !== $invoice->school_id || $award->student_id !== $invoice->student_id) {
            $this->fail('invoice', 'Scholarship and invoice must belong to the same student and school.');
        }
        return DB::transaction(function () use ($award, $invoice, $date) {
            Student::whereKey($award->student_id)->lockForUpdate()->firstOrFail();
            $award = StudentScholarship::whereKey($award->id)->lockForUpdate()->firstOrFail();
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $existing = $award->applications()->where('invoice_id', $invoice->id)->first();
            if ($existing) { return $existing; } // Reversed applications are never silently replayed.
            if ($award->status !== 'approved' || !$award->approved_at || !$award->approval_reference) {
                $this->fail('scholarship', 'Only approved scholarships can be applied.');
            }
            $issued = $invoice->issued_at?->toDateString();
            if (!$issued || $issued < $award->starts_on->toDateString() || $issued > $award->ends_on->toDateString() || $date < $issued) {
                $this->fail('accounting_date', 'Invoice must fall within award dates; posting cannot predate the invoice.');
            }
            if (in_array($invoice->status, ['cancelled', 'draft']) || !JournalBatch::where('school_id', $invoice->school_id)->where('source_type', 'invoice')->where('source_id', $invoice->id)->where('status', 'posted')->exists()) {
                $this->fail('invoice', 'Post the normal invoice before applying a scholarship.');
            }
            if ($invoice->allocations()->whereNull('invoice_item_id')->where('amount', '>', 0)->exists()
                || \App\Models\CreditNote::where('school_id', $invoice->school_id)->where('invoice_id', $invoice->id)->where('applied_amount', '>', 0)->exists()) {
                $this->fail('invoice', 'Existing payments or credits need fee-vote allocation review before scholarship coverage can be applied.');
            }
            $remaining = $award->coverage_basis === 'amount'
                ? max(0, $this->cents($award->coverage_value) - $this->cents($award->applications()->where('status', 'posted')->sum('amount')))
                : PHP_INT_MAX;
            $invoiceRemaining = $this->cents($invoice->balance);
            $lines = [];
            foreach ($invoice->items()->with('feeStructure')->orderBy('id')->get() as $item) {
                if (!in_array($item->effective_vote_code, $award->covered_votes, true)) { continue; }
                $desired = $award->coverage_basis === 'percentage'
                    ? (int) round($this->cents($item->line_total) * (float) $award->coverage_value / 100)
                    : $remaining;
                $amount = min($desired, $remaining, $invoiceRemaining, $this->cents($item->balance));
                if ($amount <= 0) { continue; }
                $lines[] = ['invoice_item_id' => $item->id, 'vote_code' => $item->effective_vote_code, 'amount' => $amount / 100];
                $remaining -= $amount;
                $invoiceRemaining -= $amount;
            }
            $amount = round(collect($lines)->sum('amount'), 2);
            if ($amount <= 0) { return null; }
            $offset = $this->offsetAccount($award);
            $receivable = app(FinancialAccountResolver::class)->getReceivableAccount($invoice->school_id);
            if ($offset->id === $receivable->id) { $this->fail('accounting_treatment', 'Scholarship offset must differ from student receivables.'); }
            if(!$offset->currency||strtoupper($offset->currency)!==strtoupper((string)$receivable->currency)){$this->fail('accounting_treatment','Scholarship and student receivable currencies differ; no conversion is assumed.');}
            $application = ScholarshipApplication::create([
                'school_id' => $invoice->school_id, 'student_scholarship_id' => $award->id,
                'invoice_id' => $invoice->id, 'accounting_date' => $date, 'amount' => $amount,
                'lines' => $lines, 'award_snapshot' => $award->toArray(), 'status' => 'posted',
            ]);
            $batch = app(AccountingService::class)->createJournalBatch([
                'school_id' => $invoice->school_id, 'transaction_date' => $date,
                'reference_number' => 'SCH-' . $application->id,
                'description' => "Scholarship {$award->reference}, invoice {$invoice->number}, student #{$invoice->student_id}",
                'source_type' => 'scholarship', 'source_id' => $application->id, 'status' => 'posted', 'created_by' => auth()->id(),
                'entries' => [
                    ['account_id' => $offset->id, 'entry_type' => 'debit', 'amount' => $amount, 'memo' => $award->accounting_treatment],
                    ['account_id' => $receivable->id, 'entry_type' => 'credit', 'amount' => $amount, 'memo' => "Student #{$invoice->student_id}, invoice {$invoice->number}"],
                ],
            ]);
            $application->update(['journal_batch_id' => $batch->id]);
            $invoice->updateQuietly(['balance' => $invoiceRemaining / 100, 'status' => $invoiceRemaining === 0 ? 'paid' : 'partial']);
            LedgerEntry::create(['school_id' => $invoice->school_id, 'student_id' => $invoice->student_id, 'invoice_id' => $invoice->id,
                'entry_date' => $date, 'account_code' => $receivable->code, 'type' => 'credit', 'amount' => $amount,
                'description' => "Scholarship SCH-{$application->id}: {$award->reference}"]);
            $this->event($award, 'applied', ['application_id' => $application->id, 'invoice_id' => $invoice->id, 'amount' => $amount, 'journal_batch_id' => $batch->id]);
            return $application;
        });
    }

    public function reverse(ScholarshipApplication $application, string $date, string $reason, ?int $approvalId = null): ScholarshipApplication
    {
        return DB::transaction(function () use ($application, $date, $reason, $approvalId) {
            $award = $application->scholarship;
            Student::whereKey($award->student_id)->lockForUpdate()->firstOrFail();
            StudentScholarship::whereKey($award->id)->lockForUpdate()->firstOrFail();
            $invoice = Invoice::whereKey($application->invoice_id)->lockForUpdate()->firstOrFail();
            $application = ScholarshipApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
            if ($application->status === 'reversed') { return $application; }
            app(ScholarshipApprovalService::class)->authorize($approvalId,$application,'scholarship_reverse',$date,$reason);
            if ($application->sponsorReceipts()->whereNull('reversed_on')->exists()) {
                $this->fail('scholarship', 'Reverse sponsor receipts before reversing scholarship coverage.');
            }
            if (\App\Models\SponsorSettlementAllocation::where('school_id',$application->school_id)->where('target_type','scholarship')->where('target_id',$application->id)->whereHas('settlement',fn($q)=>$q->where('status','posted'))->exists()) {
                $this->fail('scholarship','Reverse consolidated sponsor settlements before reversing coverage.');
            }
            if (trim($reason) === '' || $date < $application->accounting_date->toDateString()) {
                $this->fail('reason', 'Provide a reason and a reversal date on or after the scholarship posting.');
            }
            $original = $application->journalBatch;
            $batch = app(AccountingService::class)->createJournalBatch([
                'school_id' => $application->school_id, 'transaction_date' => $date,
                'reference_number' => 'REV-SCH-' . $application->id, 'description' => $reason,
                'source_type' => 'scholarship_reversal', 'source_id' => $application->id, 'status' => 'posted', 'created_by' => auth()->id(),
                'entries' => $original->entries->map(fn ($entry) => ['account_id' => $entry->account_id,
                    'entry_type' => $entry->entry_type === 'debit' ? 'credit' : 'debit', 'amount' => (float) $entry->amount,
                    'memo' => "Reverse SCH-{$application->id}, invoice {$invoice->number}"])->all(),
            ]);
            $balance = round((float) $invoice->balance + (float) $application->amount, 2);
            $invoice->updateQuietly(['balance' => $balance, 'status' => $balance >= (float) $invoice->total_amount ? 'unpaid' : 'partial']);
            $application->update(['status' => 'reversed', 'reversal_batch_id' => $batch->id, 'reversed_on' => $date, 'reversal_reason' => $reason]);
            $credit = $original->entries->firstWhere('entry_type', 'credit');
            LedgerEntry::create(['school_id' => $invoice->school_id, 'student_id' => $invoice->student_id, 'invoice_id' => $invoice->id,
                'entry_date' => $date, 'account_code' => $credit->account->code, 'type' => 'debit', 'amount' => $application->amount,
                'description' => "Scholarship reversal REV-SCH-{$application->id}: {$reason}"]);
            $this->event($award, 'reversed', ['application_id' => $application->id, 'reason' => $reason, 'journal_batch_id' => $batch->id]);
            return $application;
        });
    }

    public function offsetAccount(StudentScholarship $award): Account
    {
        if ($award->accounting_treatment==='sponsor_receivable' && $award->funding_sponsor_id) {
            return SponsorFinanceSupport::account($award->sponsorRecord);
        }
        $key = match ($award->accounting_treatment) {
            'school_remission' => 'SCHOOL_REMISSION', 'sponsor_receivable' => 'SPONSOR_RECEIVABLE',
            default => null,
        };
        if (!$key) { $this->fail('accounting_treatment', 'Management must select an accounting treatment before approval.'); }
        try {
            $account = app(FinancialAccountResolver::class)->getMappedAccount($award->school_id, 'scholarship', $key, false);
        } catch (\App\Exceptions\MissingAccountMappingException $e) {
            $this->fail('accounting_treatment', $e->getMessage());
        }
        if (($key === 'SCHOOL_REMISSION' && !in_array($account->type, ['expense', 'revenue'], true)) || ($key === 'SPONSOR_RECEIVABLE' && ($account->type !== 'asset' || $account->category !== 'receivable'))) {
            $this->fail('accounting_treatment', 'Use an approved expense/contra-revenue account for remission or a receivable asset for sponsorship.');
        }
        return $account;
    }

    public function receiveSponsor(ScholarshipApplication $application, array $input): \App\Models\ScholarshipSponsorReceipt
    {
        $data = Validator::make($input, ['reference' => 'required|string|max:100', 'received_on' => 'required|date',
            'amount' => 'required|numeric|min:0.01', 'account_id' => 'required|integer', 'cash_received' => 'required|accepted'])->validate();
        return DB::transaction(function () use ($application, $data) {
            $award = $application->scholarship;
            Student::whereKey($award->student_id)->lockForUpdate()->firstOrFail();
            $application = ScholarshipApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
            $existing = \App\Models\ScholarshipSponsorReceipt::where('school_id', $application->school_id)->where('reference', $data['reference'])->first();
            if ($existing) {
                if ($existing->scholarship_application_id !== $application->id || $this->cents($existing->amount) !== $this->cents($data['amount']) || $existing->account_id !== (int) $data['account_id'] || $existing->received_on->toDateString() !== $data['received_on']) {
                    $this->fail('reference', 'This receipt reference is already used for a different transaction.');
                }
                return $existing;
            }
            if ($this->cents($data['amount']) > $this->cents($application->sponsor_balance) || $data['received_on'] < $application->accounting_date->toDateString()) {
                $this->fail('amount', 'Receipt must settle an outstanding sponsor receivable and cannot predate it.');
            }
            $bank = Account::where('school_id', $application->school_id)->whereIn('category', ['bank','cash'])->where('is_active', true)->where('is_postable', true)->findOrFail($data['account_id']);
            $sponsorAccount = $application->journalBatch->entries->firstWhere('entry_type', 'debit')->account;
            if(!$bank->currency||strtoupper($bank->currency)!==strtoupper((string)$sponsorAccount->currency)){$this->fail('account_id','Bank and sponsor currencies differ; no conversion rate is assumed.');}
            $receipt = \App\Models\ScholarshipSponsorReceipt::create(['school_id' => $application->school_id,
                'scholarship_application_id' => $application->id, 'reference' => $data['reference'], 'received_on' => $data['received_on'],
                'amount' => round((float)$data['amount'], 2), 'account_id' => $bank->id, 'created_by' => auth()->id()]);
            $batch = app(AccountingService::class)->createJournalBatch([
                'school_id' => $application->school_id, 'transaction_date' => $data['received_on'], 'reference_number' => $data['reference'],
                'description' => "Sponsor cash received for SCH-{$application->id}: {$data['reference']}",
                'source_type' => 'scholarship_sponsor_receipt', 'source_id' => $receipt->id, 'status' => 'posted', 'created_by' => auth()->id(),
                'entries' => [['account_id' => $bank->id, 'entry_type' => 'debit', 'amount' => (float)$receipt->amount],
                    ['account_id' => $sponsorAccount->id, 'entry_type' => 'credit', 'amount' => (float)$receipt->amount]],
            ]);
            $receipt->update(['journal_batch_id' => $batch->id]);
            $this->sponsorCashbook($receipt, $batch, $data['received_on'], false);
            $this->event($award, 'sponsor_receipt', ['receipt_id' => $receipt->id, 'amount' => $receipt->amount, 'journal_batch_id' => $batch->id]);
            return $receipt;
        });
    }

    public function reverseSponsor(\App\Models\ScholarshipSponsorReceipt $receipt, string $date, string $reason, ?int $approvalId = null): void
    {
        DB::transaction(function () use ($receipt, $date, $reason, $approvalId) {
            $award = $receipt->application->scholarship;
            Student::whereKey($award->student_id)->lockForUpdate()->firstOrFail();
            $receipt = \App\Models\ScholarshipSponsorReceipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            if ($receipt->reversed_on) { return; }
            app(ScholarshipApprovalService::class)->authorize($approvalId,$receipt,'sponsor_receipt_reverse',$date,$reason);
            if (trim($reason) === '' || $date < $receipt->received_on->toDateString()) {
                $this->fail('reason', 'Provide a reason and a reversal date on or after receipt.');
            }
            $batch = app(AccountingService::class)->createJournalBatch([
                'school_id' => $receipt->school_id, 'transaction_date' => $date, 'reference_number' => 'REV-SPR-' . $receipt->id,
                'description' => $reason, 'source_type' => 'scholarship_sponsor_reversal', 'source_id' => $receipt->id,
                'status' => 'posted', 'created_by' => auth()->id(),
                'entries' => $receipt->journalBatch->entries->map(fn ($entry) => ['account_id' => $entry->account_id,
                    'entry_type' => $entry->entry_type === 'debit' ? 'credit' : 'debit', 'amount' => (float)$entry->amount])->all(),
            ]);
            $receipt->update(['reversed_on' => $date, 'reversal_batch_id' => $batch->id, 'reversal_reason' => $reason]);
            $this->sponsorCashbook($receipt, $batch, $date, true);
            $this->event($award, 'sponsor_receipt_reversal', ['receipt_id' => $receipt->id, 'reason' => $reason, 'journal_batch_id' => $batch->id]);
        });
    }

    private function sponsorCashbook(\App\Models\ScholarshipSponsorReceipt $receipt, JournalBatch $batch, string $date, bool $reversal): void
    {
        $reference = $reversal ? 'REV-SPR-' . $receipt->id : $receipt->reference;
        \App\Models\Cashbook::create(['school_id' => $receipt->school_id, 'account_id' => $receipt->account_id,
            'student_id' => $receipt->application->scholarship->student_id, 'transaction_type' => $reversal ? 'expense' : 'income',
            'category' => 'sponsor_receipt', 'classification' => 'sponsor_receipt', 'description' => $batch->description,
            'amount' => $receipt->amount, 'balance_after' => 0, 'transaction_date' => $date,
            'reference_number' => $reference, 'payment_method' => 'bank_transfer', 'journal_batch_id' => $batch->id,
            'status' => 'posted', 'created_by' => auth()->id()]);
        \App\Models\CashbookTransaction::create(['school_id' => $receipt->school_id, 'account_id' => $receipt->account_id,
            'transaction_date' => $date, 'reference_number' => $reference, 'description' => $batch->description,
            'amount' => $receipt->amount, 'transaction_type' => $reversal ? 'credit' : 'debit', 'category' => 'other',
            'journal_entry_id' => $batch->entries->firstWhere('account_id', $receipt->account_id)->id,
            'status' => 'unmatched', 'created_by' => auth()->id()]);
    }

    public function paymentAllocations(Invoice $invoice, array $allocations, float $amount): array
    {
        if (!$invoice->scholarshipApplications()->exists() && !$invoice->sponsorClaims()->exists()) { return $allocations; }
        if ($this->cents($amount) > $this->cents($invoice->balance)) {
            $this->fail('amount', 'Invoice balance changed. Refresh before recording payment.');
        }
        $items = $invoice->items()->with('feeStructure')->orderBy('id')->get()->keyBy('id');
        if($items->isEmpty()&&!$allocations){return [];}
        if (!$allocations) {
            $remaining = $this->cents($amount);
            foreach ($items as $item) {
                $value = min($remaining, $this->cents($item->balance));
                if ($value > 0) { $allocations[$item->id] = $value / 100; $remaining -= $value; }
            }
        }
        foreach ($allocations as $id => $value) {
            if (!$items->has($id) || $this->cents($value) > $this->cents($items[$id]->balance)) {
                $this->fail('allocations', 'Payment cannot cover another invoice or already funded fee votes.');
            }
        }
        if ($this->cents(array_sum($allocations)) !== $this->cents($amount)) {
            $this->fail('allocations', 'Allocate the payment exactly to the unpaid fee votes.');
        }
        return $allocations;
    }

    /** Scholarship entries are credits, with dated debit corrections, never cash. */
    public function statementEvents(Student $student): \Illuminate\Support\Collection
    {
        $events = collect();
        $applications = ScholarshipApplication::where('school_id', $student->school_id)
            ->whereHas('scholarship', fn ($q) => $q->where('student_id', $student->id))->with('invoice')->get();
        foreach ($applications as $application) {
            $base = ['invoice_id' => $application->invoice_id, 'academic_year' => $application->invoice->academic_year,
                'term' => $application->invoice->term, 'type' => 'scholarship', 'reference' => 'SCH-' . $application->id,
                'description' => 'Scholarship coverage · ' . $application->award_snapshot['reference'],
                'items' => collect(), 'due_date' => null, 'status' => $application->status, 'balance' => 0];
            $date = $application->accounting_date;
            $events->push(array_merge($base, ['raw_date' => $date, 'date' => $date->toDateString(), 'debit' => 0.0, 'credit' => (float) $application->amount]));
            if ($application->reversed_on) {
                $date = $application->reversed_on;
                $events->push(array_merge($base, ['raw_date' => $date, 'date' => $date->toDateString(), 'type' => 'scholarship_reversal',
                    'reference' => 'REV-SCH-' . $application->id, 'description' => 'Scholarship reversal: ' . $application->reversal_reason,
                    'debit' => (float) $application->amount, 'credit' => 0.0]));
            }
        }
        foreach (\App\Models\SponsorClaim::where('school_id',$student->school_id)->where('student_id',$student->id)
            ->whereIn('opening_inclusion',['student_control','current_invoice'])->whereNotNull('accounting_date')->with('invoice')->get() as $claim) {
            $base=['invoice_id'=>$claim->invoice_id,'academic_year'=>$claim->accounting_date->format('Y'),'term'=>$claim->invoice?->term,
                'type'=>'sponsor_claim','reference'=>'CLAIM-'.$claim->id,'description'=>'Historical sponsor reclassification '.$claim->reference,
                'items'=>collect(),'due_date'=>null,'status'=>$claim->status,'balance'=>0];
            $events->push(array_merge($base,['raw_date'=>$claim->accounting_date,'date'=>$claim->accounting_date->toDateString(),
                'debit'=>0.0,'credit'=>(float)$claim->opening_outstanding]));
            if($claim->reversed_on){$events->push(array_merge($base,['raw_date'=>$claim->reversed_on,'date'=>$claim->reversed_on->toDateString(),
                'reference'=>'REV-CLAIM-'.$claim->id,'description'=>'Historical sponsor reclassification reversal','debit'=>(float)$claim->opening_outstanding,'credit'=>0.0]));}
        }
        return $events;
    }

    public function legacyStatement(Student $student, $events, float $openingBalance, string $year, ?string $term): array
    {
        if($student->sponsorClaims()->whereIn('opening_inclusion',['student_control','current_invoice'])->exists()){
            $report=app(FinanceReportingService::class)->getStudentStatement($student->school,$student,['academic_year'=>$year,'term'=>$term]);
            return ['events'=>$report['ledger_rows']->map(function($event){$event['date']=$event['raw_date'];$event['balance']=$event['running_balance'];return $event;}),
                'opening_balance'=>$report['opening_balance']];
        }
        foreach ($this->statementEvents($student) as $event) {
            if ((string) $event['academic_year'] === $year && (!$term || $event['term'] === $term)) {
                $event['date'] = $event['raw_date']; // Existing statement templates format Carbon dates.
                $events->push($event);
            } elseif (($term && (string) $event['academic_year'] === $year && $event['term'] < $term)
                || (!$term && (string) $event['academic_year'] < $year)) {
                $openingBalance += $event['debit'] - $event['credit'];
            }
        }
        return ['events' => $events->sortBy(fn ($event) => \Carbon\Carbon::parse($event['date'])->timestamp)->values(), 'opening_balance' => $openingBalance];
    }

    private function event(StudentScholarship $award, string $action, array $details): void
    {
        AuditLog::create(['school_id' => $award->school_id, 'model_type' => StudentScholarship::class, 'model_id' => $award->id,
            'user_id' => auth()->id(), 'action' => $action, 'module' => 'scholarships',
            'description' => "Scholarship {$award->reference}: {$action}", 'new_values' => $details]);
    }

    private function cents($amount): int { return (int) round((float) $amount * 100); }
    private function fail(string $field, string $message): never { throw ValidationException::withMessages([$field => $message]); }
}
