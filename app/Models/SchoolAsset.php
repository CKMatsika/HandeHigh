<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolAsset extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'asset_code',
        'category',
        'description',
        'total_quantity',
        'available_quantity',
        'unit_cost',
        'is_active',
    ];

    protected $casts = [
        'total_quantity' => 'integer',
        'available_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(StudentAsset::class);
    }
}
