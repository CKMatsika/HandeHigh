<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'description',
        'start_date',
        'end_date',
        'budget_amount',
        'status',
        'project_type',
        'revenue_account_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget_amount' => 'decimal:2',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function revenueAccount()
    {
        return $this->belongsTo(Account::class, 'revenue_account_id');
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function budgetLines()
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class)->orderBy('phase_milestone')->orderBy('created_at');
    }

    public function getTasksCountAttribute(): int
    {
        return $this->tasks()->count();
    }

    public function getCompletedTasksCountAttribute(): int
    {
        return $this->tasks()->where('status', 'completed')->count();
    }

    public function getProgressPercentageAttribute(): int
    {
        $total = $this->tasks()->count();
        if ($total === 0) {
            return $this->status === 'completed' ? 100 : 0;
        }

        $completed = $this->tasks()->where('status', 'completed')->count();
        return (int) round(($completed / $total) * 100);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function billItems()
    {
        return $this->hasMany(BillItem::class);
    }

    public function receiptItems()
    {
        return $this->hasMany(ReceiptItem::class);
    }

    public function cashbookEntries()
    {
        return $this->hasMany(Cashbook::class);
    }

    public function getTotalSpentAttribute()
    {
        // Calculate total expenses for this project from GL
        return (float) $this->journalEntries()
            ->whereHas('account', function($q) {
                $q->where('type', 'expense');
            })
            ->where('entry_type', 'debit')
            ->sum('amount');
    }

    public function getTotalRevenueAttribute()
    {
        // Calculate total revenue for this project from GL
        return (float) $this->journalEntries()
            ->whereHas('account', function($q) {
                $q->where('type', 'revenue');
            })
            ->where('entry_type', 'credit')
            ->sum('amount');
    }

    public function getNetProfitLossAttribute()
    {
        return $this->total_revenue - $this->total_spent;
    }

    public function getRemainingBudgetAttribute()
    {
        return max(0, $this->budget_amount - $this->total_spent);
    }
}
