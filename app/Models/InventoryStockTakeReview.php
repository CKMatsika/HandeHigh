<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStockTakeReview extends Model
{
    protected $fillable = [
        'inventory_stock_take_id',
        'reviewed_by',
        'decision',
        'comments',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function stockTake(): BelongsTo
    {
        return $this->belongsTo(InventoryStockTake::class, 'inventory_stock_take_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
