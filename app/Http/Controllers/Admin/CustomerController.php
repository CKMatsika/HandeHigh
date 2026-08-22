<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index()
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $customers = Customer::where('school_id', $school->id)
            ->orderBy('name')
            ->paginate(25);

        return view('admin.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('admin.customers.create');
    }

    public function store(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('customers', 'code')->where('school_id', $school->id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'customer_type' => ['required', 'in:individual,business,organization'],
            'credit_limit' => ['nullable', 'numeric'],
            'payment_terms' => ['required', 'string'],
        ]);

        Customer::create(array_merge($validated, [
            'school_id' => $school->id,
            'is_active' => true,
        ]));

        return redirect()->route('admin.customers.index')->with('success', 'Customer created.');
    }

    public function edit(Customer $customer)
    {
        $school = Auth::user()?->school;
        if (! $school || $customer->school_id !== $school->id) {
            abort(403);
        }

        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $school = Auth::user()?->school;
        if (! $school || $customer->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('customers', 'code')->where('school_id', $school->id)->ignore($customer->id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'customer_type' => ['required', 'in:individual,business,organization'],
            'credit_limit' => ['nullable', 'numeric'],
            'payment_terms' => ['required', 'string'],
            'is_active' => ['boolean'],
        ]);

        $customer->update($validated);

        return redirect()->route('admin.customers.index')->with('success', 'Customer updated.');
    }
}
