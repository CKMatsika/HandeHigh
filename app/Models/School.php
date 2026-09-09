<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'code',
        'email',
        'phone',
        'telephone',
        'mobile',
        'whatsapp',
        'address',
        'postal_address',
        'city',
        'state',
        'country',
        'postal_code',
        'website',
        'motto',
        'description',
        'logo',
        'established_year',
        'school_type',
        'registration_number',
        'zimsec_center_number',
        'principal_name',
        'bursar_name',
        'administrator_name',
        'primary_color',
        'secondary_color',
        'footer_text',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'bank_branch',
        'payment_instructions',
        'timezone',
        'currency',
    ];

    /**
     * Get the full branding data payload for document generation.
     */
    public function getBrandingDataAttribute(): array
    {
        return app(\App\Services\Branding\SchoolDocumentBrandingService::class)->getBrandingPayload($this);
    }

    /**
     * Get public logo URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        return app(\App\Services\Branding\SchoolDocumentBrandingService::class)->getLogoUrl($this);
    }

    /**
     * Get base64 logo data for offline print/PDF.
     */
    public function getLogoBase64Attribute(): ?string
    {
        return app(\App\Services\Branding\SchoolDocumentBrandingService::class)->getLogoBase64($this);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function curricula()
    {
        return $this->hasMany(Curriculum::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }

    public function guardians()
    {
        return $this->hasMany(Guardian::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function gradeSchemes()
    {
        return $this->hasMany(GradeScheme::class);
    }

    public function performanceReports()
    {
        return $this->hasMany(PerformanceReport::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    public function parents()
    {
        return $this->hasMany(ParentModel::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function books()
    {
        return $this->hasMany(Book::class);
    }

    public function borrowRecords()
    {
        return $this->hasMany(BorrowRecord::class);
    }

    public function settings()
    {
        return $this->hasMany(SchoolSetting::class);
    }

    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    public function staff()
    {
        return $this->hasMany(Staff::class);
    }

    public function cashbookEntries()
    {
        return $this->hasMany(Cashbook::class);
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class);
    }

    public function schoolPeriods()
    {
        return $this->hasMany(SchoolPeriod::class);
    }

    public function fixedActivities()
    {
        return $this->hasMany(TimetableFixedActivity::class);
    }

    public function timetableExaminations()
    {
        return $this->hasMany(TimetableExamination::class);
    }

    public function timetableRequirements()
    {
        return $this->hasMany(TimetableRequirement::class);
    }

    public function timetableGenerationRuns()
    {
        return $this->hasMany(TimetableGenerationRun::class);
    }

    public function timetableCandidates()
    {
        return $this->hasMany(TimetableCandidate::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function getSetting($key, $default = null)
    {
        $setting = $this->settings()->where('key', $key)->first();
        return $setting ? $setting->typed_value : $default;
    }

    public function setSetting($key, $value, $type = 'string', $category = 'general', $description = null, $isPublic = false)
    {
        return $this->settings()->updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) || is_object($value) ? json_encode($value) : $value,
                'type' => $type,
                'category' => $category,
                'description' => $description,
                'is_public' => $isPublic,
            ]
        );
    }
}
