<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatutoryRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'rate_type',
        'currency',
        'bracket_min',
        'bracket_max',
        'rate_percentage',
        'flat_amount',
        'sector_code',
        'effective_from',
        'effective_to',
        'is_active',
        'description',
    ];

    protected $casts = [
        'bracket_min' => 'decimal:2',
        'bracket_max' => 'decimal:2',
        'rate_percentage' => 'decimal:4',
        'flat_amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEffective($query, $date = null)
    {
        $date = $date ? \Carbon\Carbon::parse($date)->toDateString() : now()->toDateString();
        return $query->where(function ($q) use ($date) {
            $q->whereNull('effective_from')
              ->orWhere('effective_from', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('effective_to')
              ->orWhere('effective_to', '>=', $date);
        });
    }

    public function scopeForCurrency($query, string $currency)
    {
        return $query->where(function ($q) use ($currency) {
            $q->where('currency', strtoupper($currency))
              ->orWhere('currency', 'ALL');
        });
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('rate_type', $type);
    }
}
