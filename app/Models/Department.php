<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'head_of_department',
        'budget',
        'is_active',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'cost_center_id');
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'cost_center_id');
    }

    public function billItems(): HasMany
    {
        return $this->hasMany(BillItem::class, 'cost_center_id');
    }

    public function receiptItems(): HasMany
    {
        return $this->hasMany(ReceiptItem::class, 'cost_center_id');
    }

    public function cashbookEntries(): HasMany
    {
        return $this->hasMany(Cashbook::class, 'cost_center_id');
    }

    public function getTotalRevenueAttribute(): float
    {
        return (float) $this->journalEntries()
            ->whereHas('account', fn($q) => $q->where('type', 'revenue'))
            ->where('entry_type', 'credit')
            ->sum('amount');
    }

    public function getTotalExpenditureAttribute(): float
    {
        return (float) $this->journalEntries()
            ->whereHas('account', fn($q) => $q->where('type', 'expense'))
            ->where('entry_type', 'debit')
            ->sum('amount');
    }

    public function getNetProfitLossAttribute(): float
    {
        return $this->total_revenue - $this->total_expenditure;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

