<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    protected $fillable = [
        'school_id',
        'return_number',
        'goods_received_voucher_id',
        'purchase_order_id',
        'vendor_id',
        'inventory_warehouse_id',
        'return_date',
        'reason',
        'reason_details',
        'debit_note_number',
        'status',
        'created_by',
        'posted_at',
    ];

    protected $casts = [
        'return_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function goodsReceivedVoucher(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedVoucher::class, 'goods_received_voucher_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(InventoryWarehouse::class, 'inventory_warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}
