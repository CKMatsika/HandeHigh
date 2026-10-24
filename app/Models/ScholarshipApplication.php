<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScholarshipApplication extends Model
{
    protected static function booted(): void
    {
        static::updating(function($application){if($application->getRawOriginal('journal_batch_id') && $application->isDirty(['school_id','invoice_id','student_scholarship_id','amount','lines','award_snapshot','accounting_date','journal_batch_id'])){throw new \LogicException('Posted coverage terms are immutable.');}});
        static::deleting(fn($application)=>throw new \LogicException('Coverage history cannot be deleted.'));
    }
    protected $guarded = ['id'];
    protected $casts = [
        'lines' => 'array', 'award_snapshot' => 'array', 'amount' => 'decimal:2',
        'accounting_date' => 'date', 'reversed_on' => 'date',
    ];

    public function scholarship() { return $this->belongsTo(StudentScholarship::class, 'student_scholarship_id'); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function journalBatch() { return $this->belongsTo(JournalBatch::class); }
    public function sponsorReceipts() { return $this->hasMany(ScholarshipSponsorReceipt::class); }
    public function getSponsorBalanceAttribute(): float
    {
        if (($this->award_snapshot['accounting_treatment'] ?? '') !== 'sponsor_receivable' || $this->status !== 'posted') { return 0.0; }
        $consolidated = (float) SponsorSettlementAllocation::where('school_id',$this->school_id)->where('target_type','scholarship')->where('target_id',$this->id)
            ->whereHas('settlement',fn($q)=>$q->where('status','posted'))->sum('amount');
        return round((float) $this->amount - (float) $this->sponsorReceipts()->whereNull('reversed_on')->sum('amount') - $consolidated, 2);
    }
}
