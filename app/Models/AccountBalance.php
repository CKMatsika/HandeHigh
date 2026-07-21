<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'account_id',
        'fiscal_year',
        'period',
        'debit_total',
        'credit_total',
        'balance',
    ];

    protected $casts = [
        'debit_total' => 'decimal:2',
        'credit_total' => 'decimal:2',
        'balance' => 'decimal:2',
        'fiscal_year' => 'integer',
        'period' => 'integer',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeForPeriod($query, $year, $period = null)
    {
        $query->where('fiscal_year', $year);
        if ($period !== null) {
            $query->where('period', $period);
        }
        return $query;
    }
}
