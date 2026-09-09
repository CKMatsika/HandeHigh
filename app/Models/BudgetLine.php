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
        $account = $this->account;
        if (! $account) {
            return 0.0;
        }

        $isRevenue = ($account->type === 'revenue' || str_starts_with($account->code, '5'));

        $query = JournalEntry::whereHas('batch', function ($q) {
            $q->where('status', 'posted');
            if ($this->budget && $this->budget->fiscal_year) {
                $q->whereYear('transaction_date', $this->budget->fiscal_year);
            }
        })
        ->where('account_id', $this->account_id);

        if ($this->budget && $this->budget->budget_type === 'monthly' && $this->period) {
            $query->whereHas('batch', function ($q) {
                $q->whereMonth('transaction_date', $this->period);
            });
        }

        if ($this->cost_center_id) {
            $query->where('cost_center_id', $this->cost_center_id);
        }

        if ($this->project_id) {
            $query->where('project_id', $this->project_id);
        }

        if ($isRevenue) {
            $credits = (float) (clone $query)->where('entry_type', 'credit')->sum('amount');
            $debits = (float) (clone $query)->where('entry_type', 'debit')->sum('amount');
            return max(0, $credits - $debits);
        } else {
            $debits = (float) (clone $query)->where('entry_type', 'debit')->sum('amount');
            $credits = (float) (clone $query)->where('entry_type', 'credit')->sum('amount');
            return max(0, $debits - $credits);
        }
    }

    public function getVarianceAttribute()
    {
        $account = $this->account;
        $isRevenue = $account && ($account->type === 'revenue' || str_starts_with($account->code, '5'));
        
        if ($isRevenue) {
            // For revenue, actual - budgeted (positive means surplus / above budget)
            return (float) $this->actual_amount - (float) $this->budgeted_amount;
        }
        
        // For expenditure, budgeted - actual (positive means under budget / savings)
        return (float) $this->budgeted_amount - (float) $this->actual_amount;
    }

    public function getVariancePercentageAttribute()
    {
        if ($this->budgeted_amount == 0) {
            return 0;
        }
        return ($this->variance / $this->budgeted_amount) * 100;
    }
}
