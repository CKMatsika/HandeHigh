<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    protected $fillable = [
        'school_id', 'employee_id', 'loan_type', 'loan_provider',
        'loan_amount', 'interest_rate', 'repayment_period_months',
        'monthly_installment', 'total_paid', 'balance', 'status',
        'disbursed_date', 'first_payment_date', 'settled_date',
        'purpose', 'notes',
    ];

    protected $casts = [
        'loan_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'monthly_installment' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'disbursed_date' => 'date',
        'first_payment_date' => 'date',
        'settled_date' => 'date',
    ];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function repayments(): HasMany { return $this->hasMany(LoanRepayment::class); }

    public function scopeActive($q) { return $q->where('status', 'active'); }
    public function scopeSettled($q) { return $q->where('status', 'settled'); }
}
