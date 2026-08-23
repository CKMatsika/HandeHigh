<?php

namespace App\Http\Requests\Timetable;

use App\Rules\TenantExists;
use Illuminate\Foundation\Http\FormRequest;

class StoreTimetableRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'school_class_id' => ['required', TenantExists::make('classes')],
            'subject_id' => ['required', TenantExists::make('subjects')],
            'teacher_id' => ['nullable', TenantExists::make('teachers')],
            'room_id' => ['nullable', TenantExists::make('rooms')],
            'weekly_periods' => 'required|integer|min:1|max:30',
            'preferred_days' => 'nullable|array',
            'preferred_periods' => 'nullable|array',
            'max_daily_lessons' => 'nullable|integer|min:1|max:5',
            'is_double_period_allowed' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:1|max:10',
        ];
    }
}
