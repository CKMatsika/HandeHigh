<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'type',
        'category',
        'parent_id',
        'opening_balance',
        'currency',
        'is_active',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id')->orderBy('sort_order');
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function balances()
    {
        return $this->hasMany(AccountBalance::class);
    }

    public function budgetLines()
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function cashbookEntries()
    {
        return $this->hasMany(Cashbook::class, 'account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function getCurrentBalanceAttribute()
    {
        // Calculate balance from journal entries
        $debits = $this->journalEntries()->where('entry_type', 'debit')->sum('amount');
        $credits = $this->journalEntries()->where('entry_type', 'credit')->sum('amount');
        
        // For assets and expenses: balance = debits - credits
        // For liabilities, equity, and revenue: balance = credits - debits
        if (in_array($this->type, ['asset', 'expense'])) {
            return $debits - $credits;
        }
        
        return $credits - $debits;
    }

    public function getFormattedBalanceAttribute()
    {
        return number_format($this->current_balance, 2);
    }
}
