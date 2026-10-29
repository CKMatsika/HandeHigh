<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\StudentReconciliationBatch;
use App\Models\StudentReconciliationFinding;
use App\Services\Academic\StudentReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class StudentReconciliationController extends Controller
{
    public function index()
    {
        $schoolId = (int) Auth::user()->school_id;
        $batches = StudentReconciliationBatch::where('school_id', $schoolId)->with('creator')->latest()->paginate(20);
        return view('admin.student-reconciliation.index', compact('batches'));
    }

    public function create()
    {
        return view('admin.student-reconciliation.create');
    }

    public function store(Request $request, StudentReconciliationService $service)
    {
        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'regex:/^20\d{2}$/D'],
            'term' => ['required', 'in:Term 1,Term 2,Term 3'],
            'files' => ['required', 'array', 'min:1', 'max:8'],
            'files.*' => ['required', 'file', 'max:5120', 'mimes:xlsx,xls,csv,txt'],
            'source_types' => ['required', 'array', 'min:1', 'max:8'],
            'source_types.*' => ['required', 'in:student_register,class_register,invoice_register'],
            'complete_register' => ['nullable', 'boolean'],
            'complete_invoice_register' => ['nullable', 'boolean'],
        ]);

        if (count($validated['files']) !== count($validated['source_types'])) {
            return back()->withErrors(['files' => 'Choose one source type for every file.'])->withInput();
        }
        if ($request->boolean('complete_register') && ! array_intersect($validated['source_types'], ['student_register', 'class_register'])) {
            return back()->withErrors(['complete_register' => 'A complete student-register comparison requires at least one student or class register.'])->withInput();
        }
        if ($request->boolean('complete_invoice_register') && ! in_array('invoice_register', $validated['source_types'], true)) {
            return back()->withErrors(['complete_invoice_register' => 'A complete invoice comparison requires at least one invoice register.'])->withInput();
        }

        try {
            $batch = $service->createBatch((int) Auth::user()->school_id, (int) Auth::id(), $validated, $validated['files']);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['files' => $e->getMessage()])->withInput();
        } catch (Throwable $e) {
            report($e);
            return back()->withErrors(['files' => 'The reconciliation could not be completed. No ERP student, enrolment, invoice, or accounting records were changed.'])->withInput();
        }

        return redirect()->route('admin.student-reconciliation.show', $batch)->with('success', 'Read-only reconciliation completed. Review the findings before using them to update ERP records through their normal workflows.');
    }

    public function show(Request $request, StudentReconciliationBatch $batch)
    {
        $this->assertTenant($batch);
        $query = $this->filteredFindings($request, $batch);
        $findings = $query->paginate(100)->withQueryString();
        $summary = $batch->summary ?? [];
        $sourceTypes = $batch->sources()->distinct()->pluck('source_type');
        $codes = $batch->findings()->select('code')->distinct()->orderBy('code')->pluck('code');
        $placementRows = Enrollment::where('school_id', $batch->school_id)->where('academic_year', $batch->academic_year)
            ->whereIn('student_id', $batch->rows()->whereNotNull('matched_student_id')->select('matched_student_id'))
            ->with('class')->get()->filter(fn ($enrollment) => \App\Services\Academic\StudentReconciliationWorkbookParser::normalizeTerm($enrollment->term) === \App\Services\Academic\StudentReconciliationWorkbookParser::normalizeTerm($batch->term));
        $placementCounts = $placementRows->groupBy(fn ($enrollment) => trim(($enrollment->grade ?? $enrollment->class?->grade ?? 'Unspecified'). ' / '.($enrollment->class?->name ?? $enrollment->class_name ?? 'No class')))
            ->map->count()->sortDesc()->take(20);
        return view('admin.student-reconciliation.show', compact('batch', 'findings', 'summary', 'sourceTypes', 'codes', 'placementCounts'));
    }

    public function print(Request $request, StudentReconciliationBatch $batch)
    {
        $this->assertTenant($batch);
        $query = $this->filteredFindings($request, $batch);
        $total = $query->count();
        $findings = $query->limit(10000)->get();
        $truncated = $total > 10000;
        AuditLog::create(['school_id' => $batch->school_id, 'user_id' => Auth::id(), 'action' => 'student_reconciliation_report_printed',
            'module' => 'academic', 'model_type' => StudentReconciliationBatch::class, 'model_id' => $batch->id,
            'new_values' => ['matching_findings' => $total, 'printed_findings' => $findings->count()], 'description' => 'Private filtered reconciliation report prepared for printing.']);
        return response()->view('admin.student-reconciliation.print', compact('batch', 'findings', 'total', 'truncated'))
            ->header('Cache-Control', 'private, no-store, no-cache, max-age=0')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function review(Request $request, StudentReconciliationBatch $batch, StudentReconciliationFinding $finding)
    {
        $this->assertTenant($batch);
        abort_unless($finding->batch_id === $batch->id, 404);
        $data = $request->validate([
            'status' => ['required', 'in:reviewed,dismissed'],
            'review_note' => ['required', 'string', 'min:4', 'max:2000'],
        ]);
        $finding->update(['status' => $data['status'], 'reviewed_by' => Auth::id(), 'reviewed_at' => now(), 'review_note' => $data['review_note']]);
        $open = $batch->findings()->where('status', 'open')->count();
        $batch->update(['open_findings_count' => $open]);
        AuditLog::create([
            'school_id' => $batch->school_id, 'user_id' => Auth::id(), 'action' => 'student_reconciliation_finding_reviewed',
            'module' => 'academic', 'model_type' => StudentReconciliationFinding::class, 'model_id' => $finding->id,
            'new_values' => ['status' => $data['status'], 'code' => $finding->code, 'batch_id' => $batch->id],
            'description' => 'Reconciliation finding reviewed. This action changed only reconciliation review metadata, not ERP student or finance data.',
        ]);
        return back()->with('success', 'Finding review status saved. ERP student and financial records were not changed.');
    }

    public function export(StudentReconciliationBatch $batch): StreamedResponse
    {
        $this->assertTenant($batch);
        AuditLog::create([
            'school_id' => $batch->school_id, 'user_id' => Auth::id(), 'action' => 'student_reconciliation_report_exported',
            'module' => 'academic', 'model_type' => StudentReconciliationBatch::class, 'model_id' => $batch->id,
            'description' => 'Private read-only reconciliation CSV report downloaded.',
        ]);
        $filename = 'student-reconciliation-'.$batch->academic_year.'-'.Str::slug($batch->term).'-batch-'.$batch->id.'.csv';

        return response()->streamDownload(function () use ($batch) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Finding ID', 'Severity', 'Status', 'Code', 'Student ERP ID', 'Student Name', 'Admission Number', 'Source File', 'Source Type', 'Source Row', 'Field', 'Source Value', 'ERP Value', 'Invoice Number', 'Message', 'Review Note']);
            $batch->findings()->with(['student', 'invoice', 'sourceRow.source'])->orderBy('id')->chunk(250, function ($findings) use ($out) {
                foreach ($findings as $finding) {
                    $values = [$finding->id, $finding->severity, $finding->status, $finding->code, $finding->student_id,
                        $finding->student?->full_name, $finding->student?->admission_number,
                        $finding->sourceRow?->source?->original_filename, $finding->sourceRow?->source?->source_type,
                        $finding->sourceRow?->source_row_number, $finding->field, $finding->source_value,
                        $finding->erp_value, $finding->invoice?->number, $finding->message, $finding->review_note];
                    fputcsv($out, array_map([$this, 'safeCsvCell'], $values));
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'X-Robots-Tag' => 'noindex, nofollow']);
    }

    private function safeCsvCell(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));
        return preg_match('/^[=+@\-\t\r]/', $text) ? "'".$text : $text;
    }

    private function filteredFindings(Request $request, StudentReconciliationBatch $batch)
    {
        $query = $batch->findings()->with(['student', 'invoice', 'sourceRow.source', 'reviewer'])->latest();
        if ($request->filled('severity')) $query->where('severity', $request->string('severity')->toString());
        if ($request->filled('status')) $query->where('status', $request->string('status')->toString());
        if ($request->filled('code')) $query->where('code', $request->string('code')->toString());
        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', '%'.$search.'%')
                    ->orWhere('source_value', 'like', '%'.$search.'%')
                    ->orWhere('erp_value', 'like', '%'.$search.'%')
                    ->orWhereHas('student', fn ($students) => $students->where('first_name', 'like', '%'.$search.'%')->orWhere('last_name', 'like', '%'.$search.'%')->orWhere('admission_number', 'like', '%'.$search.'%'));
            });
        }
        return $query;
    }

    private function assertTenant(StudentReconciliationBatch $batch): void
    {
        abort_unless(Auth::check() && (int) Auth::user()->school_id === (int) $batch->school_id, 404);
    }
}
