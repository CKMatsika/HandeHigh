<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolHouse extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'color',
        'emoji',
        'description',
        'captain_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function captain(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'captain_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'house_id');
    }
}
