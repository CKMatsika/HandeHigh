<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Hostel extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'gender',
        'supervisor_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function dormitories(): HasMany
    {
        return $this->hasMany(Dormitory::class);
    }

    public function beds(): HasManyThrough
    {
        return $this->hasManyThrough(Bed::class, Dormitory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    public function getTotalCapacityAttribute(): int
    {
        return (int) $this->dormitories()->sum('capacity');
    }

    public function getOccupiedBedsCountAttribute(): int
    {
        return $this->dormitories->sum(fn ($dorm) => $dorm->occupied_count);
    }

    public function getAvailableBedsCountAttribute(): int
    {
        return $this->dormitories->sum(fn ($dorm) => $dorm->available_count);
    }
}
