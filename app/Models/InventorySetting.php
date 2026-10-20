<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventorySetting extends Model
{
    protected $fillable = [
        'school_id',
        'valuation_method',
        'asset_capitalization_threshold',
    ];

    protected $casts = [
        'asset_capitalization_threshold' => 'decimal:2',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
