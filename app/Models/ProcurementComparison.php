<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementComparison extends Model
{
    protected $fillable = [
        'school_id',
        'procurement_rfq_id',
        'comparison_number',
        'evaluation_date',
        'winning_quotation_id',
        'winning_vendor_id',
        'selection_justification',
        'evaluated_by',
        'status',
        'approved_by',
        'approved_at',
        'purchase_order_id',
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(ProcurementRfq::class, 'procurement_rfq_id');
    }

    public function winningQuotation(): BelongsTo
    {
        return $this->belongsTo(ProcurementQuotation::class, 'winning_quotation_id');
    }

    public function winningVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'winning_vendor_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}
