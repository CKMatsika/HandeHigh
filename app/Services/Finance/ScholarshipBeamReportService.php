<?php

namespace App\Services\Finance;

use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipSponsorReceipt;
use App\Models\SponsorClaim;
use App\Models\SponsorSettlement;
use App\Models\SponsorSettlementAllocation;
use App\Models\StudentScholarship;
use Carbon\Carbon;

class ScholarshipBeamReportService
{
    public function build(int $schoolId, string $asOf, ?string $year = null): array
    {
        SponsorFinanceSupport::actor($schoolId);
        $date = Carbon::parse($asOf)->toDateString();
        $awards = StudentScholarship::where('school_id', $schoolId)->with(['student', 'applications.journalBatch.entries.account', 'sponsorRecord'])->get();
        $claims = SponsorClaim::where('school_id', $schoolId)->with(['student', 'sponsor', 'batch'])->get();
        $applications = ScholarshipApplication::where('school_id', $schoolId)->whereHas('scholarship', fn ($q) => $q->where('accounting_treatment', 'sponsor_receivable'))
            ->with(['scholarship.student', 'scholarship.sponsorRecord', 'journalBatch.entries.account'])->get();
        $rows = collect();
        foreach ($claims as $claim) {
            $balance = $this->balance($claim, $date, 'claim');
            $basis = $claim->submitted_on ?? $claim->batch->submitted_on ?? $claim->accounting_date;
            $days = $basis ? max(0, (int) $basis->diffInDays(Carbon::parse($date), false)) : null;
            $rows->push(['source' => 'claim', 'id' => $claim->id, 'sponsor' => $claim->sponsor->name, 'sponsor_id' => $claim->funding_sponsor_id,
                'government' => $claim->sponsor->category === 'government', 'academic_year' => $claim->academic_year, 'reference' => $claim->reference,
                'student' => $claim->student->full_name, 'student_id' => $claim->student_id, 'status' => $claim->status,
                'currency' => $claim->currency, 'submitted_on' => $claim->submitted_on?->toDateString(),
                'approved' => (float) $claim->approved_amount, 'recognised' => $claim->receivable_account_id ? (float) $claim->recognised_amount : 0.0,
                'proposed_recognition' => $claim->status === 'recognition_pending' ? (float) $claim->recognised_amount : 0.0,
                'received_before_cutover' => (float) $claim->received_before_cutover, 'outstanding' => $balance,
                'settled_since_cutover' => $this->settledAt($claim,$date,'claim'),
                'disputed' => (float) $claim->disputed_amount, 'rejected' => (float) $claim->rejected_amount, 'account_id' => $claim->receivable_account_id,
                'age_days' => $days, 'age_basis' => $basis?->toDateString(), 'age_bucket' => $days === null ? 'unrecognised' : ($days <= 30 ? '0-30' : ($days <= 60 ? '31-60' : ($days <= 90 ? '61-90' : '90+'))),
                'fee_votes' => $claim->fee_votes, 'supporting_reference' => $claim->supporting_reference]);
        }
        foreach ($applications as $application) {
            $award = $application->scholarship;
            $rows->push(['source' => 'scholarship', 'id' => $application->id, 'sponsor' => $award->sponsorRecord?->name ?? $award->sponsor,
                'sponsor_id' => $award->funding_sponsor_id, 'government' => $award->sponsorRecord?->category === 'government',
                'academic_year' => $award->academic_year ?? $application->invoice->academic_year, 'reference' => $award->reference,
                'student' => $award->student->full_name, 'student_id' => $award->student_id, 'status' => $application->status,
                'approved' => (float) $application->amount, 'recognised' => (float) $application->amount, 'received_before_cutover' => 0,
                'outstanding' => $this->balance($application, $date, 'scholarship'), 'account_id' => $application->journalBatch?->entries->firstWhere('entry_type', 'debit')?->account_id,
                'fee_votes' => $application->lines]);
        }
        $mapped = \App\Models\AccountMapping::where('school_id', $schoolId)->where('is_active', true)
            ->where(fn ($q) => $q->where('mapping_type', 'sponsor_receivable')->orWhere(fn ($q) => $q->where('mapping_type', 'scholarship')->where('source_key', 'SPONSOR_RECEIVABLE')))->pluck('account_id');
        $accountIds = $rows->pluck('account_id')->merge($mapped)->filter()->unique();
        $controls = $accountIds->map(function ($id) use ($schoolId, $date, $rows) {
            $entries = JournalEntry::where('account_id', $id)->whereHas('batch', fn ($q) => $q->where('school_id', $schoolId)->where('status', 'posted')->whereDate('transaction_date', '<=', $date));
            $gl = round((float) (clone $entries)->where('entry_type', 'debit')->sum('amount') - (float) (clone $entries)->where('entry_type', 'credit')->sum('amount'), 2);
            $subledger = round($rows->where('account_id', $id)->sum('outstanding'), 2);

            return ['account_id' => $id, 'account_code' => \App\Models\Account::whereKey($id)->value('code'), 'gl' => $gl, 'subledger' => $subledger, 'difference' => round($gl - $subledger, 2)];
        })->values();
        $beneficiaries = $awards->map(fn ($award) => ['id' => $award->id, 'student' => $award->student->full_name, 'student_id' => $award->student_id,
            'residency' => $award->student->getEffectiveResidency(), 'funding_source' => $award->funding_source ?? ($award->accounting_treatment === 'school_remission' ? 'internal' : 'external'),
            'sponsor' => $award->sponsorRecord?->name ?? $award->sponsor, 'academic_year' => $award->academic_year,
            'reference' => $award->reference, 'status' => $award->status, 'value' => $award->coverage_value, 'basis' => $award->coverage_basis,
            'utilised' => $award->utilised_amount, 'remaining' => $award->remaining_amount, 'settled' => $award->settled_amount, 'covered_votes' => $award->covered_votes]);
        $internal = collect();
        $coverage = collect();
        foreach ($awards as $award) {
            foreach ($award->applications as $a) {
                $events = [[$a->accounting_date, (float) $a->amount, $a->journal_batch_id]];
                if ($a->reversed_on) {
                    $events[] = [$a->reversed_on, -(float) $a->amount, $a->reversal_batch_id];
                }
                foreach ($events as [$eventDate,$amount,$journalId]) {
                    if (! $eventDate || $eventDate->toDateString() > $date) {
                        continue;
                    }
                    if ($award->accounting_treatment === 'school_remission') {
                        $internal->push(['reference' => $award->reference, 'student' => $award->student->full_name,
                            'academic_year' => $award->academic_year, 'amount' => $amount, 'journal_batch_id' => $journalId, 'date' => $eventDate->toDateString(),
                            'account' => $a->journalBatch?->entries->firstWhere('entry_type', 'debit')?->account?->code]);
                    }
                    foreach ($a->lines as $line) {
                        $coverage->push(['reference' => $award->reference, 'academic_year' => $award->academic_year, 'student' => $award->student->full_name,
                            'vote' => $line['vote_code'], 'amount' => ($amount < 0 ? -1 : 1) * (float) $line['amount'], 'invoice_id' => $a->invoice_id, 'date' => $eventDate->toDateString()]);
                    }
                }
            }
        }
        foreach ($claims as $claim) {
            $beneficiaries->push(['id' => $claim->id, 'student' => $claim->student->full_name, 'student_id' => $claim->student_id, 'residency' => $claim->student->getEffectiveResidency(),
                'funding_source' => 'external_claim', 'sponsor' => $claim->sponsor->name, 'academic_year' => $claim->academic_year, 'reference' => $claim->reference, 'status' => $claim->status,
                'value' => $claim->approved_amount, 'basis' => 'approved claim amount', 'utilised' => $claim->receivable_account_id ? (float) $claim->recognised_amount : 0,
                'remaining' => max(0, (float) $claim->approved_amount - ($claim->receivable_account_id ? (float) $claim->recognised_amount : 0)),
                'settled' => (float) $claim->received_before_cutover + $claim->settled_amount, 'covered_votes' => array_keys($claim->fee_votes)]);
            if ($claim->accounting_date && $claim->accounting_date->toDateString() <= $date) {
                foreach ($claim->allocated_lines ?? [] as $line) {
                    $coverage->push(['reference' => $claim->reference, 'academic_year' => $claim->academic_year, 'student' => $claim->student->full_name, 'vote' => $line['vote_code'],
                        'amount' => $line['amount'], 'invoice_id' => $claim->invoice_id, 'date' => $claim->accounting_date->toDateString()]);
                    if ($claim->reversed_on && $claim->reversed_on->toDateString() <= $date) {
                        $coverage->push(['reference' => $claim->reference, 'academic_year' => $claim->academic_year,
                            'student' => $claim->student->full_name, 'vote' => $line['vote_code'], 'amount' => -(float) $line['amount'], 'invoice_id' => $claim->invoice_id, 'date' => $claim->reversed_on->toDateString()]);
                    }
                }
            }
        }
        $payments = SponsorSettlement::where('school_id', $schoolId)->whereDate('received_on', '<=', $date)->with('allocations')->get()->flatMap(fn ($settlement) => $settlement->allocations->map(function ($line) use ($settlement) {
            $target = $line->target_type === 'claim' ? SponsorClaim::where('school_id', $settlement->school_id)->find($line->target_id) : ScholarshipApplication::where('school_id', $settlement->school_id)->find($line->target_id);

            return ['settlement' => $settlement->reference, 'date' => $settlement->received_on->toDateString(), 'status' => $settlement->status,
                'target_type' => $line->target_type, 'target_id' => $line->target_id, 'academic_year' => $line->target_type === 'claim' ? $target?->academic_year : $target?->scholarship->academic_year,
                'student_id' => $line->target_type === 'claim' ? $target?->student_id : $target?->scholarship->student_id, 'amount' => $line->amount,
                'journal_batch_id' => $settlement->journal_batch_id, 'approval_reference' => $settlement->approval_reference];
        }));
        $legacyPayments = ScholarshipSponsorReceipt::where('school_id', $schoolId)->whereDate('received_on', '<=', $date)->with('application.scholarship')->get()->map(fn ($receipt) => [
            'settlement' => $receipt->reference, 'date' => $receipt->received_on->toDateString(), 'status' => $receipt->reversed_on ? 'reversed' : 'posted',
            'target_type' => 'scholarship', 'target_id' => $receipt->scholarship_application_id, 'academic_year' => $receipt->application->scholarship->academic_year,
            'student_id' => $receipt->application->scholarship->student_id, 'amount' => $receipt->amount, 'journal_batch_id' => $receipt->journal_batch_id]);
        $filtered = fn ($collection) => $year ? $collection->where('academic_year', $year)->values() : $collection->values();
        $studentControl = $this->studentControlAt($schoolId, $date);
        $controls->push(['account_id'=>app(FinancialAccountResolver::class)->getReceivableAccount($schoolId)->id,
            'account_code'=>$studentControl['ar_account_code'],'gl'=>$studentControl['gl_ar_balance'],
            'subledger'=>$studentControl['subledger_debtors_balance'],'difference'=>$studentControl['difference']]);

        return ['as_of' => $date, 'beneficiaries' => $filtered($beneficiaries), 'internal_expenditure' => $filtered($internal),
            'external_receivables' => $filtered($rows), 'beam_claims' => $filtered($rows->where('government', true)),
            'beam_ageing' => $filtered($rows->where('government', true)->where('source', 'claim')),
            'payments' => $filtered($payments->concat($legacyPayments)), 'coverage' => $filtered($coverage),
            'controls' => $controls, 'student_control' => $studentControl,
            'student_vs_sponsor' => ['student_outstanding' => $studentControl['subledger_debtors_balance'],
                'sponsor_outstanding' => round($rows->sum('outstanding'), 2), 'note' => 'Separate responsibilities; historical reclassifications reduce student invoices and increase sponsor claims once.'],
            'trial_balance' => app(FinancialReconciliationService::class)->reconcileGeneralLedgerBalance($schoolId, $date)];
    }

    private function studentControlAt(int $schoolId, string $date): array
    {
        $ar = app(FinancialAccountResolver::class)->getReceivableAccount($schoolId);
        $entries = JournalEntry::where('account_id', $ar->id)->whereHas('batch', fn ($q) => $q->where('school_id', $schoolId)->where('status', 'posted')->whereDate('transaction_date', '<=', $date));
        $gl = round((float) (clone $entries)->where('entry_type', 'debit')->sum('amount') - (float) (clone $entries)->where('entry_type', 'credit')->sum('amount'), 2);
        $gross = (float) Invoice::where('school_id', $schoolId)->whereNotIn('status', ['draft', 'cancelled'])->whereDate('issued_at', '<=', $date)->sum('total_amount');
        $paid = (float) \App\Models\PaymentAllocation::whereHas('invoice', fn ($q) => $q->where('school_id', $schoolId)->whereNotIn('status', ['draft', 'cancelled'])->whereDate('issued_at', '<=', $date))
            ->whereHas('payment', fn ($q) => $q->where('school_id', $schoolId)->where('status', 'completed')->whereDate('paid_at', '<=', $date))->sum('amount');
        $credit = (float) \App\Models\CreditNote::where('school_id', $schoolId)->whereIn('status', ['issued', 'applied'])->whereDate('credit_note_date', '<=', $date)->sum('applied_amount');
        $awards = (float) ScholarshipApplication::where('school_id', $schoolId)->whereDate('accounting_date', '<=', $date)
            ->where(fn ($q) => $q->whereNull('reversed_on')->orWhereDate('reversed_on', '>', $date))->sum('amount');
        $claims = (float) SponsorClaim::where('school_id', $schoolId)->whereIn('opening_inclusion', ['student_control', 'current_invoice'])->whereDate('accounting_date', '<=', $date)
            ->where(fn ($q) => $q->whereNull('reversed_on')->orWhereDate('reversed_on', '>', $date))->sum('opening_outstanding');
        $subledger = round($gross - $paid - $credit - $awards - $claims, 2);
        $difference = round($gl - $subledger, 2);

        return ['ar_account_code' => $ar->code, 'gl_ar_balance' => $gl, 'subledger_debtors_balance' => $subledger, 'difference' => $difference, 'is_reconciled' => $difference === 0.0, 'as_of_date' => $date];
    }

    private function balance($target, string $date, string $type): float
    {
        if (! $target->accounting_date || $target->accounting_date->toDateString() > $date || ($target->reversed_on && $target->reversed_on->toDateString() <= $date)) {
            return 0;
        }
        $initial = $type === 'claim' ? (float) $target->opening_outstanding : (float) $target->amount;
        return round($initial-$this->settledAt($target,$date,$type),2);
    }

    private function settledAt($target,string $date,string $type): float
    {
        $settled = (float) SponsorSettlementAllocation::where('school_id', $target->school_id)->where('target_type', $type)->where('target_id', $target->id)
            ->whereHas('settlement', fn ($q) => $q->whereDate('received_on', '<=', $date)->whereNotNull('journal_batch_id')
                ->where(fn ($q) => $q->whereNull('reversed_on')->orWhereDate('reversed_on','>',$date)))->sum('amount');
        if ($type === 'scholarship') {
            $settled += (float) $target->sponsorReceipts()->whereDate('received_on','<=',$date)->where(fn ($q) => $q->whereNull('reversed_on')->orWhereDate('reversed_on','>',$date))->sum('amount');
        }

        return round($settled,2);
    }
}
