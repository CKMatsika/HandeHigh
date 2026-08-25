<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KioskSaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'kiosk_sale_id',
        'kiosk_product_id',
        'quantity',
        'unit_price',
        'cost_price',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(KioskSale::class, 'kiosk_sale_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(KioskProduct::class, 'kiosk_product_id');
    }
}
