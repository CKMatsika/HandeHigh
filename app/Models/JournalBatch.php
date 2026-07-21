<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'batch_number',
        'transaction_date',
        'reference_number',
        'description',
        'source_type',
        'source_id',
        'status',
        'created_by',
        'approved_by',
        'posted_at',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function entries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function isBalanced()
    {
        $debitTotal = $this->entries()->where('entry_type', 'debit')->sum('amount');
        $creditTotal = $this->entries()->where('entry_type', 'credit')->sum('amount');
        
        return abs($debitTotal - $creditTotal) < 0.01; // Allow for floating point precision
    }

    public function getDebitTotalAttribute()
    {
        return $this->entries()->where('entry_type', 'debit')->sum('amount');
    }

    public function getCreditTotalAttribute()
    {
        return $this->entries()->where('entry_type', 'credit')->sum('amount');
    }
}
