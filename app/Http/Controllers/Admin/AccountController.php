<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Rules\TenantExists;
use App\Services\Finance\ChartOfAccountsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function __construct(
        protected ChartOfAccountsService $coaService
    ) {}

    public function index(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $type = $request->input('type');
        $search = $request->input('search');
        $viewMode = $request->input('view', 'tree'); // 'tree' or 'table'
        $isActive = $request->input('is_active');
        $isPostable = $request->input('is_postable');

        $filters = [
            'type' => $type,
            'search' => $search,
            'is_active' => $isActive,
            'is_postable' => $isPostable,
        ];

        $statistics = $this->coaService->getStatistics($school);

        if ($viewMode === 'tree') {
            $tree = $this->coaService->getAccountTree($school, $filters);
            $accounts = null;
        } else {
            $tree = null;
            $query = Account::where('school_id', $school->id)
                ->with('parent')
                ->orderBy('sort_order')
                ->orderBy('code');

            if ($type) {
                $query->where('type', $type);
            }
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            }
            if ($isActive !== null && $isActive !== '') {
                $query->where('is_active', (bool) $isActive);
            }
            if ($isPostable !== null && $isPostable !== '') {
                $query->where('is_postable', (bool) $isPostable);
            }

            $accounts = $query->paginate(50)->appends($request->all());
        }

        return view('admin.accounts.index', compact('tree', 'accounts', 'type', 'search', 'viewMode', 'statistics', 'isActive', 'isPostable'));
    }

    public function create(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $selectedParentId = $request->input('parent_id');
        $selectedParent = $selectedParentId ? Account::where('school_id', $school->id)->find($selectedParentId) : null;

        $parents = Account::where('school_id', $school->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        return view('admin.accounts.create', compact('parents', 'selectedParent'));
    }

    public const VALID_CATEGORIES = [
        'current_asset', 'fixed_asset', 'bank', 'cash', 'receivable',
        'current_liability', 'long_term_liability', 'payable',
        'equity', 'retained_earnings',
        'tuition_revenue', 'other_revenue', 'grants',
        'salary_expense', 'utility_expense', 'maintenance_expense', 'supply_expense', 'other_expense',
    ];

    private function normalizeCategory(?string $category): ?string
    {
        if (! $category) {
            return null;
        }

        $normalized = strtolower(trim($category));
        return match ($normalized) {
            'bank account', 'bank_account', 'banks', 'cash & bank', 'bank accounts' => 'bank',
            'petty cash', 'cash on hand', 'cash account' => 'cash',
            'current asset', 'current assets' => 'current_asset',
            'fixed asset', 'fixed assets' => 'fixed_asset',
            'receivables', 'debtors', 'accounts receivable' => 'receivable',
            'payables', 'creditors', 'accounts payable' => 'payable',
            'current liabilities', 'current liability' => 'current_liability',
            'long term liability', 'long term liabilities' => 'long_term_liability',
            'tuition', 'tuition revenue', 'school fees', 'fees' => 'tuition_revenue',
            'salary', 'salaries', 'payroll' => 'salary_expense',
            'utilities' => 'utility_expense',
            'maintenance' => 'maintenance_expense',
            'supplies' => 'supply_expense',
            default => $normalized,
        };
    }

    public function store(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        if ($request->has('category')) {
            $request->merge(['category' => $this->normalizeCategory($request->input('category'))]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('accounts', 'code')->where('school_id', $school->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['asset', 'liability', 'equity', 'revenue', 'expense'])],
            'category' => ['required', 'string', 'max:100', Rule::in(self::VALID_CATEGORIES)],
            'parent_id' => ['nullable', TenantExists::make('accounts')],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_postable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $account = $this->coaService->createAccount($school, array_merge($validated, [
                'is_postable' => $request->has('is_postable') ? (bool) $request->input('is_postable') : true,
                'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : true,
                'opening_balance' => (float) ($request->input('opening_balance') ?? 0),
            ]));

            return redirect()->route('admin.accounts.index')->with('success', 'Account [' . $account->code . '] created successfully.');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function edit(Account $account)
    {
        $school = Auth::user()?->school;
        if (! $school || $account->school_id !== $school->id) {
            abort(403);
        }

        // Exclude self and all descendants to prevent circular hierarchy
        $descendantIds = $account->descendants()->pluck('id')->push($account->id)->all();

        $parents = Account::where('school_id', $school->id)
            ->whereNotIn('id', $descendantIds)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        return view('admin.accounts.edit', compact('account', 'parents'));
    }

    public function update(Request $request, Account $account)
    {
        $school = Auth::user()?->school;
        if (! $school || $account->school_id !== $school->id) {
            abort(403);
        }

        if ($request->has('category')) {
            $request->merge(['category' => $this->normalizeCategory($request->input('category'))]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('accounts', 'code')->where('school_id', $school->id)->ignore($account->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['asset', 'liability', 'equity', 'revenue', 'expense'])],
            'category' => ['required', 'string', 'max:100', Rule::in(self::VALID_CATEGORIES)],
            'parent_id' => ['nullable', TenantExists::make('accounts')],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_postable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $this->coaService->updateAccount($account, array_merge($validated, [
                'is_postable' => $request->has('is_postable') ? (bool) $request->input('is_postable') : $account->is_postable,
                'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : $account->is_active,
            ]));

            return redirect()->route('admin.accounts.index')->with('success', 'Account updated successfully.');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function toggle(Account $account)
    {
        $school = Auth::user()?->school;
        if (! $school || $account->school_id !== $school->id) {
            abort(403);
        }

        $account->update(['is_active' => ! $account->is_active]);

        return redirect()->route('admin.accounts.index')->with('success', 'Account status updated.');
    }

    public function destroy(Account $account)
    {
        $school = Auth::user()?->school;
        if (! $school || $account->school_id !== $school->id) {
            abort(403);
        }

        try {
            $this->coaService->deleteAccount($account);
            return redirect()->route('admin.accounts.index')->with('success', "Account [{$account->code}] deleted successfully.");
        } catch (\Throwable $e) {
            return redirect()->route('admin.accounts.index')->with('error', $e->getMessage());
        }
    }
}
