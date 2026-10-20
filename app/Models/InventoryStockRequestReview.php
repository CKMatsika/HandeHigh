<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStockRequestReview extends Model
{
    protected $fillable = [
        'inventory_stock_request_id',
        'reviewed_by',
        'decision',
        'comments',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function stockRequest(): BelongsTo
    {
        return $this->belongsTo(InventoryStockRequest::class, 'inventory_stock_request_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
