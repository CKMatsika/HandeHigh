<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KioskStockTakeReview extends Model
{
    protected $fillable = ['kiosk_stock_take_id', 'reviewed_by', 'decision', 'comments', 'reviewed_at'];
    protected $casts = ['reviewed_at' => 'datetime'];
}
