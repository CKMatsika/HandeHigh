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
        'house_id',
        'is_boarding',
        'has_transport',
        'status',
        'exit_type',
        'exit_date',
        'exit_remarks',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'exit_date' => 'date',
        'is_boarding' => 'boolean',
        'has_transport' => 'boolean',
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
     * Get student positions (note: student_id column stores user_id due to legacy FK).
     */
    public function studentPositions()
    {
        return $this->hasMany(StudentPosition::class, 'student_id');
    }

    public function currentPositions()
    {
        return $this->studentPositions()->current();
    }

    /**
     * Get student clubs (note: student_id column stores user_id due to legacy FK).
     */
    public function studentClubs()
    {
        return $this->hasMany(StudentClub::class, 'student_id');
    }

    public function currentClubs()
    {
        return $this->studentClubs()->current();
    }

    /**
     * Get student sports (note: student_id column stores user_id due to legacy FK).
     */
    public function studentSports()
    {
        return $this->hasMany(StudentSport::class, 'student_id');
    }

    public function currentSports()
    {
        return $this->studentSports()->current();
    }

    public function getSdaPositionAttribute()
    {
        if (!$this->user) {
            return null;
        }
        $membership = $this->user->sdaCommitteeMembers()->with('role')->first();
        return $membership ? $membership->role->name : null;
    }

    public function hasLeadershipRoles()
    {
        return $this->currentPositions()->exists();
    }

    // --- NEW RELATIONSHIPS: Student Lifecycle Management ---

    public function house()
    {
        return $this->belongsTo(SchoolHouse::class, 'house_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'student_subjects')
            ->withPivot(['academic_year', 'term', 'is_active'])
            ->withTimestamps();
    }

    public function currentSubjects()
    {
        $year = request()->get('academic_year', date('Y'));
        $term = request()->get('term', '1');
        return $this->subjects()
            ->wherePivot('academic_year', $year)
            ->wherePivot('term', $term)
            ->wherePivot('is_active', true);
    }

    public function assets()
    {
        return $this->hasMany(StudentAsset::class);
    }

    public function allocatedAssets()
    {
        return $this->assets()->where('status', 'allocated');
    }

    public function transfers()
    {
        return $this->hasMany(StudentTransfer::class);
    }

    public function latestTransfer()
    {
        return $this->hasOne(StudentTransfer::class)->latest();
    }

    public function libraryAccess()
    {
        return $this->hasOne(StudentLibraryAccess::class);
    }

    public function bedAssignments()
    {
        return $this->hasMany(BedAssignment::class);
    }

    public function currentBedAssignment()
    {
        return $this->hasOne(BedAssignment::class)->where('is_current', true);
    }

    public function currentBed()
    {
        return $this->hasOneThrough(Bed::class, BedAssignment::class, 'student_id', 'id', null, 'bed_id')
            ->where('bed_assignments.is_current', true);
    }

    public function dormitory()
    {
        return $this->hasOneThrough(Dormitory::class, BedAssignment::class, 'student_id', 'id', null, 'dormitory_id')
            ->where('bed_assignments.is_current', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBoarding($query)
    {
        return $query->where('is_boarding', true);
    }

    public function scopeDayScholar($query)
    {
        return $query->where('is_boarding', false);
    }

    public function isGraduating()
    {
        $graduatingGrades = ['Grade 7', 'Form 4', 'Form 6'];
        return in_array($this->grade, $graduatingGrades);
    }

    public function getGradeLevelAttribute()
    {
        if (str_starts_with($this->grade ?? '', 'Grade')) {
            return 'primary';
        }
        return 'secondary';
    }

    public function careerAssessment()
    {
        return $this->hasOne(\App\Models\CareerGuidanceAssessment::class, 'student_id')
            ->latest('assessment_date');
    }

    public function careerInterest()
    {
        return $this->hasOne(\App\Models\StudentCareerInterest::class, 'student_id');
    }
}
