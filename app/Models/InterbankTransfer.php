<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterbankTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'transfer_number',
        'transfer_date',
        'from_bank_account_id',
        'to_bank_account_id',
        'amount',
        'reference',
        'description',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function fromBankAccount()
    {
        return $this->belongsTo(Account::class, 'from_bank_account_id');
    }

    public function toBankAccount()
    {
        return $this->belongsTo(Account::class, 'to_bank_account_id');
    }

    // Add aliases for consistency
    public function fromAccount()
    {
        return $this->belongsTo(Account::class, 'from_bank_account_id');
    }

    public function toAccount()
    {
        return $this->belongsTo(Account::class, 'to_bank_account_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
