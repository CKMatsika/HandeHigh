<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchemeOfWorkItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'scheme_of_work_id',
        'week_number',
        'day_of_week',
        'topic',
        'sub_topic',
        'objectives',
        'teaching_methods',
        'resources',
        'assessment',
        'remarks',
        'sort_order',
    ];

    protected $casts = [
        'week_number' => 'integer',
        'sort_order' => 'integer',
    ];

    public function schemeOfWork()
    {
        return $this->belongsTo(SchemeOfWork::class, 'scheme_of_work_id');
    }
}
