<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'fiscal_year',
        'budget_type',
        'status',
        'created_by',
        'submitted_by',
        'submitted_at',
        'bursar_reviewed_by',
        'bursar_reviewed_at',
        'committee_reviewed_by',
        'committee_reviewed_at',
        'approved_by',
        'approved_at',
        'bursar_notes',
        'committee_notes',
        'rejection_reason',
        'total_budgeted',
        'total_actual',
        'total_variance',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'submitted_at' => 'datetime',
        'bursar_reviewed_at' => 'datetime',
        'committee_reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'total_budgeted' => 'decimal:2',
        'total_actual' => 'decimal:2',
        'total_variance' => 'decimal:2',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function lines()
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function budgetLines()
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function bursarReviewer()
    {
        return $this->belongsTo(User::class, 'bursar_reviewed_by');
    }

    public function committeeReviewer()
    {
        return $this->belongsTo(User::class, 'committee_reviewed_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function getTotalBudgetedAttribute()
    {
        return $this->lines()->sum('budgeted_amount');
    }

    // Workflow Methods
    public function canBeEdited()
    {
        return in_array($this->status, ['draft', 'rejected']);
    }

    public function canBeSubmitted()
    {
        return $this->status === 'draft' && $this->lines()->count() > 0;
    }

    public function canBeReviewedByBursar()
    {
        return $this->status === 'submitted';
    }

    public function canBeReviewedByCommittee()
    {
        return $this->status === 'finance_committee';
    }

    public function canBeApproved()
    {
        return $this->status === 'committee_review';
    }

    public function canBeActivated()
    {
        return $this->status === 'approved';
    }

    public function submit()
    {
        $this->status = 'submitted';
        $this->submitted_by = auth()->id();
        $this->submitted_at = now();
        $this->save();
    }

    public function bursarReview($approved, $notes = null)
    {
        if ($approved) {
            $this->status = 'finance_committee';
        } else {
            $this->status = 'rejected';
            $this->rejection_reason = $notes;
        }
        
        $this->bursar_reviewed_by = auth()->id();
        $this->bursar_reviewed_at = now();
        $this->bursar_notes = $notes;
        $this->save();
    }

    public function committeeReview($approved, $notes = null)
    {
        if ($approved) {
            $this->status = 'approved';
            $this->approved_by = auth()->id();
            $this->approved_at = now();
        } else {
            $this->status = 'rejected';
            $this->rejection_reason = $notes;
        }
        
        $this->committee_reviewed_by = auth()->id();
        $this->committee_reviewed_at = now();
        $this->committee_notes = $notes;
        $this->save();
    }

    public function activate()
    {
        $this->status = 'active';
        $this->save();
    }

    public function close()
    {
        $this->status = 'closed';
        $this->save();
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted to Bursar',
            'bursar_review' => 'Bursar Review',
            'finance_committee' => 'Finance Committee',
            'committee_review' => 'Committee Review',
            'approved' => 'Approved',
            'active' => 'Active',
            'closed' => 'Closed',
            'rejected' => 'Rejected',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'draft' => 'secondary',
            'submitted' => 'info',
            'bursar_review' => 'warning',
            'finance_committee' => 'primary',
            'committee_review' => 'primary',
            'approved' => 'success',
            'active' => 'emerald',
            'closed' => 'slate',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    public function recalculateTotals()
    {
        $lines = $this->lines()->with('account')->get();
        $this->total_budgeted = (float) $lines->sum('budgeted_amount');
        $this->total_actual = (float) $lines->sum(fn ($l) => $l->actual_amount);
        $this->total_variance = (float) $lines->sum(fn ($l) => $l->variance);
        $this->save();
    }
}
