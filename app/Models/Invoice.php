<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'student_id',
        'guardian_id',
        'enrollment_id',
        'number',
        'type',
        'academic_year',
        'term',
        'total_amount',
        'balance',
        'issued_at',
        'due_date',
        'status',
        'school_snapshot',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'issued_at' => 'date',
        'due_date' => 'date',
        'school_snapshot' => 'array',
    ];

    /**
     * Resolve document branding payload using frozen snapshot or live school fallback.
     */
    public function getSchoolBrandingAttribute(): array
    {
        $school = $this->school ?: (auth()->check() ? auth()->user()?->school : null) ?: School::find($this->school_id);

        return app(\App\Services\Branding\SchoolDocumentBrandingService::class)
            ->getBrandingPayload($school ?? new School(), $this->school_snapshot);
    }

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

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Generate the next sequential invoice number with pessimistic concurrency safety.
     */
    public static function generateNextNumber(int $schoolId, ?string $year = null): string
    {
        $yearStr = $year ? str_replace('-', '', $year) : date('Y');
        $prefix = "INV-{$yearStr}-";

        $latestNumber = static::where('school_id', $schoolId)
            ->where('number', 'like', "{$prefix}%")
            ->orderByRaw('LENGTH(number) DESC, number DESC')
            ->lockForUpdate()
            ->value('number');

        $nextSeq = 1;
        if ($latestNumber && preg_match('/-(\d+)$/', $latestNumber, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        } else {
            $nextSeq = static::where('school_id', $schoolId)->count() + 1;
        }

        return $prefix . str_pad($nextSeq, 6, '0', STR_PAD_LEFT);
    }
}
