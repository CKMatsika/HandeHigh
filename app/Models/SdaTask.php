<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SdaTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sda_committee_id',
        'assigned_to',
        'assigned_by',
        'sda_resolution_id',
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'completed_at',
        'completion_notes',
        'cancellation_reason',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'date',
    ];

    // Relationships
    public function committee()
    {
        return $this->belongsTo(SdaCommittee::class, 'sda_committee_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function resolution()
    {
        return $this->belongsTo(SdaResolution::class, 'sda_resolution_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    // Methods
    public function isOverdue()
    {
        return $this->due_date < now() && !in_array($this->status, ['completed', 'cancelled']);
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function canBeCompleted()
    {
        return in_array($this->status, ['pending', 'in_progress']);
    }

    public function start()
    {
        $this->update([
            'status' => 'in_progress',
        ]);
    }

    public function complete($notes = null)
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completion_notes' => $notes,
        ]);
    }

    public function cancel($reason)
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
        ]);
    }

    public function getDaysUntilDue()
    {
        if ($this->isCompleted()) {
            return null;
        }
        
        $days = $this->due_date->diffInDays(now(), false);
        
        if ($days < 0) {
            return abs($days) . ' days overdue';
        } elseif ($days === 0) {
            return 'Due today';
        } elseif ($days === 1) {
            return 'Due tomorrow';
        } else {
            return $days . ' days remaining';
        }
    }

    public function getPriorityColor()
    {
        return match($this->priority) {
            'urgent' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'secondary',
            default => 'secondary',
        };
    }

    public function getStatusColor()
    {
        return match($this->status) {
            'completed' => 'success',
            'in_progress' => 'primary',
            'pending' => 'secondary',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }
}
