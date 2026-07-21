<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SdaMeetingAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'sda_meeting_id',
        'user_id',
        'attendance_status',
        'arrival_time',
        'apology_reason',
        'notes',
    ];

    protected $casts = [
        'arrival_time' => 'datetime',
    ];

    // Relationships
    public function meeting()
    {
        return $this->belongsTo(SdaMeeting::class, 'sda_meeting_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopePresent($query)
    {
        return $query->where('attendance_status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('attendance_status', 'absent');
    }

    public function scopeApologized($query)
    {
        return $query->where('attendance_status', 'apologized');
    }

    public function scopeLate($query)
    {
        return $query->where('attendance_status', 'late');
    }
}
