<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SdaMeeting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sda_committee_id',
        'title',
        'description',
        'meeting_date',
        'venue',
        'meeting_type',
        'agenda',
        'status',
        'duration_minutes',
        'cancellation_reason',
    ];

    protected $casts = [
        'meeting_date' => 'datetime',
        'next_meeting_date' => 'datetime',
    ];

    // Relationships
    public function committee()
    {
        return $this->belongsTo(SdaCommittee::class, 'sda_committee_id');
    }

    public function attendances()
    {
        return $this->hasMany(SdaMeetingAttendance::class, 'sda_meeting_id');
    }

    public function minutes()
    {
        return $this->hasOne(SdaMeetingMinute::class, 'sda_meeting_id');
    }

    public function resolutions()
    {
        return $this->hasMany(SdaResolution::class, 'sda_meeting_id');
    }

    // Scopes
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('meeting_date', '>', now());
    }

    public function scopePast($query)
    {
        return $query->where('meeting_date', '<=', now());
    }

    // Methods
    public function isUpcoming()
    {
        return $this->meeting_date > now() && $this->status === 'scheduled';
    }

    public function canBeEdited()
    {
        return in_array($this->status, ['scheduled', 'in_progress']);
    }

    public function getAttendanceCount()
    {
        return $this->attendances()->where('attendance_status', 'present')->count();
    }

    public function getTotalMembers()
    {
        return $this->committee->activeMembers()->count();
    }
}
