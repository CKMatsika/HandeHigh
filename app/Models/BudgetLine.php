<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'account_id',
        'cost_center_id',
        'project_id',
        'period',
        'budgeted_amount',
        'notes',
    ];

    protected $casts = [
        'budgeted_amount' => 'decimal:2',
        'period' => 'integer',
    ];

    public function budget()
    {
        return $this->belongsTo(Budget::class);
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

    public function getActualAmountAttribute()
    {
        // Calculate actual spending for this account/period
        $query = JournalEntry::whereHas('batch', function($q) {
            $q->where('status', 'posted')
              ->whereYear('transaction_date', $this->budget->fiscal_year);
        })
        ->where('account_id', $this->account_id)
        ->where('entry_type', 'debit');

        if ($this->budget->budget_type === 'monthly' && $this->period) {
            $query->whereMonth('transaction_date', $this->period);
        }

        if ($this->cost_center_id) {
            $query->where('cost_center_id', $this->cost_center_id);
        }

        if ($this->project_id) {
            $query->where('project_id', $this->project_id);
        }

        return $query->sum('amount');
    }

    public function getVarianceAttribute()
    {
        return $this->budgeted_amount - $this->actual_amount;
    }

    public function getVariancePercentageAttribute()
    {
        if ($this->budgeted_amount == 0) {
            return 0;
        }
        return ($this->variance / $this->budgeted_amount) * 100;
    }
}
