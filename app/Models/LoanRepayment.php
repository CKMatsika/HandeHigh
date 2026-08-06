<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRepayment extends Model
{
    protected $fillable = [
        'loan_id', 'payroll_item_id', 'amount', 'payment_date',
        'payment_method', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function loan(): BelongsTo { return $this->belongsTo(Loan::class); }
    public function payrollItem(): BelongsTo { return $this->belongsTo(PayrollItem::class); }
}
