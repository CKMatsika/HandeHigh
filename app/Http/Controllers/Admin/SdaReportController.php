<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SdaCommittee;
use App\Models\SdaReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SdaReportController extends Controller
{
    /**
     * Display a listing of reports.
     */
    public function index()
    {
        $reports = SdaReport::with(['committee', 'submitter', 'reviewer'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.sda.reports.index', compact('reports'));
    }

    /**
     * Show the form for creating a new report.
     */
    public function create()
    {
        $committees = SdaCommittee::active()->get();
        return view('admin.sda.reports.create', compact('committees'));
    }

    /**
     * Store a newly created report.
     */
    public function store(Request $request)
    {
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

        $validated['submitted_by'] = auth()->id();
        $validated['status'] = 'draft';

        // Handle file upload
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('sda_reports', 'public');
            $validated['file_path'] = $path;
        }

        $report = SdaReport::create($validated);

        return redirect()
            ->route('admin.sda.reports.show', $report)
            ->with('success', 'Report created successfully.');
    }

    /**
     * Display the specified report.
     */
    public function show(SdaReport $report)
    {
        $report->load(['committee', 'submitter', 'reviewer']);
        return view('admin.sda.reports.show', compact('report'));
    }

    /**
     * Show the form for editing the report.
     */
    public function edit(SdaReport $report)
    {
        if (!$report->canBeEdited()) {
            return redirect()
                ->route('admin.sda.reports.show', $report)
                ->with('error', 'This report cannot be edited.');
        }

        $committees = SdaCommittee::active()->get();
        return view('admin.sda.reports.edit', compact('report', 'committees'));
    }

    /**
     * Update the specified report.
     */
    public function update(Request $request, SdaReport $report)
    {
        if (!$report->canBeEdited()) {
            return redirect()
                ->route('admin.sda.reports.show', $report)
                ->with('error', 'This report cannot be edited.');
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
            ->route('admin.sda.reports.show', $report)
            ->with('success', 'Report updated successfully.');
    }

    /**
     * Submit the report for review.
     */
    public function submit(SdaReport $report)
    {
        if (!$report->canBeEdited()) {
            return redirect()
                ->route('admin.sda.reports.show', $report)
                ->with('error', 'This report cannot be submitted.');
        }

        $report->submit();

        return redirect()
            ->route('admin.sda.reports.show', $report)
            ->with('success', 'Report submitted for review successfully.');
    }

    /**
     * Review the report.
     */
    public function review(Request $request, SdaReport $report)
    {
        if (!$report->canBeReviewed()) {
            return redirect()
                ->route('admin.sda.reports.show', $report)
                ->with('error', 'This report cannot be reviewed.');
        }

        $validated = $request->validate([
            'review_notes' => 'required|string|max:1000',
            'action' => 'required|in:approve,reject',
        ]);

        if ($validated['action'] === 'approve') {
            $report->review($validated['review_notes'], auth()->id());
            $report->approve();
        } else {
            $report->review($validated['review_notes'], auth()->id());
            $report->update(['status' => 'draft']);
        }

        return redirect()
            ->route('admin.sda.reports.show', $report)
            ->with('success', 'Report reviewed successfully.');
    }

    /**
     * Approve the report.
     */
    public function approve(SdaReport $report)
    {
        if (!in_array($report->status, ['review', 'submitted'])) {
            return redirect()
                ->route('admin.sda.reports.show', $report)
                ->with('error', 'This report cannot be approved.');
        }

        $report->approve();

        return redirect()
            ->route('admin.sda.reports.show', $report)
            ->with('success', 'Report approved successfully.');
    }

    /**
     * Publish the report.
     */
    public function publish(SdaReport $report)
    {
        if (!$report->canBePublished()) {
            return redirect()
                ->route('admin.sda.reports.show', $report)
                ->with('error', 'This report cannot be published.');
        }

        $report->publish();

        return redirect()
            ->route('admin.sda.reports.show', $report)
            ->with('success', 'Report published successfully.');
    }

    /**
     * Download the report attachment.
     */
    public function download(SdaReport $report)
    {
        if (!$report->file_path) {
            return redirect()
                ->route('admin.sda.reports.show', $report)
                ->with('error', 'No file attached to this report.');
        }

        return Storage::disk('public')->download($report->file_path);
    }

    /**
     * Remove the specified report.
     */
    public function destroy(SdaReport $report)
    {
        if (!$report->canBeEdited()) {
            return redirect()
                ->route('admin.sda.reports.show', $report)
                ->with('error', 'This report cannot be deleted.');
        }

        // Delete file if exists
        if ($report->file_path) {
            Storage::disk('public')->delete($report->file_path);
        }

        $report->delete();

        return redirect()
            ->route('admin.sda.reports.index')
            ->with('success', 'Report deleted successfully.');
    }
}
