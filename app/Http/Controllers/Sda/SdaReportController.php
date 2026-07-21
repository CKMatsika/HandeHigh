<?php

namespace App\Http\Controllers\Sda;

use App\Http\Controllers\Controller;
use App\Models\SdaCommittee;
use App\Models\SdaReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SdaReportController extends Controller
{
    /**
     * Display a listing of reports.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get committees the user belongs to
        $committees = SdaCommittee::whereHas('members', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->active()->get();
        
        // Get reports from committees the user belongs to
        $reports = SdaReport::whereHas('committee', function($query) use ($user) {
            $query->whereHas('members', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        })
        ->with(['committee', 'submitter', 'reviewer'])
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        return view('sda.reports.index', compact('reports', 'committees'));
    }

    /**
     * Display the specified report.
     */
    public function show(SdaReport $report)
    {
        $user = Auth::user();
        
        // Check if user belongs to the report's committee
        $belongs = $report->committee->members()->where('user_id', $user->id)->exists();
        
        if (!$belongs) {
            abort(403, 'You are not authorized to view this report.');
        }

        $report->load(['committee', 'submitter', 'reviewer']);

        return view('sda.reports.show', compact('report'));
    }

    /**
     * Show the form for creating a new report.
     */
    public function create()
    {
        $user = Auth::user();
        
        // Get committees the user belongs to
        $committees = SdaCommittee::whereHas('members', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->active()->get();

        if ($committees->isEmpty()) {
            return redirect()->route('sda.dashboard')
                ->with('error', 'You are not assigned to any SDA committee.');
        }

        return view('sda.reports.create', compact('committees'));
    }

    /**
     * Store a newly created report.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'sda_committee_id' => 'required|exists:sda_committees,id',
            'title' => 'required|string|max:255',
            'report_type' => 'required|in:chairman,secretary,treasurer,committee,special,annual',
            'executive_summary' => 'nullable|string',
            'content' => 'required|string',
            'recommendations' => 'nullable|string',
            'conclusions' => 'nullable|string',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
            'is_public' => 'boolean',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        // Check if user belongs to the committee
        $committee = SdaCommittee::findOrFail($validated['sda_committee_id']);
        $belongs = $committee->members()->where('user_id', $user->id)->exists();
        
        if (!$belongs) {
            return redirect()->route('sda.dashboard')
                ->with('error', 'You are not authorized to create reports for this committee.');
        }

        $validated['submitted_by'] = $user->id;
        $validated['status'] = 'draft';

        // Handle file upload
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('sda_reports', 'public');
            $validated['file_path'] = $path;
        }

        $report = SdaReport::create($validated);

        return redirect()
            ->route('sda.reports.show', $report)
            ->with('success', 'Report created successfully.');
    }

    /**
     * Show the form for editing the report.
     */
    public function edit(SdaReport $report)
    {
        $user = Auth::user();
        
        // Check if user is the submitter and report is editable
        if ($report->submitted_by !== $user->id || !$report->canBeEdited()) {
            abort(403, 'You are not authorized to edit this report.');
        }

        // Check if user belongs to the report's committee
        $belongs = $report->committee->members()->where('user_id', $user->id)->exists();
        
        if (!$belongs) {
            abort(403, 'You are not authorized to edit this report.');
        }

        $committees = SdaCommittee::whereHas('members', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->active()->get();

        return view('sda.reports.edit', compact('report', 'committees'));
    }

    /**
     * Update the specified report.
     */
    public function update(Request $request, SdaReport $report)
    {
        $user = Auth::user();
        
        // Check if user is the submitter and report is editable
        if ($report->submitted_by !== $user->id || !$report->canBeEdited()) {
            abort(403, 'You are not authorized to edit this report.');
        }

        // Check if user belongs to the report's committee
        $belongs = $report->committee->members()->where('user_id', $user->id)->exists();
        
        if (!$belongs) {
            abort(403, 'You are not authorized to edit this report.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'report_type' => 'required|in:chairman,secretary,treasurer,committee,special,annual',
            'executive_summary' => 'nullable|string',
            'content' => 'required|string',
            'recommendations' => 'nullable|string',
            'conclusions' => 'nullable|string',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
            'is_public' => 'boolean',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        // Handle file upload
        if ($request->hasFile('attachment')) {
            // Delete old file if exists
            if ($report->file_path) {
                Storage::disk('public')->delete($report->file_path);
            }
            
            $file = $request->file('attachment');
            $path = $file->store('sda_reports', 'public');
            $validated['file_path'] = $path;
        }

        $report->update($validated);

        return redirect()
            ->route('sda.reports.show', $report)
            ->with('success', 'Report updated successfully.');
    }

    /**
     * Submit the report for review.
     */
    public function submit(SdaReport $report)
    {
        $user = Auth::user();
        
        if (!$report->canBeEdited() || $report->submitted_by !== $user->id) {
            abort(403, 'You are not authorized to submit this report.');
        }

        $report->submit();

        return redirect()
            ->route('sda.reports.show', $report)
            ->with('success', 'Report submitted for review successfully.');
    }

    /**
     * Download the report attachment.
     */
    public function download(SdaReport $report)
    {
        $user = Auth::user();
        
        // Check if user belongs to the report's committee
        $belongs = $report->committee->members()->where('user_id', $user->id)->exists();
        
        if (!$belongs) {
            abort(403, 'You are not authorized to download this report.');
        }

        if (!$report->file_path) {
            return redirect()
                ->route('sda.reports.show', $report)
                ->with('error', 'No file attached to this report.');
        }

        return Storage::disk('public')->download($report->file_path);
    }
}
