<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
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
        'vendor_type',
        'payment_terms',
        'bank_name',
        'bank_account',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getOutstandingBalanceAttribute()
    {
        return $this->bills()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sum('balance');
    }
}
