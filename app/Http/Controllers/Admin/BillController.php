<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Bill;
use App\Models\Department;
use App\Models\Project;
use App\Models\Vendor;
use App\Rules\TenantExists;
use Illuminate\Http\Request;

class BillController extends Controller
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

        $bills = Bill::where('school_id', $school->id)
            ->with(['vendor', 'project', 'costCenter'])
            ->when($request->status, function($query, $status) {
                if ($status === 'pending') {
                    $query->where('status', 'pending');
                }
            })
            ->when($request->project_id, function($query, $projectId) {
                $query->where('project_id', $projectId);
            })
            ->when($request->cost_center_id, function($query, $costCenterId) {
                $query->where('cost_center_id', $costCenterId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $projects = Project::where('school_id', $school->id)->orderBy('name')->get();
        $costCenters = Department::where('school_id', $school->id)->orderBy('name')->get();

        return view('admin.bills.index', compact('bills', 'projects', 'costCenters'));
    }

    public function create()
    {
        $user = auth()->user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        app(\App\Services\AccountingService::class)->ensureChartOfAccountsExist($school);

        $vendors = Vendor::where('school_id', $school->id)->get();
        $expenseAccounts = Account::where('school_id', $school->id)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->where('is_postable', true)
            ->orderBy('code')
            ->get();
        $projects = Project::where('school_id', $school->id)->orderBy('name')->get();
        $costCenters = Department::where('school_id', $school->id)->orderBy('name')->get();

        return view('admin.bills.create', compact('vendors', 'expenseAccounts', 'projects', 'costCenters'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $school = $user?->school;

        if (!$school) {
            abort(403);
        }

        $validated = $request->validate([
            'vendor_id' => ['required', TenantExists::make('vendors')],
            'expense_account_id' => ['nullable', 'integer'],
            'project_id' => ['nullable', 'integer'],
            'cost_center_id' => ['nullable', 'integer'],
            'bill_number' => ['required', 'string', 'max:255'],
            'vendor_bill_number' => ['nullable', 'string', 'max:255'],
            'bill_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after:bill_date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $bill = Bill::create([
            'school_id' => $school->id,
            'vendor_id' => $validated['vendor_id'],
            'expense_account_id' => !empty($validated['expense_account_id']) ? (int) $validated['expense_account_id'] : null,
            'project_id' => !empty($validated['project_id']) ? (int) $validated['project_id'] : null,
            'cost_center_id' => !empty($validated['cost_center_id']) ? (int) $validated['cost_center_id'] : null,
            'bill_number' => $validated['bill_number'],
            'vendor_bill_number' => $validated['vendor_bill_number'] ?? null,
            'bill_date' => $validated['bill_date'],
            'due_date' => $validated['due_date'],
            'total_amount' => $validated['total_amount'],
            'paid_amount' => 0,
            'balance' => $validated['total_amount'],
            'status' => 'pending',
            'description' => $validated['description'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $user->id,
        ]);

        try {
            app(\App\Services\AccountingService::class)->postBill($bill);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to post bill to accounting: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.bills.index')
            ->with('status', 'Bill created successfully');
    }

    public function show(Bill $bill)
    {
        $this->authorizeSchoolAccess($bill);
        
        $bill->load(['vendor', 'items', 'payments', 'expenseAccount', 'project', 'costCenter']);
        return view('admin.bills.show', compact('bill'));
    }

    public function edit(Bill $bill)
    {
        $this->authorizeSchoolAccess($bill);
        abort_if($bill->procurementSupplierInvoice()->exists(), 409, 'Procurement supplier invoices must be corrected through their review workflow.');

        $user = auth()->user();
        $school = $user?->school;

        $vendors = Vendor::where('school_id', $school->id)->get();
        $expenseAccounts = Account::where('school_id', $school->id)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->where('is_postable', true)
            ->orderBy('code')
            ->get();
        $projects = Project::where('school_id', $school->id)->orderBy('name')->get();
        $costCenters = Department::where('school_id', $school->id)->orderBy('name')->get();

        return view('admin.bills.edit', compact('bill', 'vendors', 'expenseAccounts', 'projects', 'costCenters'));
    }

    public function update(Request $request, Bill $bill)
    {
        $this->authorizeSchoolAccess($bill);
        abort_if($bill->procurementSupplierInvoice()->exists(), 409, 'Procurement supplier invoices must be corrected through their review workflow.');

        $validated = $request->validate([
            'vendor_id' => ['required', TenantExists::make('vendors')],
            'expense_account_id' => ['nullable', 'integer'],
            'project_id' => ['nullable', 'integer'],
            'cost_center_id' => ['nullable', 'integer'],
            'bill_number' => ['required', 'string', 'max:255'],
            'vendor_bill_number' => ['nullable', 'string', 'max:255'],
            'bill_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after:bill_date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['expense_account_id'] = !empty($validated['expense_account_id']) ? (int) $validated['expense_account_id'] : null;
        $validated['project_id'] = !empty($validated['project_id']) ? (int) $validated['project_id'] : null;
        $validated['cost_center_id'] = !empty($validated['cost_center_id']) ? (int) $validated['cost_center_id'] : null;

        $bill->update($validated);

        try {
            app(\App\Services\AccountingService::class)->postBill($bill);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to update bill in accounting: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.bills.index')
            ->with('status', 'Bill updated successfully');
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
