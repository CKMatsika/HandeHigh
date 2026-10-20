<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryStockIssue extends Model
{
    protected $fillable = [
        'school_id',
        'inventory_stock_request_id',
        'inventory_warehouse_id',
        'issue_number',
        'issued_date',
        'issued_by',
        'notes',
        'posted_at',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function stockRequest(): BelongsTo
    {
        return $this->belongsTo(InventoryStockRequest::class, 'inventory_stock_request_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(InventoryWarehouse::class, 'inventory_warehouse_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryStockIssueItem::class);
    }
}
