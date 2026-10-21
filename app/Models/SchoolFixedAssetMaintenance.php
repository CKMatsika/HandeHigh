<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolFixedAssetMaintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'school_fixed_asset_id',
        'journal_batch_id',
        'title',
        'maintenance_type',
        'priority',
        'status',
        'service_provider',
        'cost',
        'scheduled_date',
        'completed_date',
        'next_service_due',
        'description',
        'performed_by',
        'recorded_by',
        'expense_account_id',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'next_service_due' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(SchoolFixedAsset::class, 'school_fixed_asset_id');
    }

    public function journalBatch(): BelongsTo
    {
        return $this->belongsTo(JournalBatch::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function getActualCostAttribute(): ?float
    {
        return $this->completed_date ? (float) $this->cost : null;
    }

    public function getEstimatedCostAttribute(): ?float
    {
        return ! $this->completed_date ? (float) $this->cost : null;
    }
}
