<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dormitory extends Model
{
    protected $fillable = [
        'school_id',
        'hostel_id',
        'name',
        'gender',
        'capacity',
        'description',
        'prefect_id',
        'supervisor_id',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function prefect(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'prefect_id');
    }

    public function beds(): HasMany
    {
        return $this->hasMany(Bed::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasManyThrough(BedAssignment::class, Bed::class);
    }

    public function getOccupiedCountAttribute(): int
    {
        return $this->beds()->whereHas('currentAssignment')->count();
    }

    public function getAvailableCountAttribute(): int
    {
        return $this->beds()->where('is_available', true)->whereDoesntHave('currentAssignment')->count();
    }
}
