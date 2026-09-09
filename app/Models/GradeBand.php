<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeBand extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_scheme_id',
        'grade',
        'min_percentage',
        'max_percentage',
        'description',
        'is_pass',
        'display_order',
    ];

    protected $casts = [
        'min_percentage' => 'decimal:2',
        'max_percentage' => 'decimal:2',
        'is_pass' => 'boolean',
        'display_order' => 'integer',
    ];

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(GradeScheme::class, 'grade_scheme_id');
    }
}
