<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'residency_type',
        'vote_code',
        'allocation_priority',
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
        'allocation_priority' => 'integer',
        'is_optional' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(FeeStructure::class, 'parent_id');
    }

    public function subVotes()
    {
        return $this->hasMany(FeeStructure::class, 'parent_id')->orderBy('allocation_priority');
    }

    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeVotes($query)
    {
        return $query->whereNotNull('vote_code');
    }

    public function scopeForResidency($query, string $residency)
    {
        return $query->where(function ($q) use ($residency) {
            $q->where('residency_type', 'all')
              ->orWhere('residency_type', $residency);
        });
    }

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
