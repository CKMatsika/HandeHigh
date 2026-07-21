<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankStatementImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'bank_account_id',
        'import_type',
        'filename',
        'statement_start_date',
        'statement_end_date',
        'opening_balance',
        'closing_balance',
        'total_transactions',
        'status',
        'error_message',
        'metadata',
        'imported_by',
    ];

    protected $casts = [
        'statement_start_date' => 'date',
        'statement_end_date' => 'date',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'total_transactions' => 'integer',
        'metadata' => 'array',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function lines()
    {
        return $this->hasMany(BankStatementLine::class);
    }

    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
