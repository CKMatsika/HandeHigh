<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'account_name',
        'account_number',
        'bank_name',
        'branch_name',
        'account_type',
        'opening_balance',
        'current_balance',
        'currency',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function cashbookEntries()
    {
        return $this->hasMany(Cashbook::class);
    }

    public function getFormattedBalanceAttribute()
    {
        return number_format($this->current_balance, 2);
    }

    public function updateBalance($amount, $type)
    {
        if ($type === 'income') {
            $this->current_balance += $amount;
        } elseif ($type === 'expense') {
            $this->current_balance -= $amount;
        }
        
        $this->save();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBank($query, $bankName)
    {
        return $query->where('bank_name', $bankName);
    }
}
