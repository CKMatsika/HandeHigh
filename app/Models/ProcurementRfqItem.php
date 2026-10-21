<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementRfqItem extends Model
{
    protected $fillable = [
        'procurement_rfq_id',
        'inventory_item_id',
        'quantity',
        'specifications',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(ProcurementRfq::class, 'procurement_rfq_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function quotationItems(): HasMany
    {
        return $this->hasMany(ProcurementQuotationItem::class);
    }
}
