<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'credit_note_number',
        'credit_note_date',
        'type',
        'customer_id',
        'student_id',
        'invoice_id',
        'total_amount',
        'applied_amount',
        'balance',
        'status',
        'reason',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'credit_note_date' => 'date',
        'total_amount' => 'decimal:2',
        'applied_amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
