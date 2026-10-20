<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'school_id',
        'inventory_item_id',
        'inventory_warehouse_id',
        'movement_type',
        'quantity_delta',
        'unit_cost',
        'quantity_after',
        'average_unit_cost_after',
        'reference_type',
        'reference_id',
        'transaction_group_id',
        'notes',
        'recorded_by',
        'occurred_at',
    ];

    protected $casts = [
        'quantity_delta' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'quantity_after' => 'decimal:4',
        'average_unit_cost_after' => 'decimal:4',
        'occurred_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(InventoryWarehouse::class, 'inventory_warehouse_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
