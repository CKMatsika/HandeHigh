<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherAbsence extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'teacher_id',
        'start_date',
        'end_date',
        'affected_period_ids',
        'reason',
        'notes',
        'status',
        'recorded_by',
    ];

    protected $casts = [
        'start_date' => 'string',
        'end_date' => 'string',
        'affected_period_ids' => 'array',
        'status' => 'string',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function substitutions(): HasMany
    {
        return $this->hasMany(TimetableSubstitution::class);
    }

    public function isActiveOn(string|Carbon $date, ?int $periodId = null): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $targetDate = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();
        $start = $this->start_date instanceof Carbon ? $this->start_date->toDateString() : Carbon::parse($this->start_date)->toDateString();
        $end = $this->end_date instanceof Carbon ? $this->end_date->toDateString() : Carbon::parse($this->end_date)->toDateString();

        if ($targetDate < $start || $targetDate > $end) {
            return false;
        }

        if (empty($this->affected_period_ids)) {
            return true; // Full day absence
        }

        if ($periodId !== null) {
            return in_array($periodId, $this->affected_period_ids);
        }

        return true;
    }
}
