<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'type',
        'department',
        'position',
        'employee_id',
        'hire_date',
        'status',
        'salary',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'salary' => 'decimal:2',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function attendances()
    {
        return $this->morphMany(Attendance::class, 'attendable');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function scopeTeachers($query)
    {
        return $query->where('type', 'teacher');
    }

    public function scopeNonTeaching($query)
    {
        return $query->where('type', 'non_teaching');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
