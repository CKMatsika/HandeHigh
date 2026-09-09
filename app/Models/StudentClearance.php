<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentClearance extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'student_id',
        'year_end_process_id',
        'academic_year',
        'graduation_grade',
        'exit_type',
        'finance_status',
        'finance_balance',
        'finance_cleared_by',
        'finance_cleared_at',
        'finance_remarks',
        'library_status',
        'unreturned_books_count',
        'library_cleared_by',
        'library_cleared_at',
        'library_remarks',
        'assets_status',
        'unreturned_assets_count',
        'assets_cleared_by',
        'assets_cleared_at',
        'assets_remarks',
        'boarding_status',
        'boarding_cleared_by',
        'boarding_cleared_at',
        'boarding_remarks',
        'status',
        'certificate_number',
        'exited_at',
        'exited_by',
        'general_remarks',
    ];

    protected $casts = [
        'finance_balance' => 'decimal:2',
        'unreturned_books_count' => 'integer',
        'unreturned_assets_count' => 'integer',
        'finance_cleared_at' => 'datetime',
        'library_cleared_at' => 'datetime',
        'assets_cleared_at' => 'datetime',
        'boarding_cleared_at' => 'datetime',
        'exited_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function yearEndProcess(): BelongsTo
    {
        return $this->belongsTo(YearEndProcess::class, 'year_end_process_id');
    }

    public function financeClearedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finance_cleared_by');
    }

    public function libraryClearedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'library_cleared_by');
    }

    public function assetsClearedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assets_cleared_by');
    }

    public function boardingClearedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'boarding_cleared_by');
    }

    public function exitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exited_by');
    }

    /**
     * Check if all department checkpoints are satisfied (cleared, waived, or not_applicable).
     */
    public function isFullyCleared(): bool
    {
        $financeOk = in_array($this->finance_status, ['cleared', 'waived']);
        $libraryOk = in_array($this->library_status, ['cleared', 'waived']);
        $assetsOk = in_array($this->assets_status, ['cleared', 'waived']);
        $boardingOk = in_array($this->boarding_status, ['cleared', 'waived', 'not_applicable']);

        return $financeOk && $libraryOk && $assetsOk && $boardingOk;
    }

    /**
     * Recalculate status based on checkpoint state.
     */
    public function refreshOverallStatus(): void
    {
        if ($this->status === 'permanently_exited') {
            return;
        }

        if ($this->isFullyCleared()) {
            $this->status = 'fully_cleared';
        } else {
            $this->status = 'pending_clearance';
        }
    }
}
