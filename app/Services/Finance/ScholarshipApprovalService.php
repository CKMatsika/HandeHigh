<?php

namespace App\Services\Finance;

use App\Models\ScholarshipApplication;
use App\Models\ScholarshipApproval;
use App\Models\ScholarshipSponsorReceipt;
use App\Models\SponsorClaim;
use App\Models\SponsorSettlement;
use Illuminate\Support\Facades\DB;

class ScholarshipApprovalService
{
    public function request($subject, string $action, string $date, string $reason): ScholarshipApproval
    {
        $actor = SponsorFinanceSupport::actor($subject->school_id);
        $allowed = ['scholarship_reverse' => ScholarshipApplication::class, 'sponsor_receipt_reverse' => ScholarshipSponsorReceipt::class,
            'claim_reverse' => SponsorClaim::class, 'settlement_reverse' => SponsorSettlement::class];
        if (($allowed[$action] ?? null) !== $subject::class || ! strtotime($date) || trim($reason) === '') {
            SponsorFinanceSupport::fail('Select a valid correction, date and reason.');
        }

        return DB::transaction(function () use ($subject, $action, $date, $reason, $actor) {
            $request = ScholarshipApproval::create(['school_id' => $subject->school_id, 'action' => $action, 'subject_type' => $subject::class,
                'subject_id' => $subject->id, 'payload' => ['date' => $date, 'reason' => $reason], 'requested_by' => $actor, 'status' => 'pending']);
            SponsorFinanceSupport::audit($request, 'correction_requested', ['subject' => $subject->id, 'reason' => $reason]);

            return $request;
        });
    }

    public function approve(ScholarshipApproval $request, string $reference): ScholarshipApproval
    {
        return DB::transaction(function () use ($request, $reference) {
            $request = ScholarshipApproval::whereKey($request->id)->lockForUpdate()->firstOrFail();
            $checker = SponsorFinanceSupport::checker($request->school_id, $request->requested_by, $reference);
            if ($request->status === 'executed') {
                return $request;
            }
            if ($request->status !== 'pending') {
                SponsorFinanceSupport::fail('Correction is not pending.');
            }
            $request->update(['status' => 'approved', 'approved_by' => $checker, 'approval_reference' => $reference]);
            $subject = $request->subject_type::where('school_id', $request->school_id)->findOrFail($request->subject_id);
            $data = $request->payload;
            match ($request->action) {
                'scholarship_reverse' => app(ScholarshipService::class)->reverse($subject, $data['date'], $data['reason'], $request->id),
                'sponsor_receipt_reverse' => app(ScholarshipService::class)->reverseSponsor($subject, $data['date'], $data['reason'], $request->id),
                'claim_reverse' => app(SponsorClaimService::class)->reverse($subject, $data['date'], $data['reason'], $request->id),
                'settlement_reverse' => app(SponsorSettlementService::class)->reverse($subject, $data['date'], $data['reason'], $request->id),
            };
            $request->update(['status' => 'executed', 'executed_at' => now()]);
            SponsorFinanceSupport::audit($request, 'correction_approved', ['reference' => $reference]);

            return $request;
        });
    }

    public function authorize(?int $id, $subject, string $action, string $date, string $reason): void
    {
        $request = $id ? ScholarshipApproval::where('school_id', $subject->school_id)->whereKey($id)->lockForUpdate()->first() : null;
        if (! $request || $request->status !== 'approved' || $request->action !== $action || $request->subject_type !== $subject::class
            || (int) $request->subject_id !== (int) $subject->id || (int) $request->approved_by !== (int) auth()->id()
            || $request->requested_by === $request->approved_by || $request->payload !== ['date' => $date, 'reason' => $reason]) {
            SponsorFinanceSupport::fail('A separately requested and checked correction is required.');
        }
    }
}
