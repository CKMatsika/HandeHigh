<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAllowance extends Model
{
    protected $fillable = [
        'employee_id', 'allowance_type_id', 'amount',
        'is_active', 'effective_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'effective_date' => 'date',
    ];

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function allowanceType(): BelongsTo { return $this->belongsTo(AllowanceType::class); }
}
