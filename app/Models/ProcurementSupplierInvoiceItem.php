<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementSupplierInvoiceItem extends Model
{
    protected $fillable = [
        'procurement_supplier_invoice_id',
        'goods_received_voucher_item_id',
        'quantity_invoiced',
        'unit_price',
        'line_total',
        'stock_cost_basis',
    ];

    protected $casts = [
        'quantity_invoiced' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'line_total' => 'decimal:2',
        'stock_cost_basis' => 'decimal:2',
    ];

    public function supplierInvoice(): BelongsTo
    {
        return $this->belongsTo(ProcurementSupplierInvoice::class, 'procurement_supplier_invoice_id');
    }

    public function goodsReceivedItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedVoucherItem::class, 'goods_received_voucher_item_id');
    }
}
