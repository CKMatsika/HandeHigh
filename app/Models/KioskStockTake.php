<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KioskStockTake extends Model
{
    protected $fillable = ['school_id', 'stock_take_number', 'status', 'taken_by', 'stock_take_date', 'notes', 'reviewed_by', 'reviewed_at', 'review_notes', 'submitted_at', 'posted_at'];
    protected $casts = ['stock_take_date' => 'date', 'reviewed_at' => 'datetime', 'submitted_at' => 'datetime', 'posted_at' => 'datetime'];
    public function items(): HasMany { return $this->hasMany(KioskStockTakeItem::class); }
    public function reviews(): HasMany { return $this->hasMany(KioskStockTakeReview::class); }
}
