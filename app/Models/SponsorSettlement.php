<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SponsorSettlement extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['received_on' => 'date', 'reversed_on' => 'date', 'cash_received' => 'boolean', 'amount' => 'decimal:2'];

    public function allocations()
    {
        return $this->hasMany(SponsorSettlementAllocation::class);
    }

    public function sponsor()
    {
        return $this->belongsTo(FundingSponsor::class, 'funding_sponsor_id');
    }

    public function journalBatch()
    {
        return $this->belongsTo(JournalBatch::class);
    }

    protected static function booted(): void
    {
        static::updating(function ($receipt) {
            if (in_array($receipt->getRawOriginal('status'), ['posted', 'reversed']) && $receipt->isDirty(['school_id', 'amount', 'reference', 'bank_account_id', 'currency', 'received_on', 'funding_sponsor_id', 'cash_received', 'supporting_reference'])) {
                throw new \LogicException('Posted settlement terms are immutable.');
            }
        });
        static::deleting(fn ($receipt) => throw new \LogicException('Settlement history cannot be deleted.'));
    }
}
