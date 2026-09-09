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
        'week_ending',
        'day_of_week',
        'topic',
        'sub_topic',
        'objectives',
        'competencies_skills',
        'som_media',
        'facility_equipment',
        'methods_activities',
        'teaching_methods',
        'resources',
        'assessment',
        'evaluation',
        'remarks',
        'sort_order',
    ];

    protected $casts = [
        'week_number' => 'integer',
        'week_ending' => 'date',
        'sort_order' => 'integer',
    ];

    public function schemeOfWork()
    {
        return $this->belongsTo(SchemeOfWork::class, 'scheme_of_work_id');
    }
}
