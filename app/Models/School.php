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
        'code',
        'email',
        'phone',
        'address',
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
        'timezone',
        'currency',
    ];

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
