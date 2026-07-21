<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SdaRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_executive',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_executive' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the committee members with this role.
     */
    public function committeeMembers()
    {
        return $this->hasMany(SdaCommitteeMember::class);
    }

    /**
     * Scope a query to only include executive roles.
     */
    public function scopeExecutive($query)
    {
        return $query->where('is_executive', true);
    }

    /**
     * Scope a query to only include active roles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get roles ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
