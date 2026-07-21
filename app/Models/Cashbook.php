<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cashbook extends Model
{
    use HasFactory;

    protected $table = 'cashbook';

    protected $fillable = [
        'school_id',
        'account_id',
        'transaction_type',
        'category',
        'description',
        'amount',
        'balance_after',
        'transaction_date',
        'reference_number',
        'payment_method',
        'related_invoice_id',
        'related_payment_id',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    // Add alias for consistency
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function relatedInvoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function relatedPayment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFormattedAmountAttribute()
    {
        $prefix = $this->transaction_type === 'expense' ? '-' : '+';
        return $prefix . number_format($this->amount, 2);
    }

    public function scopeIncome($query)
    {
        return $query->where('transaction_type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('transaction_type', 'expense');
    }

    public function scopeTransfer($query)
    {
        return $query->where('transaction_type', 'transfer');
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }
}
