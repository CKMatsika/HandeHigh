<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryStockTake extends Model
{
    protected $fillable = [
        'school_id',
        'inventory_warehouse_id',
        'stock_take_number',
        'status',
        'taken_by',
        'stock_take_date',
        'notes',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'submitted_at',
        'posted_at',
    ];

    protected $casts = [
        'stock_take_date' => 'date',
        'reviewed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(InventoryWarehouse::class, 'inventory_warehouse_id');
    }

    public function taker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'taken_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryStockTakeItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(InventoryStockTakeReview::class)->orderBy('reviewed_at');
    }
}
