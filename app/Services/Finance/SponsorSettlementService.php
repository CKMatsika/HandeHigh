<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\FundingSponsor;
use App\Models\ScholarshipApplication;
use App\Models\School;
use App\Models\SponsorClaim;
use App\Models\SponsorSettlement;
use App\Models\SponsorSettlementAllocation;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SponsorSettlementService
{
    public function prepare(FundingSponsor $sponsor, array $input): SponsorSettlement
    {
        $maker = SponsorFinanceSupport::actor($sponsor->school_id);
        $data = Validator::make($input, ['reference' => 'required|string|max:100', 'received_on' => 'required|date',
            'amount' => 'required|numeric|min:0.01', 'bank_account_id' => 'required|integer', 'cash_received' => 'required|accepted',
            'supporting_reference' => 'required|string|max:255', 'allocations' => 'required|array|min:1',
            'allocations.*.target_type' => 'required|in:claim,scholarship', 'allocations.*.target_id' => 'required|integer',
            'allocations.*.amount' => 'required|numeric|min:0.01'])->validate();

        return DB::transaction(function () use ($sponsor, $data, $maker) {
            School::whereKey($sponsor->school_id)->lockForUpdate()->firstOrFail();
            $bank = Account::where('school_id', $sponsor->school_id)->whereIn('category', ['bank', 'cash'])->where('is_active', true)->where('is_postable', true)->findOrFail($data['bank_account_id']);
            if (! $bank->currency) {
                SponsorFinanceSupport::fail('Configure the bank ledger currency before receiving funds.');
            }
            if (SponsorFinanceSupport::cents(array_sum(array_column($data['allocations'], 'amount'))) !== SponsorFinanceSupport::cents($data['amount'])) {
                SponsorFinanceSupport::fail('Settlement allocations must equal the money actually received.');
            }
            $existing = SponsorSettlement::where('school_id', $sponsor->school_id)->where('reference', $data['reference'])->first();
            if ($existing) {
                $existingLines = $existing->allocations()->orderBy('target_type')->orderBy('target_id')->get(['target_type', 'target_id', 'amount'])->toArray();
                $expectedLines = collect($data['allocations'])->sortBy(fn ($a) => $a['target_type'].'-'.str_pad($a['target_id'], 20, '0', STR_PAD_LEFT))->values();
                if ($existing->funding_sponsor_id !== $sponsor->id || SponsorFinanceSupport::cents($existing->amount) !== SponsorFinanceSupport::cents($data['amount'])
                    || $existing->received_on->toDateString() !== $data['received_on'] || $existing->bank_account_id !== (int) $data['bank_account_id']
                    || json_encode(collect($existingLines)->map(fn ($a) => [$a['target_type'], (int) $a['target_id'], SponsorFinanceSupport::cents($a['amount'])]))
                    !== json_encode($expectedLines->map(fn ($a) => [$a['target_type'], (int) $a['target_id'], SponsorFinanceSupport::cents($a['amount'])]))) {
                    SponsorFinanceSupport::fail('Receipt reference is already used with different terms.');
                }

                return $existing;
            }
            $settlement = SponsorSettlement::create(['school_id' => $sponsor->school_id, 'funding_sponsor_id' => $sponsor->id,
                'reference' => $data['reference'], 'received_on' => $data['received_on'], 'amount' => round((float) $data['amount'], 2),
                'bank_account_id' => $data['bank_account_id'], 'cash_received' => true, 'supporting_reference' => $data['supporting_reference'],
                'created_by' => $maker, 'status' => 'pending', 'currency' => $bank->currency]);
            $seen = [];
            foreach ($data['allocations'] as $line) {
                $key = $line['target_type'].'-'.$line['target_id'];
                if (isset($seen[$key])) {
                    SponsorFinanceSupport::fail('Duplicate target in one settlement.');
                }
                $seen[$key] = true;
                $this->target($sponsor, $line['target_type'], (int) $line['target_id']);
                SponsorSettlementAllocation::create(['school_id' => $sponsor->school_id, 'sponsor_settlement_id' => $settlement->id,
                    'target_type' => $line['target_type'], 'target_id' => $line['target_id'], 'amount' => round((float) $line['amount'], 2)]);
            }
            SponsorFinanceSupport::audit($settlement, 'settlement_prepared', ['amount' => $settlement->amount]);

            return $settlement;
        });
    }

    public function approve(SponsorSettlement $settlement, string $reference): SponsorSettlement
    {
        return DB::transaction(function () use ($settlement, $reference) {
            School::whereKey($settlement->school_id)->lockForUpdate()->firstOrFail();
            $settlement = SponsorSettlement::whereKey($settlement->id)->lockForUpdate()->firstOrFail();
            $checker = SponsorFinanceSupport::checker($settlement->school_id, $settlement->created_by, $reference);
            if ($settlement->status === 'posted') {
                return $settlement;
            }
            if ($settlement->status !== 'pending' || ! $settlement->cash_received) {
                SponsorFinanceSupport::fail('Only confirmed pending receipts can be posted.');
            }
            $credits = [];
            foreach ($settlement->allocations()->orderBy('target_type')->orderBy('target_id')->lockForUpdate()->get() as $line) {
                $target = $this->target($settlement->sponsor, $line->target_type, $line->target_id, true);
                $balance = $line->target_type === 'claim' ? $target->outstanding : $target->sponsor_balance;
                $date = $target->accounting_date;
                if (SponsorFinanceSupport::cents($line->amount) > SponsorFinanceSupport::cents($balance) || $settlement->received_on->lt($date)) {
                    SponsorFinanceSupport::fail('A settlement exceeds the remaining receivable or predates recognition.');
                }
                $accountId = $line->target_type === 'claim' ? $target->receivable_account_id : $target->journalBatch->entries->firstWhere('entry_type', 'debit')->account_id;
                $currency = Account::where('school_id', $settlement->school_id)->whereKey($accountId)->value('currency');
                if (strtoupper((string) $currency) !== strtoupper($settlement->currency)) {
                    SponsorFinanceSupport::fail('Bank and sponsor ledger currencies differ; an approved conversion workflow is required.');
                }
                $credits[$accountId] = round(($credits[$accountId] ?? 0) + (float) $line->amount, 2);
                $line->update(['receivable_account_id' => $accountId]);
            }
            $bank = Account::where('school_id', $settlement->school_id)->whereIn('category', ['bank', 'cash'])->where('is_active', true)->where('is_postable', true)->lockForUpdate()->findOrFail($settlement->bank_account_id);
            if (strtoupper((string) $bank->currency) !== strtoupper($settlement->currency)) {
                SponsorFinanceSupport::fail('Bank currency changed after preparation. Prepare a corrected receipt.');
            }
            $entries = [['account_id' => $settlement->bank_account_id, 'entry_type' => 'debit', 'amount' => (float) $settlement->amount]];
            foreach ($credits as $account => $amount) {
                $entries[] = ['account_id' => $account, 'entry_type' => 'credit', 'amount' => $amount];
            }
            $batch = app(AccountingService::class)->createJournalBatch(['school_id' => $settlement->school_id,
                'transaction_date' => $settlement->received_on->toDateString(), 'reference_number' => $settlement->reference,
                'description' => 'Sponsor settlement '.$settlement->reference, 'source_type' => 'sponsor_settlement', 'source_id' => $settlement->id,
                'status' => 'posted', 'created_by' => $checker, 'entries' => $entries]);
            $settlement->update(['status' => 'posted', 'approved_by' => $checker, 'approval_reference' => $reference, 'journal_batch_id' => $batch->id]);
            SponsorFinanceSupport::cashbook($batch, $settlement->bank_account_id, (float) $settlement->amount, $settlement->received_on->toDateString(), $settlement->reference);
            SponsorFinanceSupport::audit($settlement, 'settlement_posted', ['journal_batch_id' => $batch->id, 'amount' => $settlement->amount]);

            return $settlement;
        });
    }

    private function target(FundingSponsor $sponsor, string $type, int $id, bool $lock = false)
    {
        $query = $type === 'claim' ? SponsorClaim::where('funding_sponsor_id', $sponsor->id)
            : ScholarshipApplication::whereHas('scholarship', fn ($q) => $q->where('funding_sponsor_id', $sponsor->id));
        $query->where('school_id', $sponsor->school_id);
        if ($lock) {
            $query->lockForUpdate();
        }
        $target = $query->findOrFail($id);
        if ($target->status !== 'posted' || ($type === 'scholarship' && ($target->award_snapshot['accounting_treatment'] ?? '') !== 'sponsor_receivable')) {
            SponsorFinanceSupport::fail('Only recognised outstanding sponsor obligations can be settled.');
        }

        return $target;
    }

    public function reverse(SponsorSettlement $settlement, string $date, string $reason, ?int $approvalId = null): void
    {
        DB::transaction(function () use ($settlement, $date, $reason, $approvalId) {
            School::whereKey($settlement->school_id)->lockForUpdate()->firstOrFail();
            $settlement = SponsorSettlement::whereKey($settlement->id)->lockForUpdate()->firstOrFail();
            if ($settlement->status === 'reversed') {
                return;
            }
            app(ScholarshipApprovalService::class)->authorize($approvalId, $settlement, 'settlement_reverse', $date, $reason);
            if ($settlement->status !== 'posted' || $date < $settlement->received_on->toDateString()) {
                SponsorFinanceSupport::fail('Invalid settlement correction date or status.');
            }
            $batch = app(AccountingService::class)->createJournalBatch(['school_id' => $settlement->school_id, 'transaction_date' => $date,
                'reference_number' => 'REV-SET-'.$settlement->id, 'description' => $reason, 'source_type' => 'sponsor_settlement_reversal',
                'source_id' => $settlement->id, 'status' => 'posted', 'created_by' => auth()->id(),
                'entries' => $settlement->journalBatch->entries->map(fn ($entry) => ['account_id' => $entry->account_id,
                    'entry_type' => $entry->entry_type === 'debit' ? 'credit' : 'debit', 'amount' => (float) $entry->amount])->all()]);
            $settlement->update(['status' => 'reversed', 'reversed_on' => $date, 'reversal_batch_id' => $batch->id]);
            SponsorFinanceSupport::cashbook($batch, $settlement->bank_account_id, (float) $settlement->amount, $date, 'REV-SET-'.$settlement->id, true);
            SponsorFinanceSupport::audit($settlement,'settlement_reversed',['reason' => $reason, 'journal_batch_id' => $batch->id]);
        });
    }
}
