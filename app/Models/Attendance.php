<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'attendable_type',
        'attendable_id',
        'attendance_date',
        'status',
        'check_in_time',
        'check_out_time',
        'notes',
        'marked_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_time' => 'datetime:H:i',
        'check_out_time' => 'datetime:H:i',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function attendable()
    {
        return $this->morphTo();
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('attendance_date', $date);
    }

    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'present' => 'text-green-600',
            'absent' => 'text-red-600',
            'late' => 'text-yellow-600',
            'excused' => 'text-blue-600',
            'sick_leave' => 'text-purple-600',
            'vacation' => 'text-gray-600',
            default => 'text-gray-600',
        };
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'present' => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Present</span>',
            'absent' => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Absent</span>',
            'late' => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Late</span>',
            'excused' => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">Excused</span>',
            'sick_leave' => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">Sick Leave</span>',
            'vacation' => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">Vacation</span>',
            default => '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">' . ucfirst($this->status) . '</span>',
        };
    }
}
