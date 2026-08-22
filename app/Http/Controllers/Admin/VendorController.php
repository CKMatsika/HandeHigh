<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    public function index()
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $vendors = Vendor::where('school_id', $school->id)
            ->orderBy('name')
            ->paginate(25);

        return view('admin.vendors.index', compact('vendors'));
    }

    public function create()
    {
        return view('admin.vendors.create');
    }

    public function store(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('vendors', 'code')->where('school_id', $school->id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'vendor_type' => ['required', 'string'],
            'payment_terms' => ['required', 'string'],
        ]);

        Vendor::create(array_merge($validated, [
            'school_id' => $school->id,
            'is_active' => true,
        ]));

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor created.');
    }

    public function edit(Vendor $vendor)
    {
        $school = Auth::user()?->school;
        if (! $school || $vendor->school_id !== $school->id) {
            abort(403);
        }

        return view('admin.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $school = Auth::user()?->school;
        if (! $school || $vendor->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('vendors', 'code')->where('school_id', $school->id)->ignore($vendor->id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'vendor_type' => ['required', 'string'],
            'payment_terms' => ['required', 'string'],
            'is_active' => ['boolean'],
        ]);

        $vendor->update($validated);

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor updated.');
    }
}
