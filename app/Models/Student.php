<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'user_id',
        'first_name',
        'last_name',
        'other_names',
        'gender',
        'date_of_birth',
        'admission_number',
        'registration_number',
        'grade',
        'class_name',
        'is_boarding',
        'has_transport',
        'status',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function guardians()
    {
        return $this->belongsToMany(Guardian::class)
            ->withPivot(['relationship', 'is_primary'])
            ->withTimestamps();
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function attendances()
    {
        return $this->morphMany(Attendance::class, 'attendable');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }

    /**
     * Get student positions for this student.
     */
    public function studentPositions()
    {
        return $this->hasMany(StudentPosition::class, 'student_id');
    }

    /**
     * Get current student positions for this student.
     */
    public function currentPositions()
    {
        return $this->studentPositions()->current();
    }

    /**
     * Get student clubs for this student.
     */
    public function studentClubs()
    {
        return $this->hasMany(StudentClub::class, 'student_id');
    }

    /**
     * Get current student clubs for this student.
     */
    public function currentClubs()
    {
        return $this->studentClubs()->current();
    }

    /**
     * Get student sports for this student.
     */
    public function studentSports()
    {
        return $this->hasMany(StudentSport::class, 'student_id');
    }

    /**
     * Get current student sports for this student.
     */
    public function currentSports()
    {
        return $this->studentSports()->current();
    }

    /**
     * Get student's SDA position/role.
     */
    public function getSdaPositionAttribute()
    {
        if (!$this->user) {
            return null;
        }
        
        $membership = $this->user->sdaCommitteeMembers()->with('role')->first();
        return $membership ? $membership->role->name : null;
    }

    /**
     * Check if student has any leadership positions.
     */
    public function hasLeadershipRoles()
    {
        return $this->currentPositions()->exists();
    }
}
