<?php

namespace App\Services\Finance;

use App\Models\Account;

use App\Models\Invoice;
use App\Models\JournalBatch;
use App\Models\LedgerEntry;
use App\Models\School;
use App\Models\SponsorClaim;
use App\Models\SponsorClaimBatch;
use App\Models\Student;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SponsorClaimService
{
    public function approveBatch(SponsorClaimBatch $batch, string $reference): SponsorClaimBatch
    {
        return DB::transaction(function () use ($batch, $reference) {
            School::whereKey($batch->school_id)->lockForUpdate()->firstOrFail();
            $batch = SponsorClaimBatch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $checker = SponsorFinanceSupport::checker($batch->school_id, $batch->created_by, $reference);
            if ($batch->status === 'posted') {
                return $batch;
            }
            if (! $batch->claims()->exists()) {
                SponsorFinanceSupport::fail('No verified claims are ready for approval.');
            }
            foreach ($batch->claims()->orderBy('id')->lockForUpdate()->get() as $claim) {
                $this->post($claim);
            }
            $batch->update(['status' => 'posted', 'approved_by' => $checker, 'approval_reference' => $reference]);
            SponsorFinanceSupport::audit($batch, 'claim_batch_approved', ['reference' => $reference]);

            return $batch;
        });
    }

    public function validateTerms(SponsorClaimBatch $batch, array $input): array
    {
        SponsorFinanceSupport::actor($batch->school_id);
        if ($batch->status !== 'pending_verification') {
            SponsorFinanceSupport::fail('This batch is frozen.');
        }
        $data = Validator::make($input, [
            'student_id' => 'required|integer', 'academic_year' => 'required|digits:4', 'reference' => 'required|string|max:100',
            'approved_amount' => 'required|numeric|min:0.01', 'recognised_amount' => 'required|numeric|min:0',
            'received_before_cutover' => 'required|numeric|min:0', 'outstanding_balance' => 'required|numeric|min:0',
            'disputed_amount' => 'nullable|numeric|min:0', 'rejected_amount' => 'nullable|numeric|min:0',
            'fee_votes' => 'required|array|min:1', 'fee_votes.*' => 'required|numeric|min:0.01',
            'classification' => 'required|in:prior_receivable,current_receivable,pending_claim,rejected,disputed',
            'opening_inclusion' => 'required|in:sponsor_control,student_control,not_in_opening,unknown,current_invoice',
            'accounting_date' => 'nullable|date',
            'submitted_on' => 'nullable|date', 'currency' => 'nullable|string|size:3',
            'invoice_id' => 'nullable|integer', 'opening_journal_batch_id' => 'nullable|integer',
            'recognition_evidence' => 'required|string|max:4000', 'supporting_reference' => 'required|string|max:255',
            'migration_staging_row_id' => 'nullable|integer',
        ])->validate();
        Student::where('school_id', $batch->school_id)->findOrFail($data['student_id']);
        if ((string) $data['academic_year'] !== (string) $batch->academic_year) {
            SponsorFinanceSupport::fail('Claim year must match its batch.');
        }
        $approved = SponsorFinanceSupport::cents($data['approved_amount']);
        $recognised = SponsorFinanceSupport::cents($data['recognised_amount']);
        $received = SponsorFinanceSupport::cents($data['received_before_cutover']);
        if ($recognised > $approved || $received > $recognised || SponsorFinanceSupport::cents($data['outstanding_balance']) !== $recognised - $received
            || SponsorFinanceSupport::cents(array_sum($data['fee_votes'])) !== $approved
            || SponsorFinanceSupport::cents(($data['disputed_amount'] ?? 0) + ($data['rejected_amount'] ?? 0)) > $approved
            || $recognised + SponsorFinanceSupport::cents($data['rejected_amount'] ?? 0) > $approved) {
            SponsorFinanceSupport::fail('Claim, receipts, outstanding and fee-vote totals do not reconcile.');
        }
        foreach (array_keys($data['fee_votes']) as $vote) {
            if (! preg_match('/^[A-Z0-9_]{1,50}$/', (string) $vote)) {
                SponsorFinanceSupport::fail('Invalid fee vote.');
            }
        }
        if (! in_array($data['classification'], ['prior_receivable', 'current_receivable']) && ($recognised !== 0 || $received !== 0)) {
            SponsorFinanceSupport::fail('Pending, rejected or unrecognised disputed claims cannot create a receivable.');
        }
        if ($data['classification'] === 'prior_receivable' && ($data['opening_inclusion'] === 'unknown' || $recognised - $received <= 0)) {
            SponsorFinanceSupport::fail('Verify opening-balance inclusion before recognising a historical claim.');
        }
        if (in_array($data['classification'], ['prior_receivable', 'current_receivable']) && empty($data['currency'])) {
            SponsorFinanceSupport::fail('Verify the ledger currency before recognising a claim.', 'currency');
        }
        if ($data['classification'] === 'current_receivable' && ($data['opening_inclusion'] !== 'current_invoice' || empty($data['accounting_date']))) {
            SponsorFinanceSupport::fail('Current claims require a posted invoice transfer and an accounting date.');
        }
        unset($data['outstanding_balance']);
        if (SponsorClaim::where('funding_sponsor_id', $batch->funding_sponsor_id)->where('academic_year', $data['academic_year'])->where('student_id', $data['student_id'])->where('status','!=','reversed')->exists()) {
            SponsorFinanceSupport::fail('A consolidated claim already exists for this sponsor/year/student.');
        }
        if (! empty($data['opening_journal_batch_id'])) {
            JournalBatch::where('school_id', $batch->school_id)->where('source_type', 'opening_balance')->where('status', 'posted')->findOrFail($data['opening_journal_batch_id']);
        }
        if (! empty($data['invoice_id'])) {
            Invoice::where('school_id', $batch->school_id)->where('student_id', $data['student_id'])->findOrFail($data['invoice_id']);
        }

        return $data;
    }

    public function createClaim(SponsorClaimBatch $batch, array $input): SponsorClaim
    {
        $data = $this->validateTerms($batch, $input);
        $data['active_identity']=hash('sha256',implode('|',[$batch->school_id,$batch->funding_sponsor_id,$data['academic_year'],$data['student_id']]));
        $data['supersedes_claim_id']=SponsorClaim::where('funding_sponsor_id',$batch->funding_sponsor_id)->where('academic_year',$data['academic_year'])->where('student_id',$data['student_id'])->where('status','reversed')->latest('id')->value('id');
        $claim = SponsorClaim::create(array_merge($data, ['school_id' => $batch->school_id, 'funding_sponsor_id' => $batch->funding_sponsor_id,
            'sponsor_claim_batch_id' => $batch->id, 'opening_outstanding' => 0, 'status' => 'pending']));
        SponsorFinanceSupport::audit($claim, 'claim_verified', ['terms' => $claim->toArray()]);

        return $claim;
    }

    private function post(SponsorClaim $claim): void
    {
        if ($claim->status !== 'pending') {
            return;
        }
        if (! in_array($claim->classification, ['prior_receivable', 'current_receivable'])) {
            $claim->update(['status' => $claim->classification]);
            SponsorFinanceSupport::audit($claim, 'claim_not_recognised', ['classification' => $claim->classification]);

            return;
        }
        $account = SponsorFinanceSupport::account($claim->sponsor);
        if (! $claim->currency || strtoupper($claim->currency) !== strtoupper((string) $account->currency)) {
            SponsorFinanceSupport::fail('Verify the claim currency against the sponsor ledger. No exchange rate is assumed.');
        }
        $amount = round((float) $claim->recognised_amount - (float) $claim->received_before_cutover, 2);
        $current = $claim->classification === 'current_receivable';
        $date = $current ? ($claim->accounting_date?->toDateString() ?? '') : '2026-01-01';
        if($claim->supersedes_claim_id){$prior=SponsorClaim::where('school_id',$claim->school_id)->findOrFail($claim->supersedes_claim_id);
            if($current && $date<$prior->reversed_on->toDateString()){SponsorFinanceSupport::fail('Replacement recognition cannot predate the checked reversal.');}
            if(!$current){$date=max($date,$prior->reversed_on->toDateString());}}
        if ((! $current && $claim->academic_year >= '2026') || ($current && ($claim->academic_year < '2026' || ! $date || $claim->opening_inclusion !== 'current_invoice' || (float) $claim->received_before_cutover !== 0.0))) {
            SponsorFinanceSupport::fail('Use prior-year opening treatment or a dated current invoice transfer as appropriate.');
        }
        $journal = null;
        $allocated = [];
        if ($current) {
            $invoice = Invoice::where('school_id', $claim->school_id)->where('student_id', $claim->student_id)->lockForUpdate()->findOrFail($claim->invoice_id);
            if ((string) $invoice->academic_year !== (string) $claim->academic_year || ! $invoice->issued_at || $date < $invoice->issued_at->toDateString()
                || in_array($invoice->status, ['draft', 'cancelled']) || SponsorFinanceSupport::cents($invoice->balance) < SponsorFinanceSupport::cents($amount)
                || ! JournalBatch::where('school_id', $claim->school_id)->where('source_type', 'invoice')->where('source_id', $invoice->id)->where('status', 'posted')->exists()) {
                SponsorFinanceSupport::fail('Match a posted current-year invoice with sufficient eligible debt.');
            }
            if ($invoice->allocations()->whereNull('invoice_item_id')->where('amount', '>', 0)->exists()) {
                SponsorFinanceSupport::fail('Review unallocated payments before recognising fee-vote claim coverage.');
            }
            $remaining = SponsorFinanceSupport::cents($amount);
            foreach ($invoice->items()->with('feeStructure')->orderBy('id')->get() as $item) {
                $vote = $item->effective_vote_code;
                $limit = SponsorFinanceSupport::cents($claim->fee_votes[$vote] ?? 0);
                $used = collect($allocated)->where('vote_code', $vote)->sum('amount');
                $value = min($remaining, max(0, $limit - SponsorFinanceSupport::cents($used)), SponsorFinanceSupport::cents($item->balance));
                if ($value > 0) {
                    $allocated[] = ['invoice_item_id' => $item->id, 'vote_code' => $vote, 'amount' => $value / 100];
                    $remaining -= $value;
                }
            }
            if ($remaining !== 0) {
                SponsorFinanceSupport::fail('Recognised claim exceeds the eligible unpaid fee votes.');
            }
            $ar = app(FinancialAccountResolver::class)->getReceivableAccount($claim->school_id);
            $journal = $this->journal($claim, $amount, $date, $account->id, $ar->id, 'sponsor_claim_reclass');
            $invoice->updateQuietly(['balance' => round((float) $invoice->balance - $amount, 2), 'status' => (float) $invoice->balance - $amount <= 0 ? 'paid' : 'partial']);
            LedgerEntry::create(['school_id' => $claim->school_id, 'student_id' => $claim->student_id, 'invoice_id' => $invoice->id, 'entry_date' => $date,
                'account_code' => $ar->code, 'type' => 'credit', 'amount' => $amount, 'description' => 'Sponsor claim '.$claim->reference]);
        } elseif ($claim->opening_inclusion === 'sponsor_control') {
            $opening = JournalBatch::where('school_id', $claim->school_id)->where('status', 'posted')
                ->where('source_type', 'opening_balance')->whereDate('transaction_date', '2026-01-01')->findOrFail($claim->opening_journal_batch_id);
            $released=SponsorClaim::where('school_id',$claim->school_id)->where('opening_journal_batch_id',$opening->id)->where('opening_inclusion','sponsor_control')->where('status','reversed')->max('reversed_on');
            if($released){$date=max($date,substr($released,0,10));}
            $openingAmount = (float) $opening->entries()->where('account_id', $account->id)->where('entry_type', 'debit')->sum('amount')
                - (float) $opening->entries()->where('account_id', $account->id)->where('entry_type', 'credit')->sum('amount');
            $linked = (float) SponsorClaim::where('school_id', $claim->school_id)->where('status', 'posted')
                ->where('opening_inclusion', 'sponsor_control')->where('opening_journal_batch_id', $opening->id)
                ->where('receivable_account_id', $account->id)->sum('opening_outstanding');
            if (SponsorFinanceSupport::cents($linked + $amount) > SponsorFinanceSupport::cents($openingAmount)) {
                SponsorFinanceSupport::fail('Claim exceeds the unlinked sponsor balance already carried in this opening journal.');
            }
            // Attach a beneficiary subledger to an existing opening asset; create no second GL asset.
        } elseif ($claim->opening_inclusion === 'student_control') {
            $invoice = Invoice::where('school_id', $claim->school_id)->where('student_id', $claim->student_id)->lockForUpdate()->findOrFail($claim->invoice_id);
            if (! $invoice->issued_at || $invoice->issued_at->toDateString() > $date || in_array($invoice->status, ['draft', 'cancelled'])
                || SponsorFinanceSupport::cents($invoice->balance) < SponsorFinanceSupport::cents($amount)) {
                SponsorFinanceSupport::fail('Match an existing opening student invoice with sufficient outstanding debt.');
            }
            $studentAr = app(FinancialAccountResolver::class)->getReceivableAccount($claim->school_id);
            $opening = JournalBatch::where('school_id', $claim->school_id)->where('status', 'posted')->where('source_type', 'opening_balance')
                ->whereDate('transaction_date', '2026-01-01')->findOrFail($claim->opening_journal_batch_id);
            $openingAmount = (float) $opening->entries()->where('account_id', $studentAr->id)->where('entry_type', 'debit')->sum('amount')
                - (float) $opening->entries()->where('account_id', $studentAr->id)->where('entry_type', 'credit')->sum('amount');
            $used = (float) SponsorClaim::where('school_id', $claim->school_id)->where('status', 'posted')->where('opening_inclusion', 'student_control')
                ->where('opening_journal_batch_id', $opening->id)->sum('opening_outstanding');
            if (SponsorFinanceSupport::cents($used + $amount) > SponsorFinanceSupport::cents($openingAmount)) {
                SponsorFinanceSupport::fail('Opening student control is insufficient.');
            }
            $journal = $this->journal($claim, $amount, $date, $account->id, $studentAr->id, 'sponsor_claim_reclass');
            $invoice->updateQuietly(['balance' => round((float) $invoice->balance - $amount, 2), 'status' => (float) $invoice->balance - $amount <= 0 ? 'paid' : 'partial']);
            LedgerEntry::create(['school_id' => $claim->school_id, 'student_id' => $claim->student_id, 'invoice_id' => $invoice->id,
                'entry_date' => $date, 'account_code' => $studentAr->code, 'type' => 'credit', 'amount' => $amount, 'description' => 'BEAM reclassification '.$claim->reference]);
        } elseif ($claim->opening_inclusion === 'not_in_opening') {
            $equity = app(FinancialAccountResolver::class)->getMappedAccount($claim->school_id, 'control_account', 'OPENING_BALANCE_EQUITY', false);
            if ($equity->type !== 'equity') {
                SponsorFinanceSupport::fail('Opening balance correction must use an approved equity account.');
            }
            $journal = $this->journal($claim, $amount, $date, $account->id, $equity->id, 'sponsor_opening');
        } else {
            SponsorFinanceSupport::fail('Unverified opening treatment cannot be posted.');
        }
        $claim->update(['status' => 'posted', 'opening_outstanding' => $amount, 'accounting_date' => $date, 'receivable_account_id' => $account->id, 'journal_batch_id' => $journal?->id, 'allocated_lines' => $allocated]);
        if ($claim->migration_staging_row_id) {
            \App\Models\MigrationStagingRow::whereKey($claim->migration_staging_row_id)->update(['status' => 'posted', 'journal_batch_id' => $journal?->id]);
        }
        SponsorFinanceSupport::audit($claim, 'claim_opening_posted', ['amount' => $amount, 'journal_batch_id' => $journal?->id, 'opening_inclusion' => $claim->opening_inclusion]);
    }

    public function prepareRecognition(SponsorClaim $claim, array $input): SponsorClaim
    {
        $maker = SponsorFinanceSupport::actor($claim->school_id);
        $data = Validator::make($input, ['recognised_amount' => 'required|numeric|min:0.01', 'received_before_cutover' => 'required|numeric|min:0',
            'approved_amount'=>'nullable|numeric|min:0.01','fee_votes'=>'nullable|array|min:1','fee_votes.*'=>'required|numeric|min:0.01',
            'opening_inclusion' => 'required|in:sponsor_control,student_control,not_in_opening,current_invoice',
            'opening_journal_batch_id' => 'nullable|integer', 'invoice_id' => 'nullable|integer', 'accounting_date' => 'nullable|date',
            'currency' => 'required|string|size:3',
            'recognition_evidence' => 'required|string|max:4000', 'supporting_reference' => 'required|string|max:255'])->validate();

        return DB::transaction(function () use ($claim, $data, $maker) {
            School::whereKey($claim->school_id)->lockForUpdate()->firstOrFail();
            $claim = SponsorClaim::whereKey($claim->id)->lockForUpdate()->firstOrFail();
            if (in_array($claim->status, ['posted', 'reversed'])) {
                SponsorFinanceSupport::fail('Posted claims require a controlled correction.');
            }
            $approved=$data['approved_amount']??$claim->approved_amount;$votes=$data['fee_votes']??$claim->fee_votes;
            if(SponsorFinanceSupport::cents(array_sum($votes))!==SponsorFinanceSupport::cents($approved)){SponsorFinanceSupport::fail('Revised approved amount must reconcile to its fee votes.');}
            foreach(array_keys($votes) as $vote){if(!preg_match('/^[A-Z0-9_]{1,50}$/',(string)$vote)){SponsorFinanceSupport::fail('Invalid fee vote.');}}
            if (SponsorFinanceSupport::cents($data['recognised_amount']) > SponsorFinanceSupport::cents($approved)
                || SponsorFinanceSupport::cents($data['received_before_cutover']) >= SponsorFinanceSupport::cents($data['recognised_amount'])) {
                SponsorFinanceSupport::fail('Recognition must fit the approved claim and leave an outstanding asset.');
            }
            $before = $claim->toArray();
            $claim->fill($data);
            $claim->approved_amount=$approved;$claim->fee_votes=$votes;
            $claim->status = 'recognition_pending';
            $claim->classification = $claim->academic_year < '2026' ? 'prior_receivable' : 'current_receivable';
            $claim->recognition_prepared_by = $maker;
            $claim->disputed_amount = 0;
            $claim->rejected_amount = 0;
            $claim->save();
            SponsorFinanceSupport::audit($claim, 'claim_recognition_prepared', ['before' => $before, 'after' => $claim->toArray()]);

            return $claim;
        });
    }

    public function approveRecognition(SponsorClaim $claim, string $reference): SponsorClaim
    {
        return DB::transaction(function () use ($claim, $reference) {
            School::whereKey($claim->school_id)->lockForUpdate()->firstOrFail();
            $claim = SponsorClaim::whereKey($claim->id)->lockForUpdate()->firstOrFail();
            $checker = SponsorFinanceSupport::checker($claim->school_id, (int) $claim->recognition_prepared_by, $reference);
            if ($claim->status === 'posted' && $claim->recognition_approved_by) {
                return $claim;
            }
            if ($claim->status !== 'recognition_pending' || ! $claim->recognition_prepared_by) {
                SponsorFinanceSupport::fail('Prepare and verify the pending recognition first.');
            }
            $claim->update(['status' => 'pending']);
            $this->post($claim);
            $claim->update(['recognition_approved_by' => $checker, 'recognition_approval_reference' => $reference]);
            SponsorFinanceSupport::audit($claim, 'claim_recognition_checked', ['reference' => $reference]);

            return $claim;
        });
    }

    private function journal(SponsorClaim $claim, float $amount, string $date, int $debit, int $credit, string $source): JournalBatch
    {
        $currencies=Account::where('school_id',$claim->school_id)->whereIn('id',[$debit,$credit])->pluck('currency')->map(fn($value)=>strtoupper((string)$value))->unique();
        if($currencies->count()!==1||$currencies->first()!==strtoupper((string)$claim->currency)){SponsorFinanceSupport::fail('Transfer and opening accounts must share the verified claim currency. No conversion is assumed.');}
        return app(AccountingService::class)->createJournalBatch(['school_id' => $claim->school_id, 'transaction_date' => $date,
            'reference_number' => 'CLAIM-'.$claim->id, 'description' => 'Verified historical claim '.$claim->reference,
            'source_type' => $source, 'source_id' => $claim->id, 'status' => 'posted', 'created_by' => auth()->id(),
            'entries' => [['account_id' => $debit, 'entry_type' => 'debit', 'amount' => $amount], ['account_id' => $credit, 'entry_type' => 'credit', 'amount' => $amount]]]);
    }

    public function reverse(SponsorClaim $claim, string $date, string $reason, ?int $approvalId = null): void
    {
        DB::transaction(function () use ($claim, $date, $reason, $approvalId) {
            School::whereKey($claim->school_id)->lockForUpdate()->firstOrFail();
            $claim = SponsorClaim::whereKey($claim->id)->lockForUpdate()->firstOrFail();
            if ($claim->status === 'reversed') {
                return;
            }
            app(ScholarshipApprovalService::class)->authorize($approvalId, $claim, 'claim_reverse', $date, $reason);
            if ($claim->status !== 'posted' || $claim->settled_amount > 0 || $date < $claim->accounting_date->toDateString()) {
                SponsorFinanceSupport::fail('Reverse settlements first and use a correction date on or after recognition.');
            }
            if (! $claim->journal_batch_id) {
                $claim->update(['status'=>'reversed','reversed_on'=>$date,'active_identity'=>null]);
                SponsorFinanceSupport::audit($claim,'opening_link_released',['reason'=>$reason,'opening_journal_batch_id'=>$claim->opening_journal_batch_id,
                    'note'=>'Original GL opening asset is retained; reconcile the unallocated control with the verified replacement claim.']);
                return;
            }
            $original = JournalBatch::where('school_id', $claim->school_id)->findOrFail($claim->journal_batch_id);
            $batch = app(AccountingService::class)->createJournalBatch(['school_id' => $claim->school_id, 'transaction_date' => $date,
                'reference_number' => 'REV-CLAIM-'.$claim->id, 'description' => $reason, 'source_type' => 'sponsor_claim_reversal', 'source_id' => $claim->id,
                'status' => 'posted', 'created_by' => auth()->id(), 'entries' => $original->entries->map(fn ($entry) => ['account_id' => $entry->account_id,
                    'entry_type' => $entry->entry_type === 'debit' ? 'credit' : 'debit', 'amount' => (float) $entry->amount])->all()]);
            if (in_array($claim->opening_inclusion, ['student_control', 'current_invoice'])) {
                $invoice = $claim->invoice()->lockForUpdate()->firstOrFail();
                $invoice->updateQuietly(['balance' => round((float) $invoice->balance + (float) $claim->opening_outstanding, 2), 'status' => 'unpaid']);
                $ar = app(FinancialAccountResolver::class)->getReceivableAccount($claim->school_id);
                LedgerEntry::create(['school_id' => $claim->school_id, 'student_id' => $claim->student_id, 'invoice_id' => $invoice->id, 'entry_date' => $date,
                    'account_code' => $ar->code, 'type' => 'debit', 'amount' => $claim->opening_outstanding, 'description' => 'Sponsor reclassification reversal '.$claim->reference]);
            }
            $claim->update(['status' => 'reversed', 'reversed_on' => $date, 'reversal_batch_id' => $batch->id,'active_identity'=>null]);
            SponsorFinanceSupport::audit($claim,'claim_reversed',['reason' => $reason, 'journal_batch_id' => $batch->id]);
        });
    }
}
