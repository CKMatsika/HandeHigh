<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolFixedAsset extends Model
{
    protected $fillable = [
        'school_id',
        'inventory_item_id',
        'goods_received_voucher_item_id',
        'supplier_invoice_item_id',
        'inventory_warehouse_id',
        'asset_account_id',
        'custodian_user_id',
        'created_by',
        'asset_number',
        'serial_number',
        'status',
        'is_capitalized',
        'purchase_cost',
        'capitalized_cost',
        'acquired_date',
        'location',
        'notes',
    ];

    protected $casts = [
        'is_capitalized' => 'boolean',
        'purchase_cost' => 'decimal:2',
        'capitalized_cost' => 'decimal:2',
        'acquired_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function goodsReceivedItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedVoucherItem::class, 'goods_received_voucher_item_id');
    }

    public function supplierInvoiceItem(): BelongsTo
    {
        return $this->belongsTo(ProcurementSupplierInvoiceItem::class, 'supplier_invoice_item_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(InventoryWarehouse::class, 'inventory_warehouse_id');
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'custodian_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SchoolFixedAssetAssignment::class)->orderBy('occurred_at');
    }
}
