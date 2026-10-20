<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolFixedAssetAssignment extends Model
{
    protected $fillable = [
        'school_fixed_asset_id',
        'from_user_id',
        'to_user_id',
        'assigned_by',
        'action',
        'from_location',
        'to_location',
        'unit_cost',
        'notes',
        'occurred_at',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:4',
        'occurred_at' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(SchoolFixedAsset::class, 'school_fixed_asset_id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
