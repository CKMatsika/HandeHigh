<?php

namespace App\Http\Requests\Timetable;

use App\Rules\TenantExists;
use Illuminate\Foundation\Http\FormRequest;

class GenerateTimetableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seed' => 'nullable|string|max:100',
            'candidate_count' => 'nullable|integer|min:1|max:10',
            'preserve_locked' => 'nullable|boolean',
            'class_id' => ['nullable', TenantExists::make('classes')],
            'teacher_id' => ['nullable', TenantExists::make('teachers')],
            'subject_id' => ['nullable', TenantExists::make('subjects')],
            'day_of_week' => 'nullable|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'weights' => 'nullable|array',
            'weights.subject_daily_spread' => 'nullable|numeric|min:0|max:100',
            'weights.teacher_workload_balance' => 'nullable|numeric|min:0|max:100',
            'weights.class_workload_balance' => 'nullable|numeric|min:0|max:100',
            'weights.consecutive_lesson_penalty' => 'nullable|numeric|min:0|max:100',
            'weights.core_subject_morning_preference' => 'nullable|numeric|min:0|max:100',
            'weights.teacher_free_period_balance' => 'nullable|numeric|min:0|max:100',
            'weights.room_utilization' => 'nullable|numeric|min:0|max:100',
            'weights.gap_penalty' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
