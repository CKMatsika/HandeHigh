<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentClub extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'club_name',
        'club_type',
        'role',
        'description',
        'joined_date',
        'left_date',
        'is_active',
    ];

    protected $casts = [
        'joined_date' => 'date',
        'left_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_active', true)
                    ->where(function($q) {
                        $q->whereNull('left_date')
                          ->orWhere('left_date', '>=', now());
                    });
    }

    public function getTypeLabelAttribute()
    {
        return match($this->club_type) {
            'academic' => 'Academic',
            'sports' => 'Sports',
            'arts' => 'Arts',
            'community' => 'Community Service',
            'technology' => 'Technology',
            'cultural' => 'Cultural',
            default => ucfirst($this->club_type),
        };
    }

    public function getBadgeColorAttribute()
    {
        return match($this->club_type) {
            'academic' => 'primary',
            'sports' => 'success',
            'arts' => 'warning',
            'community' => 'info',
            'technology' => 'secondary',
            'cultural' => 'danger',
            default => 'secondary',
        };
    }
}
