<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_id', 'employee_id',
        'basic_salary', 'housing_allowance', 'transport_allowance',
        'communication_allowance', 'education_allowance', 'leave_allowance',
        'bonus', 'overtime', 'other_earnings', 'other_earnings_desc',
        'school_top_up',
        'gross_pay',
        'paye', 'aids_levy', 'nssa_employee', 'nssa_employer',
        'trade_union', 'nec', 'loan_repayment',
        'other_deductions', 'other_deductions_desc',
        'total_deductions', 'net_pay',
        'payment_method', 'bank_account', 'status', 'notes',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'housing_allowance' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'communication_allowance' => 'decimal:2',
        'education_allowance' => 'decimal:2',
        'leave_allowance' => 'decimal:2',
        'bonus' => 'decimal:2',
        'overtime' => 'decimal:2',
        'other_earnings' => 'decimal:2',
        'school_top_up' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'paye' => 'decimal:2',
        'aids_levy' => 'decimal:2',
        'nssa_employee' => 'decimal:2',
        'nssa_employer' => 'decimal:2',
        'trade_union' => 'decimal:2',
        'nec' => 'decimal:2',
        'loan_repayment' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
    ];

    public function payroll(): BelongsTo { return $this->belongsTo(Payroll::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
}
