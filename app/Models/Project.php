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

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getTotalSpentAttribute()
    {
        // Calculate total expenses for this project
        return $this->journalEntries()
            ->whereHas('account', function($q) {
                $q->where('type', 'expense');
            })
            ->where('entry_type', 'debit')
            ->sum('amount');
    }

    public function getRemainingBudgetAttribute()
    {
        return max(0, $this->budget_amount - $this->total_spent);
    }
}
