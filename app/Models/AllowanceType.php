<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AllowanceType extends Model
{
    protected $fillable = [
        'school_id', 'name', 'category', 'description',
        'is_taxable', 'is_pensionable', 'is_active',
    ];

    protected $casts = [
        'is_taxable' => 'boolean',
        'is_pensionable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function employeeAllowances(): HasMany { return $this->hasMany(EmployeeAllowance::class); }
}
