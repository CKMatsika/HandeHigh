<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'employee_id',
        'department_id',
        'position',
        'employment_type',
        'employment_status',
        'date_of_birth',
        'hire_date',
        'termination_date',
        'termination_reason',
        'salary',
        'work_schedule',
        'address',
        'emergency_contact',
        'emergency_phone',
        'profile_photo',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'salary' => 'decimal:2',
        'employment_status' => 'string',
        'employment_type' => 'string',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function allowances(): HasMany
    {
        return $this->hasMany(EmployeeAllowance::class);
    }

    public function activeAllowances(): HasMany
    {
        return $this->allowances()->where('is_active', true);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function activeLoans(): HasMany
    {
        return $this->loans()->where('status', 'active');
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function qualifications()
    {
        return $this->morphMany(Qualification::class, 'qualifiable');
    }

    public function positionAssignments()
    {
        return $this->morphMany(StaffPositionAssignment::class, 'assignable');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function isActive(): bool
    {
        return $this->employment_status === 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('employment_status', 'active');
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function managedHostels(): HasMany
    {
        return $this->hasMany(Hostel::class, 'supervisor_id');
    }

    public function managedDormitories(): HasMany
    {
        return $this->hasMany(Dormitory::class, 'supervisor_id');
    }

    public function isNonTeaching(): bool
    {
        $pos = strtolower($this->position ?? '');
        $dept = strtolower($this->department?->name ?? '');
        
        return ! str_contains($pos, 'teacher') && ! str_contains($dept, 'academics');
    }

    public function isMatron(): bool
    {
        $pos = strtolower($this->position ?? '');
        return str_contains($pos, 'matron');
    }

    public function isBoardingMaster(): bool
    {
        $pos = strtolower($this->position ?? '');
        return str_contains($pos, 'boarding master') || str_contains($pos, 'hostel master') || str_contains($pos, 'dorm master');
    }
}
