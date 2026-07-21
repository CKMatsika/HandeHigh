<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'position_type',
        'position_title',
        'description',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
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
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', now());
                    });
    }

    public function getTypeLabelAttribute()
    {
        return match($this->position_type) {
            'prefect' => 'Prefect',
            'head_boy' => 'Head Boy',
            'head_girl' => 'Head Girl',
            'class_rep' => 'Class Representative',
            'house_captain' => 'House Captain',
            'sports_captain' => 'Sports Captain',
            'club_president' => 'Club President',
            default => ucfirst($this->position_type),
        };
    }

    public function getBadgeColorAttribute()
    {
        return match($this->position_type) {
            'head_boy', 'head_girl' => 'danger',
            'prefect' => 'warning',
            'class_rep' => 'info',
            'house_captain' => 'primary',
            'sports_captain' => 'success',
            'club_president' => 'secondary',
            default => 'secondary',
        };
    }
}
