<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankStudentPaymentReviewState extends Model
{
    protected $fillable = [
        'school_id', 'bank_transaction_id', 'student_id',
        'deferred_by', 'deferred_at', 'restored_by', 'restored_at',
    ];

    protected $casts = [
        'deferred_at' => 'datetime',
        'restored_at' => 'datetime',
    ];

    public function bankTransaction()
    {
        return $this->belongsTo(BankTransaction::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
