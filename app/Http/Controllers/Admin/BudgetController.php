<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Account;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    public function index()
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $budgets = Budget::where('school_id', $school->id)
            ->withCount('lines')
            ->with(['creator', 'submitter', 'bursarReviewer', 'committeeReviewer', 'approver'])
            ->orderByDesc('fiscal_year')
            ->paginate(20);

        return view('admin.budgets.index', compact('budgets'));
    }

    public function create()
    {
        return view('admin.budgets.create');
    }

    public function store(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'budget_type' => ['required', 'in:annual,term,monthly'],
        ]);

        Budget::create([
            'school_id' => $school->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'fiscal_year' => $validated['fiscal_year'],
            'budget_type' => $validated['budget_type'],
            'status' => 'draft',
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.budgets.index')->with('success', 'Budget created successfully.');
    }

    public function show(Budget $budget)
    {
        $this->authorizeBudgetAccess($budget);
        
        $budget->load([
            'lines.account',
            'lines.costCenter',
            'lines.project',
            'creator',
            'submitter',
            'bursarReviewer',
            'committeeReviewer',
            'approver'
        ]);

        return view('admin.budgets.show', compact('budget'));
    }

    public function edit(Budget $budget)
    {
        $this->authorizeBudgetAccess($budget);
        
        if (!$budget->canBeEdited()) {
            abort(403, 'This budget cannot be edited in its current status.');
        }

        return view('admin.budgets.edit', compact('budget'));
    }

    public function update(Request $request, Budget $budget)
    {
        $this->authorizeBudgetAccess($budget);
        
        if (!$budget->canBeEdited()) {
            abort(403, 'This budget cannot be edited in its current status.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'budget_type' => ['required', 'in:annual,term,monthly'],
        ]);

        $budget->update($validated);

        return redirect()->route('admin.budgets.show', $budget)->with('success', 'Budget updated successfully.');
    }

    public function submit(Budget $budget)
    {
        $this->authorizeBudgetAccess($budget);
        
        if (!$budget->canBeSubmitted()) {
            abort(403, 'Budget cannot be submitted. Please add budget lines first.');
        }

        $budget->submit();

        return redirect()->route('admin.budgets.show', $budget)->with('success', 'Budget submitted to Bursar for review.');
    }

    public function bursarReview(Request $request, Budget $budget)
    {
        $this->authorizeBursarAccess($budget);
        
        if (!$budget->canBeReviewedByBursar()) {
            abort(403, 'This budget is not ready for Bursar review.');
        }

        $validated = $request->validate([
            'approved' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $budget->bursarReview($validated['approved'], $validated['notes']);

        $message = $validated['approved'] 
            ? 'Budget approved and sent to Finance Committee.' 
            : 'Budget rejected and returned to Accounts Clerk.';

        return redirect()->route('admin.budgets.show', $budget)->with('success', $message);
    }

    public function committeeReview(Request $request, Budget $budget)
    {
        $this->authorizeCommitteeAccess($budget);
        
        if (!$budget->canBeReviewedByCommittee()) {
            abort(403, 'This budget is not ready for Finance Committee review.');
        }

        $validated = $request->validate([
            'approved' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $budget->committeeReview($validated['approved'], $validated['notes']);

        $message = $validated['approved'] 
            ? 'Budget approved successfully.' 
            : 'Budget rejected and returned to Accounts Clerk.';

        return redirect()->route('admin.budgets.show', $budget)->with('success', $message);
    }

    public function activate(Budget $budget)
    {
        $this->authorizeBudgetAccess($budget);
        
        if (!$budget->canBeActivated()) {
            abort(403, 'Only approved budgets can be activated.');
        }

        $budget->activate();

        return redirect()->route('admin.budgets.show', $budget)->with('success', 'Budget activated successfully.');
    }

    public function close(Budget $budget)
    {
        $this->authorizeBudgetAccess($budget);
        
        if ($budget->status !== 'active') {
            abort(403, 'Only active budgets can be closed.');
        }

        $budget->close();

        return redirect()->route('admin.budgets.show', $budget)->with('success', 'Budget closed successfully.');
    }

    // Budget Lines Management
    public function createLine(Budget $budget)
    {
        $this->authorizeBudgetAccess($budget);
        
        if (!$budget->canBeEdited()) {
            abort(403, 'Budget lines can only be added to draft budgets.');
        }

        $accounts = Account::where('school_id', $budget->school_id)->get();
        $departments = Department::where('school_id', $budget->school_id)->get();

        return view('admin.budgets.lines.create', compact('budget', 'accounts', 'departments'));
    }

    public function storeLine(Request $request, Budget $budget)
    {
        $this->authorizeBudgetAccess($budget);
        
        if (!$budget->canBeEdited()) {
            abort(403, 'Budget lines can only be added to draft budgets.');
        }

        $validated = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'cost_center_id' => ['nullable', 'exists:departments,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'period' => ['nullable', 'integer', 'min:1', 'max:12'],
            'budgeted_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $budget->lines()->create($validated);
        $budget->recalculateTotals();

        return redirect()->route('admin.budgets.show', $budget)->with('success', 'Budget line added successfully.');
    }

    public function editLine(Budget $budget, BudgetLine $line)
    {
        $this->authorizeBudgetAccess($budget);
        
        if (!$budget->canBeEdited()) {
            abort(403, 'Budget lines can only be edited in draft budgets.');
        }

        $accounts = Account::where('school_id', $budget->school_id)->get();
        $departments = Department::where('school_id', $budget->school_id)->get();

        return view('admin.budgets.lines.edit', compact('budget', 'line', 'accounts', 'departments'));
    }

    public function updateLine(Request $request, Budget $budget, BudgetLine $line)
    {
        $this->authorizeBudgetAccess($budget);
        
        if (!$budget->canBeEdited()) {
            abort(403, 'Budget lines can only be edited in draft budgets.');
        }

        $validated = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'cost_center_id' => ['nullable', 'exists:departments,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'period' => ['nullable', 'integer', 'min:1', 'max:12'],
            'budgeted_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $line->update($validated);
        $budget->recalculateTotals();

        return redirect()->route('admin.budgets.show', $budget)->with('success', 'Budget line updated successfully.');
    }

    public function destroyLine(Budget $budget, BudgetLine $line)
    {
        $this->authorizeBudgetAccess($budget);
        
        if (!$budget->canBeEdited()) {
            abort(403, 'Budget lines can only be deleted from draft budgets.');
        }

        $line->delete();
        $budget->recalculateTotals();

        return redirect()->route('admin.budgets.show', $budget)->with('success', 'Budget line deleted successfully.');
    }

    // Authorization Methods
    private function authorizeBudgetAccess(Budget $budget)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school || $budget->school_id !== $school->id) {
            abort(403);
        }

        // Accounts clerks can only access budgets they created
        if ($user->hasRole('accounts-clerk') && $budget->created_by !== $user->id) {
            abort(403);
        }
    }

    private function authorizeBursarAccess(Budget $budget)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school || $budget->school_id !== $school->id || !$user->hasRole('bursar')) {
            abort(403);
        }
    }

    private function authorizeCommitteeAccess(Budget $budget)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school || $budget->school_id !== $school->id) {
            abort(403);
        }

        // Check if user is in finance committee or has appropriate role
        if (!$user->hasRole(['super-admin', 'school-admin', 'bursar']) && 
            !$user->sdaCommitteeMembers()->whereHas('role', function($q) {
                $q->where('name', 'Finance Committee');
            })->exists()) {
            abort(403);
        }
    }
}
