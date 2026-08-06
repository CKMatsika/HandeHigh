<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Qualification extends Model
{
    protected $fillable = [
        'school_id',
        'qualifiable_id',
        'qualifiable_type',
        'name',
        'institution',
        'grade',
        'year_start',
        'year_end',
        'document_url',
        'notes',
    ];

    protected $casts = [
        'year_start' => 'integer',
        'year_end' => 'integer',
    ];

    public function qualifiable()
    {
        return $this->morphTo();
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
