<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KioskStockMovement extends Model
{
    protected $fillable = ['school_id', 'kiosk_product_id', 'created_by', 'movement_type', 'quantity_delta', 'quantity_before', 'quantity_after', 'reference_type', 'reference_id', 'notes'];
    protected $casts = ['quantity_delta' => 'decimal:4', 'quantity_before' => 'decimal:4', 'quantity_after' => 'decimal:4'];

    public function product() { return $this->belongsTo(KioskProduct::class, 'kiosk_product_id'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
