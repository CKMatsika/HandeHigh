<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SdaCommittee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'mandate',
        'chairman_title',
        'requires_financial_approval',
        'requires_procurement_approval',
        'is_active',
    ];

    protected $casts = [
        'requires_financial_approval' => 'boolean',
        'requires_procurement_approval' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the members of this committee.
     */
    public function members()
    {
        return $this->hasMany(SdaCommitteeMember::class);
    }

    /**
     * Get active members of this committee.
     */
    public function activeMembers()
    {
        return $this->members()->where('is_active', true);
    }

    /**
     * Get the chairman of this committee.
     */
    public function chairman()
    {
        return $this->activeMembers()
            ->whereHas('role', function ($query) {
                $query->where('slug', 'chairman');
            })
            ->first();
    }

    /**
     * Get committee members with executive roles.
     */
    public function executiveMembers()
    {
        return $this->activeMembers()
            ->whereHas('role', function ($query) {
                $query->where('is_executive', true);
            })
            ->get();
    }

    /**
     * Scope a query to only include active committees.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to committees that require financial approval.
     */
    public function scopeRequiresFinancialApproval($query)
    {
        return $query->where('requires_financial_approval', true);
    }

    /**
     * Scope a query to committees that require procurement approval.
     */
    public function scopeRequiresProcurementApproval($query)
    {
        return $query->where('requires_procurement_approval', true);
    }

    /**
     * Check if a user is a member of this committee.
     */
    public function hasMember($userId)
    {
        return $this->activeMembers()->where('user_id', $userId)->exists();
    }

    /**
     * Get a user's role in this committee.
     */
    public function getUserRole($userId)
    {
        return $this->activeMembers()
            ->where('user_id', $userId)
            ->with('role')
            ->first()?->role;
    }
}
