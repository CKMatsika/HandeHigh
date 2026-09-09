<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $fillable = [
        'school_id',
        'period_month',
        'period_year',
        'processed_date',
        'status',
        'total_gross',
        'total_deductions',
        'total_net',
        'total_employer_nssa',
        'total_gross_usd',
        'total_gross_zwg',
        'total_deductions_usd',
        'total_deductions_zwg',
        'total_net_usd',
        'total_net_zwg',
        'total_paye_usd',
        'total_paye_zwg',
        'total_aids_levy_usd',
        'total_aids_levy_zwg',
        'total_employer_nssa_usd',
        'total_employer_nssa_zwg',
        'total_employer_nec_usd',
        'total_employer_nec_zwg',
        'is_locked',
        'locked_at',
        'journal_batch_id',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'processed_date' => 'date',
        'locked_at' => 'datetime',
        'is_locked' => 'boolean',
        'total_gross' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net' => 'decimal:2',
        'total_employer_nssa' => 'decimal:2',
        'total_gross_usd' => 'decimal:2',
        'total_gross_zwg' => 'decimal:2',
        'total_deductions_usd' => 'decimal:2',
        'total_deductions_zwg' => 'decimal:2',
        'total_net_usd' => 'decimal:2',
        'total_net_zwg' => 'decimal:2',
        'total_paye_usd' => 'decimal:2',
        'total_paye_zwg' => 'decimal:2',
        'total_aids_levy_usd' => 'decimal:2',
        'total_aids_levy_zwg' => 'decimal:2',
        'total_employer_nssa_usd' => 'decimal:2',
        'total_employer_nssa_zwg' => 'decimal:2',
        'total_employer_nec_usd' => 'decimal:2',
        'total_employer_nec_zwg' => 'decimal:2',
    ];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function journalBatch(): BelongsTo { return $this->belongsTo(JournalBatch::class); }
    public function items(): HasMany { return $this->hasMany(PayrollItem::class); }

    public function scopeDraft($q) { return $q->where('status', 'draft'); }
    public function scopeLocked($q) { return $q->where('is_locked', true); }
}
