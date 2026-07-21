<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashbookTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'account_id',
        'transaction_date',
        'reference_number',
        'description',
        'amount',
        'transaction_type',
        'category',
        'journal_entry_id',
        'status',
        'matched_with',
        'matched_amount',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'matched_amount' => 'decimal:2',
        'matched_with' => 'array',
    ];

    // Relationships
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function bankMatches()
    {
        return $this->belongsToMany(BankTransaction::class, 'transaction_matches')
            ->withPivot('match_amount', 'match_type', 'created_by', 'created_at')
            ->withTimestamps();
    }

    // Scopes
    public function scopeUnmatched($query)
    {
        return $query->where('status', 'unmatched');
    }

    public function scopeMatched($query)
    {
        return $query->where('status', 'matched');
    }

    public function scopePartiallyMatched($query)
    {
        return $query->where('status', 'partially_matched');
    }

    public function scopeByAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    // Methods
    public function getUnmatchedAmountAttribute()
    {
        return $this->amount - $this->matched_amount;
    }

    public function isFullyMatched()
    {
        return $this->matched_amount >= $this->amount;
    }

    public function updateMatchStatus()
    {
        if ($this->matched_amount >= $this->amount) {
            $this->status = 'matched';
        } elseif ($this->matched_amount > 0) {
            $this->status = 'partially_matched';
        } else {
            $this->status = 'unmatched';
        }
        $this->save();
    }

    public function addMatch($bankTransactionId, $amount, $matchType = 'exact')
    {
        $matchedWith = $this->matched_with ?? [];
        $matchedWith[] = [
            'bank_transaction_id' => $bankTransactionId,
            'amount' => $amount,
            'type' => $matchType,
            'matched_at' => now()->toISOString(),
        ];
        
        $this->matched_with = $matchedWith;
        $this->matched_amount += $amount;
        $this->updateMatchStatus();
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }

    public function getTypeLabelAttribute()
    {
        return ucfirst($this->transaction_type);
    }

    public function getCategoryLabelAttribute()
    {
        return match($this->category) {
            'income' => 'Income',
            'expense' => 'Expense',
            'transfer' => 'Transfer',
            'bank_charge' => 'Bank Charge',
            'interest' => 'Interest',
            'other' => 'Other',
            default => ucfirst($this->category),
        };
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'unmatched' => 'Unmatched',
            'matched' => 'Matched',
            'partially_matched' => 'Partially Matched',
            'disputed' => 'Disputed',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'unmatched' => 'red',
            'matched' => 'green',
            'partially_matched' => 'yellow',
            'disputed' => 'orange',
            default => 'gray',
        };
    }
}
