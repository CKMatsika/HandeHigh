<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentSport extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'sport_name',
        'sport_category',
        'position',
        'team_level',
        'achievements',
        'started_date',
        'ended_date',
        'is_active',
    ];

    protected $casts = [
        'started_date' => 'date',
        'ended_date' => 'date',
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
                        $q->whereNull('ended_date')
                          ->orWhere('ended_date', '>=', now());
                    });
    }

    public function getCategoryLabelAttribute()
    {
        return match($this->sport_category) {
            'team_sport' => 'Team Sport',
            'individual_sport' => 'Individual Sport',
            'athletics' => 'Athletics',
            'water_sport' => 'Water Sport',
            'winter_sport' => 'Winter Sport',
            default => ucfirst($this->sport_category),
        };
    }

    public function getBadgeColorAttribute()
    {
        return match($this->sport_category) {
            'team_sport' => 'primary',
            'individual_sport' => 'success',
            'athletics' => 'warning',
            'water_sport' => 'info',
            'winter_sport' => 'secondary',
            default => 'secondary',
        };
    }
}
