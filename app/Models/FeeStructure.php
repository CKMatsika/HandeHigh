<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'academic_year',
        'term',
        'grade',
        'category',
        'code',
        'label',
        'amount',
        'is_optional',
        'subject_name',
        'service_type',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_optional' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
