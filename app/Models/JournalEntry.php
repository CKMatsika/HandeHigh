<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_batch_id',
        'account_id',
        'entry_type',
        'amount',
        'memo',
        'cost_center_id',
        'project_id',
        'reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function batch()
    {
        return $this->belongsTo(JournalBatch::class, 'journal_batch_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(Department::class, 'cost_center_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeDebits($query)
    {
        return $query->where('entry_type', 'debit');
    }

    public function scopeCredits($query)
    {
        return $query->where('entry_type', 'credit');
    }

    public function scopeByAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }
}
