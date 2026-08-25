<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'receipt_number',
        'receipt_date',
        'type',
        'customer_id',
        'customer_name',
        'total_amount',
        'tax_amount',
        'grand_total',
        'payment_method',
        'bank_account_id',
        'reference',
        'description',
        'notes',
        'school_snapshot',
        'created_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'school_snapshot' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Receipt $receipt) {
            if (empty($receipt->school_snapshot) && $receipt->school_id) {
                $school = $receipt->school ?: \App\Models\School::find($receipt->school_id);
                if ($school) {
                    $receipt->school_snapshot = app(\App\Services\Branding\SchoolDocumentBrandingService::class)->generateSnapshot($school);
                }
            }
        });
    }

    /**
     * Resolve document branding payload using frozen snapshot or live school fallback.
     */
    public function getSchoolBrandingAttribute(): array
    {
        return app(\App\Services\Branding\SchoolDocumentBrandingService::class)
            ->getBrandingPayload($this->school, $this->school_snapshot);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(ReceiptItem::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journalBatch()
    {
        return $this->hasOne(JournalBatch::class, 'source_id')->where('source_type', 'receipt');
    }
}
