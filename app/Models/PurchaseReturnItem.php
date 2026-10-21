<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends Model
{
    protected $fillable = [
        'purchase_return_id',
        'goods_received_voucher_item_id',
        'inventory_item_id',
        'quantity_returned',
        'unit_cost',
        'total_cost',
        'remarks',
    ];

    protected $casts = [
        'quantity_returned' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:2',
    ];

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function goodsReceivedVoucherItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedVoucherItem::class, 'goods_received_voucher_item_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
