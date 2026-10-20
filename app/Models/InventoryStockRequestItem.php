<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStockRequestItem extends Model
{
    protected $fillable = [
        'inventory_stock_request_id',
        'inventory_item_id',
        'quantity_requested',
        'quantity_issued',
        'notes',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:4',
        'quantity_issued' => 'decimal:4',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(InventoryStockRequest::class, 'inventory_stock_request_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
