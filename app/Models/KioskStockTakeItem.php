<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KioskStockTakeItem extends Model
{
    protected $fillable = ['kiosk_stock_take_id', 'kiosk_product_id', 'system_quantity', 'counted_quantity'];
    protected $casts = ['system_quantity' => 'decimal:4', 'counted_quantity' => 'decimal:4'];
}
