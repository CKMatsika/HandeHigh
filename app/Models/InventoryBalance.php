<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryBalance extends Model
{
    protected $fillable = [
        'school_id',
        'inventory_item_id',
        'inventory_warehouse_id',
        'quantity_on_hand',
        'average_unit_cost',
        'reorder_level',
        'reorder_quantity',
    ];

    protected $casts = [
        'quantity_on_hand' => 'decimal:4',
        'average_unit_cost' => 'decimal:4',
        'reorder_level' => 'decimal:4',
        'reorder_quantity' => 'decimal:4',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(InventoryWarehouse::class, 'inventory_warehouse_id');
    }
}
