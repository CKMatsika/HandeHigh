<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLibraryAccess extends Model
{
    protected $table = 'student_library_access';

    protected $fillable = [
        'student_id',
        'is_member',
        'membership_date',
        'max_books',
        'max_days',
        'fine_per_day',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_member' => 'boolean',
        'max_books' => 'integer',
        'max_days' => 'integer',
        'fine_per_day' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
