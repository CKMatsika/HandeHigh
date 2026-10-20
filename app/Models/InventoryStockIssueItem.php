<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStockIssueItem extends Model
{
    protected $fillable = [
        'inventory_stock_issue_id',
        'inventory_stock_request_item_id',
        'inventory_item_id',
        'quantity_issued',
        'unit_cost',
        'line_total',
    ];

    protected $casts = [
        'quantity_issued' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'line_total' => 'decimal:2',
    ];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(InventoryStockIssue::class, 'inventory_stock_issue_id');
    }

    public function requestItem(): BelongsTo
    {
        return $this->belongsTo(InventoryStockRequestItem::class, 'inventory_stock_request_item_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
