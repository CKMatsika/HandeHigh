<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceivedVoucherItem extends Model
{
    protected $fillable = [
        'goods_received_voucher_id',
        'purchase_order_item_id',
        'inventory_item_id',
        'quantity_received',
        'unit_cost',
        'line_total',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'line_total' => 'decimal:2',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedVoucher::class, 'goods_received_voucher_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function supplierInvoiceItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProcurementSupplierInvoiceItem::class);
    }
}
