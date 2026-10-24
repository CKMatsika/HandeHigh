<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Account, Invoice, ScholarshipApplication, Student, StudentScholarship};
use App\Services\Finance\{FinancialAccountResolver, ScholarshipService};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScholarshipController extends Controller
{
    public function __construct(private ScholarshipService $service) {}

    private function access(Student $student, bool $management = false): void
    {
        abort_unless(auth()->user()?->school_id === $student->school_id, 403);
        abort_unless(auth()->user()->hasAnyRole($management
            ? ['super-admin', 'school-admin', 'headmaster']
            : ['super-admin', 'school-admin', 'headmaster', 'deputy-headmaster', 'bursar', 'accountant']), 403);
    }

    public function index(Student $student)
    {
        $this->access($student);
        $awards = $student->scholarships()->with(['applications.invoice', 'applications.sponsorReceipts', 'events'])->latest()->get();
        $invoices = $student->invoices()->where('school_id', $student->school_id)->whereNotIn('status', ['draft', 'cancelled'])->latest()->get();
        $votes = \App\Models\FeeStructure::where('school_id', $student->school_id)->whereNotNull('vote_code')->distinct()->pluck('vote_code')
            ->merge(array_keys(\App\Services\Finance\HandeFeeVotesService::getStandardVotes()))->unique()->sort()->values();
        $accounts = Account::where('school_id', $student->school_id)->where('is_active', true)->where('is_postable', true)->orderBy('code')->get();
        $mappings = \App\Models\AccountMapping::where('school_id', $student->school_id)->where('mapping_type', 'scholarship')->with('account')->get();
        $sponsors = \App\Models\FundingSponsor::where('school_id',$student->school_id)->where('is_active',true)->orderBy('name')->get();
        return view('admin.students.scholarships', compact('student', 'awards', 'invoices', 'votes', 'accounts', 'mappings','sponsors'));
    }

    public function store(Request $request, Student $student)
    {
        $this->access($student);
        $this->service->saveAward($student, $request->all());
        return back()->with('success', 'Scholarship saved for approval. No financial transaction was posted.');
    }

    public function update(Request $request, Student $student, StudentScholarship $scholarship)
    {
        $this->access($student);
        abort_unless($scholarship->student_id === $student->id && $scholarship->school_id === $student->school_id, 404);
        $this->service->saveAward($student, $request->all(), $scholarship);
        return back()->with('success', 'Pending scholarship updated.');
    }

    public function approve(Request $request, Student $student, StudentScholarship $scholarship)
    {
        $this->access($student, true);
        abort_unless($scholarship->student_id === $student->id && $scholarship->school_id === $student->school_id, 404);
        $data = $request->validate(['approval_reference' => 'required|string|max:1000']);
        $this->service->approve($scholarship, $data['approval_reference']);
        return back()->with('success', 'Scholarship approved. Existing invoices are unchanged; apply explicitly if needed.');
    }

    public function close(Request $request, Student $student, StudentScholarship $scholarship)
    {
        $this->access($student, true);
        abort_unless($scholarship->student_id === $student->id && $scholarship->school_id === $student->school_id, 404);
        $data = $request->validate(['reason' => 'required|string|max:1000']);
        $this->service->close($scholarship, $data['reason']);
        return back()->with('success', 'Award closed for future use. Posted coverage is preserved.');
    }

    public function apply(Request $request, Student $student, StudentScholarship $scholarship)
    {
        $this->access($student);
        abort_unless($scholarship->student_id === $student->id && $scholarship->school_id === $student->school_id, 404);
        $data = $request->validate(['invoice_id' => 'required|integer', 'accounting_date' => 'required|date']);
        $invoice = $student->invoices()->where('school_id', $student->school_id)->findOrFail($data['invoice_id']);
        $application = $this->service->apply($scholarship, $invoice, $data['accounting_date']);
        return back()->with('success', $application ? 'Scholarship coverage posted (or already recorded).' : 'No unpaid covered votes remain.');
    }

    public function reverse(Request $request, Student $student, ScholarshipApplication $application)
    {
        $this->access($student, true);
        abort_unless($application->school_id === $student->school_id && $application->scholarship->student_id === $student->id, 404);
        $data = $request->validate(['accounting_date' => 'required|date', 'reason' => 'required|string|max:1000']);
        app(\App\Services\Finance\ScholarshipApprovalService::class)->request($application,'scholarship_reverse',$data['accounting_date'],$data['reason']);
        return back()->with('success', 'Reversal requested. A different management checker must approve it in Sponsor/BEAM Funding.');
    }

    public function mapping(Request $request, Student $student)
    {
        $this->access($student, true);
        $data = $request->validate(['source_key' => 'required|in:SCHOOL_REMISSION,SPONSOR_RECEIVABLE',
            'account_id' => ['required', Rule::exists('accounts', 'id')->where('school_id', $student->school_id)],
            'approval_reference' => 'required|string|max:1000']);
        $account = Account::where('school_id', $student->school_id)->findOrFail($data['account_id']);
        abort_unless(($data['source_key'] === 'SCHOOL_REMISSION' && in_array($account->type, ['expense', 'revenue'], true))
            || ($data['source_key'] === 'SPONSOR_RECEIVABLE' && $account->type === 'asset' && $account->category === 'receivable'), 422, 'Select the appropriate remission or sponsor receivable account.');
        $studentAr = app(FinancialAccountResolver::class)->getReceivableAccount($student->school_id);
        abort_if($studentAr->id === $account->id, 422, 'Sponsor receivables must be separate from student debtors.');
        app(FinancialAccountResolver::class)->setMapping($student->school_id, 'scholarship', $data['source_key'], $account, $data['approval_reference']);
        \App\Models\AuditLog::create(['school_id' => $student->school_id, 'user_id' => auth()->id(),
            'action' => 'scholarship_mapping', 'model_type' => Account::class, 'model_id' => $account->id,
            'module' => 'scholarships', 'description' => $data['approval_reference'], 'new_values' => $data]);
        return back()->with('success', 'Approved scholarship account mapping saved.');
    }

    public function receiveSponsor(Request $request, Student $student, ScholarshipApplication $application)
    {
        $this->access($student);
        abort_unless($application->school_id === $student->school_id && $application->scholarship->student_id === $student->id, 404);
        $this->service->receiveSponsor($application, $request->all());
        return back()->with('success', 'Actual sponsor receipt recorded against sponsor receivables.');
    }

    public function reverseSponsor(Request $request, Student $student, \App\Models\ScholarshipSponsorReceipt $scholarshipReceipt)
    {
        $receipt = $scholarshipReceipt;
        $this->access($student, true);
        abort_unless($receipt->school_id === $student->school_id && $receipt->application->scholarship->student_id === $student->id, 404);
        $data = $request->validate(['accounting_date' => 'required|date', 'reason' => 'required|string|max:1000']);
        app(\App\Services\Finance\ScholarshipApprovalService::class)->request($receipt,'sponsor_receipt_reverse',$data['accounting_date'],$data['reason']);
        return back()->with('success', 'Receipt reversal requested for independent checker approval in Sponsor/BEAM Funding.');
    }
}
