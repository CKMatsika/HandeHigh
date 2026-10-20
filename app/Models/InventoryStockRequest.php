<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryStockRequest extends Model
{
    protected $fillable = [
        'school_id',
        'request_number',
        'requested_by',
        'department_id',
        'needed_by',
        'purpose',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'submitted_at',
    ];

    protected $casts = [
        'needed_by' => 'date',
        'reviewed_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryStockRequestItem::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(InventoryStockIssue::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(InventoryStockRequestReview::class)->orderBy('reviewed_at');
    }
}
