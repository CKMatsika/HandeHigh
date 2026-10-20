<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStockTakeItem extends Model
{
    protected $fillable = [
        'inventory_stock_take_id',
        'inventory_item_id',
        'system_quantity',
        'system_average_unit_cost',
        'counted_quantity',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:4',
        'system_average_unit_cost' => 'decimal:4',
        'counted_quantity' => 'decimal:4',
    ];

    public function stockTake(): BelongsTo
    {
        return $this->belongsTo(InventoryStockTake::class, 'inventory_stock_take_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
