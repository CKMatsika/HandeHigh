<?php

namespace App\Http\Requests\Timetable;

use App\Rules\TenantExists;
use Illuminate\Foundation\Http\FormRequest;

class SimulateGenerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seed' => 'nullable|string|max:100',
            'preserve_locked' => 'nullable|boolean',
            'class_id' => ['nullable', TenantExists::make('classes')],
            'teacher_id' => ['nullable', TenantExists::make('teachers')],
            'subject_id' => ['nullable', TenantExists::make('subjects')],
            'day_of_week' => 'nullable|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'weights' => 'nullable|array',
            'candidate_id' => ['nullable', TenantExists::make('timetable_candidates')],
        ];
    }
}
