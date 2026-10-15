<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'student_id',
        'guardian_id',
        'method',
        'account_id',
        'reference',
        'amount',
        'paid_at',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function guardian()
    {
        return $this->belongsTo(Guardian::class);
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function cashbookEntries()
    {
        return $this->hasMany(Cashbook::class, 'related_payment_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Resolve document branding payload for official receipt printing.
     */
    public function getSchoolBrandingAttribute(): array
    {
        $school = $this->school ?: (auth()->check() ? auth()->user()?->school : null) ?: School::find($this->school_id);

        return app(\App\Services\Branding\SchoolDocumentBrandingService::class)
            ->getBrandingPayload($school ?? new School());
    }
}
