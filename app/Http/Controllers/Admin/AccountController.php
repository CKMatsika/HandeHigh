<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Rules\TenantExists;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $type = $request->input('type');
        $query = Account::where('school_id', $school->id)
            ->with('parent')
            ->orderBy('sort_order')
            ->orderBy('code');

        if ($type) {
            $query->where('type', $type);
        }

        $accounts = $query->paginate(50)->appends($request->only('type'));

        return view('admin.accounts.index', compact('accounts', 'type'));
    }

    public function create()
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $parents = Account::where('school_id', $school->id)
            ->orderBy('code')
            ->get();

        return view('admin.accounts.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('accounts', 'code')->where('school_id', $school->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['asset', 'liability', 'equity', 'revenue', 'expense'])],
            'category' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', TenantExists::make('accounts')],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        Account::create(array_merge($validated, [
            'school_id' => $school->id,
            'currency' => 'USD',
            'is_active' => true,
            'opening_balance' => 0,
        ]));

        return redirect()->route('admin.accounts.index')->with('success', 'Account created.');
    }

    public function edit(Account $account)
    {
        $school = Auth::user()?->school;
        if (! $school || $account->school_id !== $school->id) {
            abort(403);
        }

        $parents = Account::where('school_id', $school->id)
            ->where('id', '!=', $account->id)
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

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('accounts', 'code')->where('school_id', $school->id)->ignore($account->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['asset', 'liability', 'equity', 'revenue', 'expense'])],
            'category' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', TenantExists::make('accounts')],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ]);

        $account->update($validated);

        return redirect()->route('admin.accounts.index')->with('success', 'Account updated.');
    }

    public function toggle(Account $account)
    {
        $school = Auth::user()?->school;
        if (! $school || $account->school_id !== $school->id) {
            abort(403);
        }

        $account->update(['is_active' => ! $account->is_active]);

        return redirect()->route('admin.accounts.index')->with('success', 'Account status changed.');
    }
}
