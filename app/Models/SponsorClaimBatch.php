<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SponsorClaimBatch extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['submitted_on' => 'date'];

    public function claims()
    {
        return $this->hasMany(SponsorClaim::class);
    }

    public function sponsor()
    {
        return $this->belongsTo(FundingSponsor::class, 'funding_sponsor_id');
    }

    public function migrationBatch()
    {
        return $this->belongsTo(MigrationBatch::class);
    }
}
