<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentScholarship extends Model
{
    protected static function booted(): void
    {
        static::updating(function($award){if(in_array($award->getRawOriginal('status'),['approved','closed']) && $award->isDirty(['school_id','student_id','reference','name','sponsor','funding_sponsor_id','academic_year','coverage_type','coverage_basis','coverage_value','covered_votes','starts_on','ends_on','accounting_treatment','recognition_evidence'])){throw new \LogicException('Approved award terms are immutable.');}});
        static::deleting(fn($award)=>throw new \LogicException('Award history cannot be deleted.'));
    }
    protected $guarded = ['id'];
    protected $casts = [
        'covered_votes' => 'array', 'coverage_value' => 'decimal:2',
        'starts_on' => 'date', 'ends_on' => 'date', 'approved_at' => 'datetime',
    ];

    public function student() { return $this->belongsTo(Student::class); }
    public function applications() { return $this->hasMany(ScholarshipApplication::class); }
    public function events() { return $this->hasMany(AuditLog::class, 'model_id')->where('model_type', self::class)->orderByDesc('id'); }
    public function sponsorRecord() { return $this->belongsTo(FundingSponsor::class, 'funding_sponsor_id'); }
    public function getUtilisedAmountAttribute(): float { return (float)$this->applications()->where('status','posted')->sum('amount'); }
    public function getRemainingAmountAttribute(): ?float { return $this->coverage_basis==='amount' ? max(0,round((float)$this->coverage_value-$this->utilised_amount,2)) : null; }
    public function getSettledAmountAttribute(): float
    {
        if ($this->accounting_treatment==='school_remission') { return $this->utilised_amount; }
        return round($this->applications()->where('status','posted')->get()->sum(fn($application)=>(float)$application->amount-$application->sponsor_balance),2);
    }
}
