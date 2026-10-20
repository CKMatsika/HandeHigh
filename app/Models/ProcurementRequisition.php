<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementRequisition extends Model
{
    protected $fillable = [
        'school_id',
        'requisition_number',
        'requested_by',
        'department_id',
        'delivery_warehouse_id',
        'needed_by',
        'purpose',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'needed_by' => 'date',
        'submitted_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function deliveryWarehouse(): BelongsTo
    {
        return $this->belongsTo(InventoryWarehouse::class, 'delivery_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementRequisitionItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProcurementRequisitionReview::class);
    }

    public function purchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class);
    }
}
