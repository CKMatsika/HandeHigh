<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SdaMeetingMinute extends Model
{
    use HasFactory;

    protected $fillable = [
        'sda_meeting_id',
        'recorded_by',
        'opening_remarks',
        'previous_minutes_summary',
        'matters_arising',
        'new_business',
        'other_business',
        'closing_remarks',
        'next_meeting_date',
        'status',
        'chairman_review_notes',
        'chairman_reviewed_at',
        'approved_at',
    ];

    protected $casts = [
        'next_meeting_date' => 'datetime',
        'chairman_reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function meeting()
    {
        return $this->belongsTo(SdaMeeting::class, 'sda_meeting_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeReview($query)
    {
        return $query->where('status', 'review');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    // Methods
    public function canBeEdited()
    {
        return in_array($this->status, ['draft', 'review']);
    }

    public function isApproved()
    {
        return in_array($this->status, ['approved', 'published']);
    }

    public function markAsReviewed($notes = null)
    {
        $this->update([
            'status' => 'review',
            'chairman_review_notes' => $notes,
            'chairman_reviewed_at' => now(),
        ]);
    }

    public function approve()
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function publish()
    {
        $this->update([
            'status' => 'published',
            'approved_at' => now(),
        ]);
    }
}
