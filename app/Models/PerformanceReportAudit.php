<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReportAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'performance_report_id',
        'performance_report_subject_id',
        'user_id',
        'action',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(PerformanceReport::class, 'performance_report_id');
    }

    public function reportSubject(): BelongsTo
    {
        return $this->belongsTo(PerformanceReportSubject::class, 'performance_report_subject_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
