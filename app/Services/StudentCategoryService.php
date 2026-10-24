<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StudentCategoryService
{
    public function fromRequest(Request $request, ?Student $student = null): array
    {
        $data = $request->validate([
            'student_category' => 'sometimes|required|in:boarding,day,scholarship',
            'scholarship_residency' => 'required_if:student_category,scholarship|nullable|in:boarding,day',
        ]);
        $category = $data['student_category'] ?? null;
        $result = ['is_boarding' => $category === 'scholarship' ? $data['scholarship_residency'] === 'boarding'
            : ($category ? $category === 'boarding' : $request->boolean('is_boarding'))];
        if ($category) { $result['funding_status'] = $category === 'scholarship' ? 'scholarship' : 'regular'; }
        if ($student && !$result['is_boarding'] && $student->currentBedAssignment()->exists()) {
            throw ValidationException::withMessages(['student_category' => 'Release the current bed or use the residency transition workflow before changing to Day.']);
        }
        if ($student && ($result['funding_status'] ?? null) === 'regular'
            && $student->scholarships()->where('status', 'approved')->whereDate('ends_on', '>=', now()->toDateString())->exists()) {
            throw ValidationException::withMessages(['student_category' => 'Close current approved awards before changing funding status to regular.']);
        }
        return $result;
    }
}
