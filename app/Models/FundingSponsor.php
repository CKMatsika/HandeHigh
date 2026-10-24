<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundingSponsor extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public function claims()
    {
        return $this->hasMany(SponsorClaim::class);
    }

    public function scholarships()
    {
        return $this->hasMany(StudentScholarship::class);
    }

    public function settlements()
    {
        return $this->hasMany(SponsorSettlement::class);
    }
}
