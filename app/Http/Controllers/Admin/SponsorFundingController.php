<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\FundingSponsor;
use App\Models\ScholarshipApproval;
use App\Models\SponsorClaim;
use App\Models\SponsorClaimBatch;
use App\Models\SponsorSettlement;
use App\Services\Finance\BeamMigrationService;
use App\Services\Finance\FinancialAccountResolver;
use App\Services\Finance\ScholarshipApprovalService;
use App\Services\Finance\ScholarshipBeamReportService;
use App\Services\Finance\SponsorFinanceSupport;
use App\Services\Finance\SponsorSettlementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SponsorFundingController extends Controller
{
    private function school(bool $management = false): int
    {
        $id = (int) auth()->user()?->school_id;
        SponsorFinanceSupport::actor($id, $management);

        return $id;
    }

    public function index()
    {
        $schoolId = $this->school();
        $sponsors = FundingSponsor::where('school_id', $schoolId)->orderBy('name')->get();
        $batches = SponsorClaimBatch::where('school_id', $schoolId)->with('sponsor')->latest()->get();
        $settlements = SponsorSettlement::where('school_id', $schoolId)->with('allocations')->latest()->get();
        $approvals = ScholarshipApproval::where('school_id', $schoolId)->where('status', 'pending')->get();
        $accounts = Account::where('school_id', $schoolId)->where('is_active', true)->where('is_postable', true)->orderBy('code')->get();
        $pendingClaims = SponsorClaim::where('school_id', $schoolId)->whereNotIn('status', ['posted', 'reversed'])->with('student')->get();
        $targets = collect();
        foreach (SponsorClaim::where('school_id', $schoolId)->where('status', 'posted')->with('student')->get() as $claim) {
            if ($claim->outstanding > 0) {
                $targets->push(['key' => 'claim:'.$claim->id, 'label' => $claim->student->full_name.' · '.$claim->academic_year.' · '.$claim->reference.' · '.$claim->outstanding, 'sponsor_id' => $claim->funding_sponsor_id]);
            }
        }
        foreach (\App\Models\ScholarshipApplication::where('school_id', $schoolId)->where('status', 'posted')->with('scholarship.student')->get() as $application) {
            if ($application->scholarship->funding_sponsor_id && $application->sponsor_balance > 0) {
                $targets->push(['key' => 'scholarship:'.$application->id, 'label' => $application->scholarship->student->full_name.' · '.$application->scholarship->academic_year.' · '.$application->scholarship->reference.' · '.$application->sponsor_balance, 'sponsor_id' => $application->scholarship->funding_sponsor_id]);
            }
        }

        return view('admin.funding.index', compact('sponsors', 'batches', 'settlements', 'approvals', 'accounts', 'targets', 'pendingClaims'));
    }

    public function sponsor(Request $request)
    {
        $schoolId = $this->school(true);
        $data = $request->validate(['code' => ['required', 'regex:/^[A-Z0-9_-]{1,50}$/', Rule::unique('funding_sponsors')->where('school_id', $schoolId)],
            'name' => 'required|string|max:255', 'category' => 'required|in:government,company,ngo,donor,individual']);
        $sponsor = FundingSponsor::create($data + ['school_id' => $schoolId]);
        SponsorFinanceSupport::audit($sponsor, 'sponsor_created', $data);

        return back()->with('success', 'Sponsor created. Configure its receivable account before recognition.');
    }

    public function mapping(Request $request, FundingSponsor $fundingSponsor)
    {
        $schoolId = $this->school(true);
        abort_unless($fundingSponsor->school_id === $schoolId, 404);
        $data = $request->validate(['account_id' => ['required', Rule::exists('accounts', 'id')->where('school_id', $schoolId)], 'approval_reference' => 'required|string|max:1000']);
        $account = Account::where('school_id', $schoolId)->findOrFail($data['account_id']);
        $ar = app(FinancialAccountResolver::class)->getReceivableAccount($schoolId);
        if ($account->type !== 'asset' || $account->category !== 'receivable' || $account->id === $ar->id) {
            SponsorFinanceSupport::fail('Use a separate sponsor receivable asset.');
        }
        app(FinancialAccountResolver::class)->setMapping($schoolId, 'sponsor_receivable', $fundingSponsor->code, $account, $data['approval_reference']);
        SponsorFinanceSupport::audit($fundingSponsor, 'sponsor_mapping', $data);

        return back()->with('success', 'Sponsor account mapping saved; existing postings are unchanged.');
    }

    public function template()
    {
        $this->school();

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, BeamMigrationService::HEADERS);
            fclose($out);
        }, 'beam-claims-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function upload(Request $request)
    {
        $schoolId = $this->school();
        $data = $request->validate(['funding_sponsor_id' => 'required|integer', 'academic_year' => 'required|digits:4', 'reference' => 'required|string|max:100',
            'file' => 'required|file|mimes:csv,txt|max:5120']);
        $sponsor = FundingSponsor::where('school_id', $schoolId)->findOrFail($data['funding_sponsor_id']);
        $file = fopen($request->file('file')->getRealPath(), 'r');
        try {
            $header = fgetcsv($file);
            if (! $header) {
                SponsorFinanceSupport::fail('CSV has no heading row.');
            }
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            if (array_diff(array_slice(BeamMigrationService::HEADERS, 0, 11), $header) || count($header) !== count(array_unique($header))) {
                SponsorFinanceSupport::fail('Use the BEAM template headings.');
            }
            $rows = [];
            while (($values = fgetcsv($file)) !== false) {
                if ($values === [null]) {
                    continue;
                }
                if (count($values) !== count($header) || count($rows) >= 5000) {
                    SponsorFinanceSupport::fail('Invalid CSV row or row limit exceeded.');
                }
                $rows[] = array_combine($header, array_map('trim', $values));
            }
        } finally {
            fclose($file);
        }
        $batch = app(BeamMigrationService::class)->stage($sponsor, $data['academic_year'], $data['reference'], $rows);

        return redirect()->route('admin.funding.batch', $batch)->with('success', 'Uploaded to staging only. Review classification and evidence before approval.');
    }

    public function batch(SponsorClaimBatch $claimBatch)
    {
        abort_unless($claimBatch->school_id === $this->school(), 404);
        $rows = $claimBatch->migrationBatch->stagingRows()->orderBy('row_number')->paginate(50);

        return view('admin.funding.batch', compact('claimBatch', 'rows'));
    }

    public function review(Request $request, SponsorClaimBatch $claimBatch, int $row)
    {
        abort_unless($claimBatch->school_id === $this->school(), 404);
        app(BeamMigrationService::class)->review($claimBatch, $row, $request->all());

        return back()->with('success', 'Classification verified. Nothing posted.');
    }

    public function approveBatch(Request $request, SponsorClaimBatch $claimBatch)
    {
        abort_unless($claimBatch->school_id === $this->school(true), 404);
        $data = $request->validate(['approval_reference' => 'required|string|max:1000']);
        app(BeamMigrationService::class)->approve($claimBatch, $data['approval_reference']);

        return back()->with('success', 'Verified claim batch approved. Non-receivable historical items remain staged for their separate workflows.');
    }

    public function recognize(Request $request, SponsorClaim $fundingClaim)
    {
        abort_unless($fundingClaim->school_id === $this->school(), 404);
        app(\App\Services\Finance\SponsorClaimService::class)->prepareRecognition($fundingClaim, $request->all());

        return back()->with('success', 'Recognition prepared without posting; independent approval is required.');
    }

    public function approveRecognition(Request $request, SponsorClaim $fundingClaim)
    {
        abort_unless($fundingClaim->school_id === $this->school(true), 404);
        $data = $request->validate(['approval_reference' => 'required|string|max:1000']);
        app(\App\Services\Finance\SponsorClaimService::class)->approveRecognition($fundingClaim, $data['approval_reference']);

        return back()->with('success', 'Verified obligation recognised with a separate checker.');
    }

    public function prepareSettlement(Request $request)
    {
        $sponsor = FundingSponsor::where('school_id', $this->school())->findOrFail($request->input('funding_sponsor_id'));
        $input = $request->all();
        $input['allocations'] = collect($input['allocations'] ?? [])->filter(fn ($line) => isset($line['amount']) && $line['amount'] !== '')->map(function ($line) {
            if (isset($line['target'])) {
                $parts = explode(':', $line['target'], 2);
                $line['target_type'] = $parts[0];
                $line['target_id'] = $parts[1] ?? null;
                unset($line['target']);
            }

            return $line;
        })->values()->all();
        app(SponsorSettlementService::class)->prepare($sponsor, $input);

        return back()->with('success', 'Settlement prepared; a different checker must approve before cash/GL posting.');
    }

    public function approveSettlement(Request $request, SponsorSettlement $sponsorSettlement)
    {
        abort_unless($sponsorSettlement->school_id === $this->school(true), 404);
        $data = $request->validate(['approval_reference' => 'required|string|max:1000']);
        app(SponsorSettlementService::class)->approve($sponsorSettlement, $data['approval_reference']);

        return back()->with('success', 'Settlement posted once to sponsor receivables, bank and cashbook.');
    }

    public function correction(Request $request)
    {
        $schoolId = $this->school();
        $data = $request->validate(['type' => 'required|in:claim,settlement', 'id' => 'required|integer', 'accounting_date' => 'required|date', 'reason' => 'required|string|max:1000']);
        $subject = $data['type'] === 'claim' ? SponsorClaim::where('school_id', $schoolId)->findOrFail($data['id']) : SponsorSettlement::where('school_id', $schoolId)->findOrFail($data['id']);
        app(ScholarshipApprovalService::class)->request($subject, $data['type'] === 'claim' ? 'claim_reverse' : 'settlement_reverse', $data['accounting_date'], $data['reason']);

        return back()->with('success', 'Correction requested. A different management checker must approve it.');
    }

    public function approveCorrection(Request $request, ScholarshipApproval $fundingApproval)
    {
        abort_unless($fundingApproval->school_id === $this->school(true), 404);
        $data = $request->validate(['approval_reference' => 'required|string|max:1000']);
        app(ScholarshipApprovalService::class)->approve($fundingApproval, $data['approval_reference']);

        return back()->with('success', 'Correction approved and posted with an immutable trail.');
    }

    public function reports(Request $request)
    {
        $schoolId = $this->school();
        $data = $request->validate(['as_of' => 'nullable|date', 'academic_year' => 'nullable|digits:4', 'report' => 'nullable|in:beneficiaries,internal_expenditure,external_receivables,beam_claims,beam_ageing,payments,coverage,controls']);
        $report = app(ScholarshipBeamReportService::class)->build($schoolId, $data['as_of'] ?? date('Y-m-d'), $data['academic_year'] ?? null);
        $selected = $data['report'] ?? 'beam_claims';
        if ($request->boolean('export')) {
            return response()->streamDownload(function () use ($report, $selected) {
                $rows = collect($report[$selected])->map(fn ($row) => (array) $row);
                $out = fopen('php://output', 'w');
                $headers = $rows->isNotEmpty() ? array_keys($rows->first()) : [];
                if ($headers) {
                    fputcsv($out, $headers);
                }
                foreach ($rows as $row) {
                    fputcsv($out, array_map(function ($key) use ($row) {
                        $value = $row[$key] ?? null;
                        $v = is_array($value) ? json_encode($value) : (string) $value;

                        return ! is_numeric($v) && preg_match('/^[=+@-]/',$v) ? "'".$v : $v;
                    }, $headers));
                }
                fclose($out);
            }, 'funding-'.$selected.'.csv', ['Content-Type' => 'text/csv']);
        }

        return view('admin.funding.reports',compact('report','selected'));
    }
}
