<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementRequisitionItem extends Model
{
    protected $fillable = [
        'procurement_requisition_id',
        'inventory_item_id',
        'quantity',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequisition::class, 'procurement_requisition_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
