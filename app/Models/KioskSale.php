<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KioskSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'receipt_number',
        'sale_date',
        'payment_method',
        'bank_account_id',
        'subtotal',
        'tax_amount',
        'grand_total',
        'cashier_id',
        'customer_name',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(KioskSaleItem::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function journalBatch(): HasOne
    {
        return $this->hasOne(JournalBatch::class, 'source_id')->where('source_type', 'receipt');
    }
}
