<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementQuotationItem extends Model
{
    protected $fillable = [
        'procurement_quotation_id',
        'procurement_rfq_item_id',
        'inventory_item_id',
        'quantity',
        'unit_price',
        'total_price',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'total_price' => 'decimal:2',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(ProcurementQuotation::class, 'procurement_quotation_id');
    }

    public function rfqItem(): BelongsTo
    {
        return $this->belongsTo(ProcurementRfqItem::class, 'procurement_rfq_item_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
