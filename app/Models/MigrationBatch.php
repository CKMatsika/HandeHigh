<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MigrationBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'batch_number',
        'import_type',
        'status',
        'file_name',
        'total_rows',
        'valid_rows',
        'error_rows',
        'posted_rows',
        'total_debit',
        'total_credit',
        'is_balanced',
        'metadata',
        'uploaded_by',
        'approved_by',
        'approved_at',
        'posted_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'is_balanced' => 'boolean',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function stagingRows()
    {
        return $this->hasMany(MigrationStagingRow::class)->orderBy('row_number');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'ready_for_approval';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }
}
