<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProcurementRfq extends Model
{
    protected $fillable = [
        'school_id',
        'procurement_requisition_id',
        'rfq_number',
        'title',
        'submission_deadline',
        'terms_and_conditions',
        'status',
        'created_by',
        'published_at',
    ];

    protected $casts = [
        'submission_deadline' => 'date',
        'published_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequisition::class, 'procurement_requisition_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementRfqItem::class);
    }

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'procurement_rfq_vendors')
            ->withPivot('invited_at')
            ->withTimestamps();
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(ProcurementQuotation::class);
    }

    public function comparison(): HasOne
    {
        return $this->hasOne(ProcurementComparison::class);
    }
}
