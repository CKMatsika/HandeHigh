<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'bill_id',
        'bank_account_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'check_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
