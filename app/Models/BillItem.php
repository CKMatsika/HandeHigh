<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_id',
        'expense_account_id',
        'description',
        'category',
        'quantity',
        'unit_price',
        'line_total',
        'project_id',
        'cost_center_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function expenseAccount()
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function costCenter()
    {
        return $this->belongsTo(Department::class, 'cost_center_id');
    }
}
