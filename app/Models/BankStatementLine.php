<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankStatementLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_statement_import_id',
        'transaction_date',
        'description',
        'amount',
        'type',
        'balance',
        'reference',
        'fit_id',
        'check_number',
        'reconciliation_status',
        'matched_transaction_id',
        'matched_journal_entry_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function import()
    {
        return $this->belongsTo(BankStatementImport::class);
    }

    public function matchedTransaction()
    {
        return $this->belongsTo(Cashbook::class, 'matched_transaction_id');
    }

    public function matchedJournalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'matched_journal_entry_id');
    }

    public function scopeUnmatched($query)
    {
        return $query->where('reconciliation_status', 'unmatched');
    }

    public function scopeMatched($query)
    {
        return $query->where('reconciliation_status', 'matched');
    }
}
