<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SdaResolution extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sda_meeting_id',
        'proposed_by',
        'seconded_by',
        'title',
        'description',
        'background',
        'implementation_plan',
        'resolution_type',
        'priority',
        'status',
        'votes_for',
        'votes_against',
        'votes_abstained',
        'implementation_deadline',
        'implementation_notes',
        'implemented_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'implementation_deadline' => 'date',
        'implemented_at' => 'date',
    ];

    // Relationships
    public function meeting()
    {
        return $this->belongsTo(SdaMeeting::class, 'sda_meeting_id');
    }

    public function proposer()
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function seconder()
    {
        return $this->belongsTo(User::class, 'seconded_by');
    }

    public function tasks()
    {
        return $this->hasMany(SdaTask::class, 'sda_resolution_id');
    }

    // Scopes
    public function scopePassed($query)
    {
        return $query->where('status', 'passed');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['proposed', 'seconded', 'debated', 'voted']);
    }

    public function scopeImplemented($query)
    {
        return $query->where('status', 'implemented');
    }

    public function scopeOverdue($query)
    {
        return $query->where('implementation_deadline', '<', now())
                    ->whereNotIn('status', ['implemented', 'cancelled']);
    }

    // Methods
    public function isPassed()
    {
        return $this->status === 'passed';
    }

    public function isImplemented()
    {
        return $this->status === 'implemented';
    }

    public function isOverdue()
    {
        return $this->implementation_deadline && 
               $this->implementation_deadline < now() && 
               !$this->isImplemented();
    }

    public function canBeVoted()
    {
        return in_array($this->status, ['seconded', 'debated']);
    }

    public function recordVote($votesFor, $votesAgainst, $votesAbstained = 0)
    {
        $totalVotes = $votesFor + $votesAgainst + $votesAbstained;
        $majority = $totalVotes / 2;
        
        $this->update([
            'votes_for' => $votesFor,
            'votes_against' => $votesAgainst,
            'votes_abstained' => $votesAbstained,
            'status' => ($votesFor > $majority) ? 'passed' : 'rejected',
        ]);
    }

    public function markAsImplemented($notes = null)
    {
        $this->update([
            'status' => 'implemented',
            'implemented_at' => now(),
            'implementation_notes' => $notes,
        ]);
    }

    public function cancel($reason)
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
        ]);
    }
}
