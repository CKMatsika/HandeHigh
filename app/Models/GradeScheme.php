<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeScheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'level',
        'description',
        'effective_from',
        'effective_to',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function bands(): HasMany
    {
        return $this->hasMany(GradeBand::class)->orderBy('display_order')->orderByDesc('min_percentage');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(PerformanceReport::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForLevel($query, string $level)
    {
        return $query->where('level', $level);
    }
}
