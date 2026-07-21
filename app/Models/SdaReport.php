<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SdaReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sda_committee_id',
        'submitted_by',
        'title',
        'report_type',
        'executive_summary',
        'content',
        'recommendations',
        'conclusions',
        'report_date',
        'period_start',
        'period_end',
        'status',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
        'published_at',
        'file_path',
        'is_public',
    ];

    protected $casts = [
        'report_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
        'is_public' => 'boolean',
    ];

    // Relationships
    public function committee()
    {
        return $this->belongsTo(SdaCommittee::class, 'sda_committee_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['approved', 'published']);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeChairman($query)
    {
        return $query->where('report_type', 'chairman');
    }

    public function scopeSecretary($query)
    {
        return $query->where('report_type', 'secretary');
    }

    public function scopeTreasurer($query)
    {
        return $query->where('report_type', 'treasurer');
    }

    // Methods
    public function canBeEdited()
    {
        return in_array($this->status, ['draft', 'submitted']);
    }

    public function canBeReviewed()
    {
        return $this->status === 'submitted';
    }

    public function canBePublished()
    {
        return in_array($this->status, ['approved', 'submitted']);
    }

    public function submit()
    {
        $this->update([
            'status' => 'submitted',
            'report_date' => now(),
        ]);
    }

    public function review($notes, $reviewerId)
    {
        $this->update([
            'status' => 'review',
            'review_notes' => $notes,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);
    }

    public function approve()
    {
        $this->update([
            'status' => 'approved',
        ]);
    }

    public function publish()
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function archive()
    {
        $this->update([
            'status' => 'archived',
        ]);
    }

    public function getFormattedPeriod()
    {
        if ($this->period_start && $this->period_end) {
            return $this->period_start->format('M d, Y') . ' - ' . $this->period_end->format('M d, Y');
        }
        return $this->report_date->format('M d, Y');
    }
}
