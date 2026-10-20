<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'school_id',
        'procurement_requisition_id',
        'vendor_id',
        'purchase_order_number',
        'status',
        'order_date',
        'expected_delivery_date',
        'notes',
        'created_by',
        'issued_by',
        'issued_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'issued_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequisition::class, 'procurement_requisition_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceivedVouchers(): HasMany
    {
        return $this->hasMany(GoodsReceivedVoucher::class);
    }
}
