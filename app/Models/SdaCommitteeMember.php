<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SdaCommitteeMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sda_committee_id',
        'sda_role_id',
        'appointment_date',
        'end_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that is the committee member.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the committee this member belongs to.
     */
    public function committee()
    {
        return $this->belongsTo(SdaCommittee::class, 'sda_committee_id');
    }

    /**
     * Get the role of this committee member.
     */
    public function role()
    {
        return $this->belongsTo(SdaRole::class, 'sda_role_id');
    }

    /**
     * Scope a query to only include active members.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include current members (no end date or end date in future).
     */
    public function scopeCurrent($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('end_date')
              ->orWhere('end_date', '>=', now());
        });
    }

    /**
     * Scope a query to only include executive members.
     */
    public function scopeExecutive($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->where('is_executive', true);
        });
    }

    /**
     * Check if the member has financial approval authority.
     */
    public function hasFinancialApproval()
    {
        return $this->committee->requires_financial_approval && 
               $this->role->is_executive;
    }

    /**
     * Check if the member has procurement approval authority.
     */
    public function hasProcurementApproval()
    {
        return $this->committee->requires_procurement_approval && 
               $this->role->is_executive;
    }

    /**
     * Get the full title of the member (role + committee).
     */
    public function getFullTitleAttribute()
    {
        return "{$this->role->name} - {$this->committee->name}";
    }
}
