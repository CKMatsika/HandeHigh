<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlashCardSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'teacher_id',
        'subject_id',
        'school_class_id',
        'title',
        'description',
        'status',
        'source_type',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function items()
    {
        return $this->hasMany(FlashCardItem::class, 'flash_card_set_id')->orderBy('sort_order');
    }

    public function studySessions()
    {
        return $this->hasMany(FlashCardStudySession::class, 'flash_card_set_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
