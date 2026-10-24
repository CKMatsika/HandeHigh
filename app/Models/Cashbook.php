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
        'voucher_number',
        'payment_method',
        'status',
        'related_invoice_id',
        'related_payment_id',
        'created_by',
        'notes',
        'project_id',
        'cost_center_id',
        'reversal_batch_id',
        'reversed_at',
        'reversed_by',
        'classification',
        'student_id',
        'vendor_id',
        'counterparty_name',
        'vote_code',
        'fund_code',
        'offset_account_id',
        'supporting_document_ref',
        'journal_batch_id',
        'currency',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'date',
        'reversed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->category)) {
                $model->category = $model->classification ?? 'general';
            }
            if (!isset($model->balance_after)) {
                $model->balance_after = 0.00;
            }
        });
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function offsetAccount()
    {
        return $this->belongsTo(Account::class, 'offset_account_id');
    }

    public function journalBatch()
    {
        return $this->belongsTo(JournalBatch::class, 'journal_batch_id');
    }

    public function reversalBatch()
    {
        return $this->belongsTo(JournalBatch::class, 'reversal_batch_id');
    }

    public function reverser()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(Department::class, 'cost_center_id');
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

    public function getDebitAttribute(): float
    {
        if (in_array(strtolower($this->transaction_type ?? ''), ['income', 'debit'])) {
            return (float) $this->amount;
        }
        return 0.0;
    }

    public function getCreditAttribute(): float
    {
        if (in_array(strtolower($this->transaction_type ?? ''), ['expense', 'credit'])) {
            return (float) $this->amount;
        }
        return 0.0;
    }

    public function getRunningBalanceAttribute(): float
    {
        if ($this->balance_after !== null && (float) $this->balance_after != 0.0) {
            return (float) $this->balance_after;
        }
        return (float) ($this->balance_after ?? $this->amount ?? 0.0);
    }

    public function getReferenceAttribute(): ?string
    {
        if (!empty($this->reference_number)) {
            return $this->reference_number;
        }
        if ($this->relatedPayment && !empty($this->relatedPayment->reference)) {
            return $this->relatedPayment->reference;
        }
        if ($this->related_payment_id) {
            return 'PAY-' . $this->related_payment_id;
        }
        if ($this->category === 'opening_balance') {
            return 'OP-BAL' . ($this->account_id ? '-' . $this->account_id : '');
        }
        if ($this->relatedInvoice && !empty($this->relatedInvoice->number)) {
            return $this->relatedInvoice->number;
        }
        if (preg_match('/(INV-[\w-]+)/i', $this->description ?? '', $matches)) {
            return $matches[1];
        }
        return null;
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
