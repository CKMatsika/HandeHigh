<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KioskTillSession extends Model
{
    protected $fillable = [
        'school_id', 'till_label', 'cashier_id', 'opened_by', 'closed_by', 'status', 'active_key',
        'opening_float', 'cash_sales', 'mobile_money_sales', 'bank_transfer_sales', 'card_sales',
        'total_sales', 'sales_count', 'expected_cash', 'counted_cash', 'cash_variance',
        'z_reading_number', 'opening_notes', 'closing_notes', 'opened_at', 'closed_at',
    ];

    protected $casts = [
        'opening_float' => 'decimal:2', 'cash_sales' => 'decimal:2',
        'mobile_money_sales' => 'decimal:2', 'bank_transfer_sales' => 'decimal:2',
        'card_sales' => 'decimal:2', 'total_sales' => 'decimal:2',
        'expected_cash' => 'decimal:2', 'counted_cash' => 'decimal:2',
        'cash_variance' => 'decimal:2', 'opened_at' => 'datetime', 'closed_at' => 'datetime',
    ];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function cashier(): BelongsTo { return $this->belongsTo(User::class, 'cashier_id'); }
    public function openedBy(): BelongsTo { return $this->belongsTo(User::class, 'opened_by'); }
    public function closedBy(): BelongsTo { return $this->belongsTo(User::class, 'closed_by'); }
    public function sales(): HasMany { return $this->hasMany(KioskSale::class, 'kiosk_till_session_id'); }
}
