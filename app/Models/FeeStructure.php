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
        'revenue_account_id',
        'project_id',
        'cost_center_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_optional' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function revenueAccount()
    {
        return $this->belongsTo(Account::class, 'revenue_account_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(Department::class, 'cost_center_id');
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
