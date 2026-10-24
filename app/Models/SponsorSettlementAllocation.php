<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SponsorSettlementAllocation extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['amount' => 'decimal:2'];

    public function settlement()
    {
        return $this->belongsTo(SponsorSettlement::class, 'sponsor_settlement_id');
    }

    protected static function booted(): void
    {
        static::updating(function ($line) {
            if ($line->settlement->status !== 'pending') {
                throw new \LogicException('Posted allocations are immutable.');
            }
        });
        static::deleting(function ($line) {
            if ($line->settlement->status !== 'pending') {
                throw new \LogicException('Posted allocations cannot be deleted.');
            }
        });
    }
}
