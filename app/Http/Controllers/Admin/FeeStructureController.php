<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FeeStructureController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $academicYear = $request->input('academic_year', now()->format('Y'));
        $term = $request->input('term');

        $query = FeeStructure::where('school_id', $school->id)
            ->where('academic_year', $academicYear);

        if ($term) {
            $query->where('term', $term);
        }

        $feeStructures = $query
            ->orderBy('grade')
            ->orderBy('category')
            ->orderBy('code')
            ->paginate(25)
            ->appends($request->only(['academic_year', 'term']));

        $terms = ['Term 1', 'Term 2', 'Term 3'];
        $categories = ['tuition', 'levy', 'subject', 'boarding', 'transport', 'other'];

        return view('admin.fees.index', [
            'school' => $school,
            'feeStructures' => $feeStructures,
            'academicYear' => $academicYear,
            'term' => $term,
            'terms' => $terms,
            'categories' => $categories,
        ]);
    }

    public function show(FeeStructure $fee)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $fee->school_id !== $school->id) {
            abort(403);
        }

        return view('admin.fees.show', [
            'school' => $school,
            'fee' => $fee,
        ]);
    }

    public function print(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $academicYear = $request->input('academic_year', now()->format('Y'));
        $term = $request->input('term');

        $query = FeeStructure::where('school_id', $school->id)
            ->where('academic_year', $academicYear);

        if ($term) {
            $query->where('term', $term);
        }

        $feeStructures = $query
            ->orderBy('grade')
            ->orderBy('category')
            ->orderBy('code')
            ->get();

        return view('admin.fees.print', [
            'school' => $school,
            'feeStructures' => $feeStructures,
            'academicYear' => $academicYear,
            'term' => $term,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'term' => ['required', 'string', 'max:20'],
            'grade' => ['nullable', 'string', 'max:50'],
            'category' => ['required', 'string', 'max:50'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('fee_structures', 'code')->where(function ($q) use ($school, $request) {
                    return $q->where('school_id', $school->id)
                        ->where('academic_year', $request->input('academic_year'))
                        ->where('term', $request->input('term'))
                        ->where('grade', $request->input('grade'));
                }),
            ],
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'is_optional' => ['nullable', 'boolean'],
            'subject_name' => ['nullable', 'string', 'max:255'],
            'service_type' => ['nullable', 'string', 'max:50'],
        ]);

        $isOptional = $request->boolean('is_optional');

        FeeStructure::create([
            'school_id' => $school->id,
            'academic_year' => $validated['academic_year'],
            'term' => $validated['term'],
            'grade' => $validated['grade'] ?? null,
            'category' => $validated['category'],
            'code' => $validated['code'],
            'label' => $validated['label'],
            'amount' => $validated['amount'],
            'is_optional' => $isOptional,
            'subject_name' => $validated['subject_name'] ?? null,
            'service_type' => $validated['service_type'] ?? null,
        ]);

        return redirect()
            ->route('admin.fees.index', [
                'academic_year' => $validated['academic_year'],
                'term' => $validated['term'],
            ])
            ->with('status', 'Fee item created successfully.');
    }

    public function update(Request $request, FeeStructure $fee)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        if ($fee->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'term' => ['required', 'string', 'max:20'],
            'grade' => ['nullable', 'string', 'max:50'],
            'category' => ['required', 'string', 'max:50'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('fee_structures', 'code')->where(function ($q) use ($school, $request) {
                    return $q->where('school_id', $school->id)
                        ->where('academic_year', $request->input('academic_year'))
                        ->where('term', $request->input('term'))
                        ->where('grade', $request->input('grade'));
                })->ignore($fee->id),
            ],
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'is_optional' => ['nullable', 'boolean'],
            'subject_name' => ['nullable', 'string', 'max:255'],
            'service_type' => ['nullable', 'string', 'max:50'],
        ]);

        $isOptional = $request->boolean('is_optional');

        $fee->update([
            'academic_year' => $validated['academic_year'],
            'term' => $validated['term'],
            'grade' => $validated['grade'] ?? null,
            'category' => $validated['category'],
            'code' => $validated['code'],
            'label' => $validated['label'],
            'amount' => $validated['amount'],
            'is_optional' => $isOptional,
            'subject_name' => $validated['subject_name'] ?? null,
            'service_type' => $validated['service_type'] ?? null,
        ]);

        return redirect()
            ->route('admin.fees.index', [
                'academic_year' => $validated['academic_year'],
                'term' => $validated['term'],
            ])
            ->with('status', 'Fee item updated successfully.');
    }

    public function destroy(FeeStructure $fee)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(400, 'No school context available for this user.');
        }

        if ($fee->school_id !== $school->id) {
            abort(403);
        }
        $academicYear = $fee->academic_year;
        $term = $fee->term;

        $fee->delete();

        return redirect()
            ->route('admin.fees.index', [
                'academic_year' => $academicYear,
                'term' => $term,
            ])
            ->with('status', 'Fee item deleted successfully.');
    }
}
