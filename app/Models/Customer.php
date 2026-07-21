<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'tax_id',
        'customer_type',
        'credit_limit',
        'payment_terms',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getOutstandingBalanceAttribute()
    {
        // Calculate total outstanding from receipts minus payments
        $totalReceipts = $this->receipts()->sum('grand_total');
        // This would need to be calculated based on actual payment tracking
        return $totalReceipts;
    }
}
