<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolFixedAssetDepreciation extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'school_fixed_asset_id',
        'journal_batch_id',
        'depreciation_date',
        'period_name',
        'method',
        'depreciation_amount',
        'book_value_before',
        'book_value_after',
        'accumulated_depreciation_after',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'depreciation_date' => 'date',
        'depreciation_amount' => 'decimal:2',
        'book_value_before' => 'decimal:2',
        'book_value_after' => 'decimal:2',
        'accumulated_depreciation_after' => 'decimal:2',
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

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
