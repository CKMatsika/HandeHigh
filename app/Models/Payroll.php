<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $fillable = [
        'school_id', 'period_month', 'period_year', 'processed_date',
        'status', 'total_gross', 'total_deductions', 'total_net',
        'total_employer_nssa', 'created_by', 'notes',
    ];

    protected $casts = [
        'processed_date' => 'date',
        'total_gross' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net' => 'decimal:2',
        'total_employer_nssa' => 'decimal:2',
    ];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(PayrollItem::class); }

    public function scopeDraft($q) { return $q->where('status', 'draft'); }
}
