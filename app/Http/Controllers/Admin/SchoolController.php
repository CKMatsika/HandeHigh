<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;

class SchoolController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', School::class);

        $schools = School::orderBy('name')->paginate(15);

        return view('admin.schools.index', [
            'schools' => $schools,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->route('admin.schools.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', School::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:schools,code'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        School::create($data);

        return redirect()
            ->route('admin.schools.index')
            ->with('status', 'School created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('admin.schools.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return redirect()->route('admin.schools.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $school = School::findOrFail($id);
        $this->authorize('update', $school);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:schools,code,' . $school->id],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $school->update($data);

        return redirect()
            ->route('admin.schools.index')
            ->with('status', 'School updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $school = School::findOrFail($id);
        $this->authorize('delete', $school);

        $school->delete();

        return redirect()
            ->route('admin.schools.index')
            ->with('status', 'School deleted successfully.');
    }
}
