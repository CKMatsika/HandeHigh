<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SponsorClaim extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['fee_votes' => 'array', 'allocated_lines' => 'array', 'accounting_date' => 'date', 'reversed_on' => 'date', 'submitted_on' => 'date',
        'approved_amount' => 'decimal:2', 'recognised_amount' => 'decimal:2', 'received_before_cutover' => 'decimal:2',
        'opening_outstanding' => 'decimal:2', 'disputed_amount' => 'decimal:2', 'rejected_amount' => 'decimal:2'];

    public function batch()
    {
        return $this->belongsTo(SponsorClaimBatch::class, 'sponsor_claim_batch_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function sponsor()
    {
        return $this->belongsTo(FundingSponsor::class, 'funding_sponsor_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getSettledAmountAttribute(): float
    {
        return (float) SponsorSettlementAllocation::where('school_id', $this->school_id)->where('target_type', 'claim')->where('target_id', $this->id)
            ->whereHas('settlement', fn ($q) => $q->where('status', 'posted'))->sum('amount');
    }

    public function getOutstandingAttribute(): float
    {
        return $this->status === 'posted' ? round((float) $this->opening_outstanding - $this->settled_amount, 2) : 0.0;
    }

    protected static function booted(): void
    {
        static::updating(function ($claim) {
            if($claim->getRawOriginal('status')==='posted'&&$claim->isDirty('active_identity')&&$claim->status!=='reversed'){throw new \LogicException('An active claim identity is released only by controlled reversal.');}
            if (in_array($claim->getRawOriginal('status'), ['posted', 'reversed']) && $claim->isDirty(['school_id', 'funding_sponsor_id', 'reference', 'approved_amount', 'recognised_amount', 'received_before_cutover', 'opening_outstanding', 'student_id', 'fee_votes', 'allocated_lines', 'receivable_account_id', 'academic_year', 'classification', 'opening_inclusion', 'currency', 'submitted_on', 'recognition_evidence', 'supporting_reference'])) {
                throw new \LogicException('Posted claim terms are immutable; use an approved correction.');
            }
        });
        static::deleting(fn ($claim) => throw new \LogicException('Claim history cannot be deleted.'));
    }
}
