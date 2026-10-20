<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementSupplierInvoice extends Model
{
    protected $fillable = [
        'school_id',
        'goods_received_voucher_id',
        'vendor_id',
        'bill_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'total_amount',
        'status',
        'submitted_by',
        'approved_by',
        'approved_at',
        'posted_at',
        'approval_notes',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function goodsReceivedVoucher(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedVoucher::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementSupplierInvoiceItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProcurementSupplierInvoiceReview::class);
    }
}
