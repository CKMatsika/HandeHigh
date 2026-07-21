<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'bank_account_id',
        'reconciliation_date',
        'book_balance',
        'bank_balance',
        'reconciled_balance',
        'matched_items',
        'unmatched_items',
        'notes',
        'status',
        'reconciled_by',
    ];

    protected $casts = [
        'reconciliation_date' => 'date',
        'book_balance' => 'decimal:2',
        'bank_balance' => 'decimal:2',
        'reconciled_balance' => 'decimal:2',
        'matched_items' => 'array',
        'unmatched_items' => 'array',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(Account::class);
    }

    public function reconciler()
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }
}
