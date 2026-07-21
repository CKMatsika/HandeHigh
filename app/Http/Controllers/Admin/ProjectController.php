<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        $projects = Project::where('school_id', $school->id)
            ->when($request->status, function($query, $status) {
                if ($status === 'active') {
                    $query->where('status', 'active');
                } elseif ($status === 'completed') {
                    $query->where('status', 'completed');
                } elseif ($status === 'approved') {
                    $query->where('status', 'approved');
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.projects.index', compact('projects'));
    }

    public function create()
    {
        $user = auth()->user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        return view('admin.projects.create');
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'budget_amount' => ['required', 'numeric', 'min:0'],
            'project_type' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:planning,approved,active,completed,cancelled'],
        ]);

        $project = Project::create([
            'school_id' => $school->id,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'budget_amount' => $validated['budget_amount'],
            'status' => $validated['status'],
            'project_type' => $validated['project_type'],
        ]);

        return redirect()
            ->route('admin.projects.index')
            ->with('status', 'Project created successfully');
    }

    public function show(Project $project)
    {
        $this->authorizeSchoolAccess($project);
        
        $project->load(['journalEntries', 'budgetLines']);
        return view('admin.projects.show', compact('project'));
    }

    public function edit(Project $project)
    {
        $this->authorizeSchoolAccess($project);
        
        return view('admin.projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $this->authorizeSchoolAccess($project);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'budget_amount' => ['required', 'numeric', 'min:0'],
            'project_type' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:planning,approved,active,completed,cancelled'],
        ]);

        $project->update($validated);

        return redirect()
            ->route('admin.projects.index')
            ->with('status', 'Project updated successfully');
    }

    private function authorizeSchoolAccess($model)
    {
        $user = auth()->user();
        $school = $user?->school;

        if (!$school || $model->school_id !== $school->id) {
            abort(403);
        }
    }
}
