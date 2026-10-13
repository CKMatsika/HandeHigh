<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'project_id',
        'title',
        'description',
        'phase_milestone',
        'assigned_to',
        'created_by',
        'priority',
        'status',
        'due_date',
        'completed_at',
        'progress_percent',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'progress_percent' => 'integer',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

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

    public function getPriorityBadgeColorAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'red',
            'high' => 'amber',
            'medium' => 'cyan',
            'low' => 'slate',
            default => 'slate',
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'emerald',
            'in_progress' => 'blue',
            'blocked' => 'rose',
            'pending' => 'amber',
            default => 'slate',
        };
    }
}
