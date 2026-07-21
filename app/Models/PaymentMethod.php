<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'provider',
        'currency',
        'is_active',
        'description',
        'config',
        'transaction_fee_percentage',
        'fixed_transaction_fee',
        'minimum_amount',
        'maximum_amount',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
        'transaction_fee_percentage' => 'decimal:2',
        'fixed_transaction_fee' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCurrency($query, $currency)
    {
        return $query->where('currency', $currency);
    }

    public function scopeMobileMoney($query)
    {
        return $query->where('type', 'mobile_money');
    }

    public function scopeCard($query)
    {
        return $query->where('type', 'card');
    }

    public function scopeBankTransfer($query)
    {
        return $query->where('type', 'bank_transfer');
    }

    public function scopeOnline($query)
    {
        return $query->where('type', 'online');
    }

    // Methods
    public function calculateTransactionFee($amount)
    {
        $fee = 0;
        
        // Percentage fee
        if ($this->transaction_fee_percentage > 0) {
            $fee += ($amount * $this->transaction_fee_percentage) / 100;
        }
        
        // Fixed fee
        $fee += $this->fixed_transaction_fee;
        
        return $fee;
    }

    public function getTotalAmountWithFee($amount)
    {
        return $amount + $this->calculateTransactionFee($amount);
    }

    public function canProcessAmount($amount)
    {
        if ($amount < $this->minimum_amount) {
            return false;
        }
        
        if ($this->maximum_amount && $amount > $this->maximum_amount) {
            return false;
        }
        
        return true;
    }

    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            'mobile_money' => 'Mobile Money',
            'card' => 'Card Payment',
            'bank_transfer' => 'Bank Transfer',
            'online' => 'Online Payment',
            default => ucfirst($this->type),
        };
    }

    public function getTypeIconAttribute()
    {
        return match($this->type) {
            'mobile_money' => 'smartphone',
            'card' => 'credit-card',
            'bank_transfer' => 'building',
            'online' => 'globe',
            default => 'payment',
        };
    }

    public function getCurrencySymbolAttribute()
    {
        return match($this->currency) {
            'USD' => '$',
            'ZWL' => 'ZWL',
            'EUR' => '€',
            'GBP' => '£',
            default => $this->currency,
        };
    }

    // Relationships
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices()
    {
        return $this->hasManyThrough(Invoice::class, Payment::class);
    }
}
