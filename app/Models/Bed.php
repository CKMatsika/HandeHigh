<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bed extends Model
{
    protected $fillable = [
        'dormitory_id',
        'bed_number',
        'description',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function dormitory(): BelongsTo
    {
        return $this->belongsTo(Dormitory::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(BedAssignment::class);
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(BedAssignment::class)->where('is_current', true);
    }

    public function currentStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class)->through('currentAssignment');
    }
}
