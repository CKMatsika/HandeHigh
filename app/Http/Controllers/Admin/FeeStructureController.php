<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Department;
use App\Models\FeeStructure;
use App\Models\Project;
use App\Models\SchoolClass;
use App\Services\StudentClassMappingService;
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
            abort(403, 'No school context available for this user.');
        }

        $academicYear = $request->input('academic_year', now()->format('Y'));
        $term = $request->input('term');

        $query = FeeStructure::where('school_id', $school->id)
            ->where('academic_year', $academicYear);

        if ($term) {
            $query->where('term', $term);
        }

        $feeStructures = $query
            ->with(['revenueAccount', 'project', 'costCenter', 'parent', 'subVotes'])
            ->orderByRaw('parent_id IS NULL DESC')
            ->orderBy('grade')
            ->orderBy('category')
            ->orderBy('allocation_priority')
            ->orderBy('code')
            ->paginate(35)
            ->appends($request->only(['academic_year', 'term']));

        $parentFees = FeeStructure::where('school_id', $school->id)
            ->where('academic_year', $academicYear)
            ->whereNull('parent_id')
            ->orderBy('label')
            ->get();

        $terms = ['Term 1', 'Term 2', 'Term 3'];
        $categories = ['tuition', 'levy', 'subject', 'boarding', 'transport', 'other'];
        $standardVotes = \App\Services\Finance\HandeFeeVotesService::getStandardVotes();
        $revenueAccounts = Account::where('school_id', $school->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
        $projects = Project::where('school_id', $school->id)->orderBy('name')->get();
        $costCenters = Department::where('school_id', $school->id)->orderBy('name')->get();
        $classGradeOptions = SchoolClass::where('school_id', $school->id)
            ->where(function ($query) use ($academicYear) {
                $query->where('academic_year', $academicYear)->orWhereNull('academic_year');
            })
            ->get(['name', 'grade'])
            ->flatMap(fn (SchoolClass $class) => [$class->grade, $class->name])
            ->filter()->unique()->sort()->values();

        return view('admin.fees.index', [
            'school' => $school,
            'feeStructures' => $feeStructures,
            'parentFees' => $parentFees,
            'standardVotes' => $standardVotes,
            'academicYear' => $academicYear,
            'term' => $term,
            'terms' => $terms,
            'categories' => $categories,
            'revenueAccounts' => $revenueAccounts,
            'projects' => $projects,
            'costCenters' => $costCenters,
            'classGradeOptions' => $classGradeOptions,
        ]);
    }

    public function show(FeeStructure $fee)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school || $fee->school_id !== $school->id) {
            abort(403);
        }

        $fee->load(['revenueAccount', 'project', 'costCenter']);

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
            abort(403, 'No school context available for this user.');
        }

        $academicYear = $request->input('academic_year', now()->format('Y'));
        $term = $request->input('term', 'Term 3');

        $query = FeeStructure::where('school_id', $school->id)
            ->where('academic_year', $academicYear);

        if ($term) {
            $query->where('term', $term);
        }

        $feeStructures = $query
            ->with('subVotes')
            ->orderByRaw('parent_id IS NULL DESC')
            ->orderBy('allocation_priority')
            ->orderBy('code')
            ->get();

        $boardingParent = $feeStructures->first(fn($f) => $f->parent_id === null && ($f->residency_type === 'boarding' || str_contains(strtolower($f->label), 'boarding')));
        $dayParent = $feeStructures->first(fn($f) => $f->parent_id === null && ($f->residency_type === 'day' || str_contains(strtolower($f->label), 'day')));

        $signatories = \App\Services\Finance\HandeFeeVotesService::getAuthorisedSignatories();

        return view('admin.fees.print', [
            'school' => $school,
            'feeStructures' => $feeStructures,
            'boardingParent' => $boardingParent,
            'dayParent' => $dayParent,
            'signatories' => $signatories,
            'academicYear' => $academicYear,
            'term' => $term,
        ]);
    }

    public function syncHandeSchedule(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(403, 'No school context available for this user.');
        }

        $academicYear = $request->input('academic_year', '2026');
        $term = $request->input('term', 'Term 3');

        \App\Services\Finance\HandeFeeVotesService::syncSchoolVotes($school, $academicYear, $term);

        return redirect()
            ->route('admin.fees.index', ['academic_year' => $academicYear, 'term' => $term])
            ->with('status', "Official Hande High School {$academicYear} {$term} Fee Votes Schedule synchronized successfully.");
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (! $school) {
            abort(403, 'No school context available for this user.');
        }

        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'term' => ['required', 'string', 'max:20'],
            'grade' => ['nullable', 'string', 'max:50'],
            'category' => ['required', 'string', 'max:50'],
            'parent_id' => ['nullable', Rule::exists('fee_structures', 'id')->where('school_id', $school->id)],
            'residency_type' => ['nullable', 'string', 'in:all,boarding,day'],
            'vote_code' => ['nullable', 'string', 'max:50'],
            'allocation_priority' => ['nullable', 'integer', 'min:1'],
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
            'revenue_account_id' => ['nullable', Rule::exists('accounts', 'id')->where('school_id', $school->id)],
            'project_id' => ['nullable', Rule::exists('projects', 'id')->where('school_id', $school->id)],
            'cost_center_id' => ['nullable', Rule::exists('departments', 'id')->where('school_id', $school->id)],
        ]);

        $isOptional = $request->boolean('is_optional');
        $gradeScope = $this->normalizeGradeScope($school->id, $validated['grade'] ?? null, $validated['academic_year']);

        FeeStructure::create([
            'school_id' => $school->id,
            'academic_year' => $validated['academic_year'],
            'term' => $validated['term'],
            'grade' => $gradeScope,
            'parent_id' => $validated['parent_id'] ?? null,
            'residency_type' => $validated['residency_type'] ?? 'all',
            'vote_code' => $validated['vote_code'] ?? null,
            'allocation_priority' => $validated['allocation_priority'] ?? 10,
            'category' => $validated['category'],
            'code' => $validated['code'],
            'label' => $validated['label'],
            'amount' => $validated['amount'],
            'is_optional' => $isOptional,
            'subject_name' => $validated['subject_name'] ?? null,
            'service_type' => $validated['service_type'] ?? null,
            'revenue_account_id' => $validated['revenue_account_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'cost_center_id' => $validated['cost_center_id'] ?? null,
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
            abort(403, 'No school context available for this user.');
        }

        if ($fee->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'term' => ['required', 'string', 'max:20'],
            'grade' => ['nullable', 'string', 'max:50'],
            'category' => ['required', 'string', 'max:50'],
            'parent_id' => ['nullable', Rule::exists('fee_structures', 'id')->where('school_id', $school->id)],
            'residency_type' => ['nullable', 'string', 'in:all,boarding,day'],
            'vote_code' => ['nullable', 'string', 'max:50'],
            'allocation_priority' => ['nullable', 'integer', 'min:1'],
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
            'revenue_account_id' => ['nullable', Rule::exists('accounts', 'id')->where('school_id', $school->id)],
            'project_id' => ['nullable', Rule::exists('projects', 'id')->where('school_id', $school->id)],
            'cost_center_id' => ['nullable', Rule::exists('departments', 'id')->where('school_id', $school->id)],
        ]);

        $isOptional = $request->boolean('is_optional');
        $gradeScope = $this->normalizeGradeScope($school->id, $validated['grade'] ?? null, $validated['academic_year']);

        $fee->update([
            'academic_year' => $validated['academic_year'],
            'term' => $validated['term'],
            'grade' => $gradeScope,
            'parent_id' => $validated['parent_id'] ?? null,
            'residency_type' => $validated['residency_type'] ?? ($fee->residency_type ?? 'all'),
            'vote_code' => $validated['vote_code'] ?? null,
            'allocation_priority' => $validated['allocation_priority'] ?? 10,
            'category' => $validated['category'],
            'code' => $validated['code'],
            'label' => $validated['label'],
            'amount' => $validated['amount'],
            'is_optional' => $isOptional,
            'subject_name' => $validated['subject_name'] ?? null,
            'service_type' => $validated['service_type'] ?? null,
            'revenue_account_id' => $validated['revenue_account_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'cost_center_id' => $validated['cost_center_id'] ?? null,
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
            abort(403, 'No school context available for this user.');
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

    private function normalizeGradeScope(int $schoolId, ?string $grade, string $academicYear): ?string
    {
        if (blank($grade)) {
            return null;
        }

        $grade = trim($grade);
        $class = app(StudentClassMappingService::class)->resolveClass($schoolId, $grade, null, $academicYear);

        // Stream-specific fee structures keep the exact registered class name;
        // ordinary grade/form scopes retain their configured grade label.
        return $class?->name ?? $grade;
    }
}
