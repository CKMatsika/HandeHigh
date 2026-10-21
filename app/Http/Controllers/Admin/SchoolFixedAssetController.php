<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\InventoryWarehouse;
use App\Models\School;
use App\Models\SchoolFixedAsset;
use App\Models\SchoolFixedAssetDepreciation;
use App\Models\SchoolFixedAssetMaintenance;
use App\Services\SchoolFixedAssetService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolFixedAssetController extends Controller
{
    public function index(Request $request)
    {
        $school = $this->school();

        $query = SchoolFixedAsset::where('school_id', $school->id)
            ->with(['item.category', 'warehouse', 'custodian', 'assetAccount']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('capitalized')) {
            $query->where('is_capitalized', $request->capitalized === 'yes');
        }

        if ($request->filled('search')) {
            $term = '%'.$request->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('asset_number', 'like', $term)
                    ->orWhere('serial_number', 'like', $term)
                    ->orWhere('location', 'like', $term)
                    ->orWhereHas('item', fn ($iq) => $iq->where('name', 'like', $term));
            });
        }

        $assets = $query->latest('acquired_date')->paginate(25)->withQueryString();

        $stats = [
            'total_assets' => SchoolFixedAsset::where('school_id', $school->id)->count(),
            'capitalized_count' => SchoolFixedAsset::where('school_id', $school->id)->where('is_capitalized', true)->whereNotIn('status', ['disposed', 'written_off'])->count(),
            'total_capitalized_cost' => SchoolFixedAsset::where('school_id', $school->id)->where('is_capitalized', true)->whereNotIn('status', ['disposed', 'written_off'])->sum('capitalized_cost'),
            'total_accumulated_depreciation' => SchoolFixedAsset::where('school_id', $school->id)->where('is_capitalized', true)->whereNotIn('status', ['disposed', 'written_off'])->sum('accumulated_depreciation'),
            'disposed_count' => SchoolFixedAsset::where('school_id', $school->id)->whereIn('status', ['disposed', 'written_off'])->count(),
        ];

        return view('admin.inventory.fixed-assets.index', compact('assets', 'stats'));
    }

    public function show(SchoolFixedAsset $fixedAsset)
    {
        $this->assertSchoolRecord($fixedAsset);
        $fixedAsset->load([
            'item.category',
            'warehouse',
            'custodian',
            'assetAccount',
            'accumulatedDepreciationAccount',
            'depreciationExpenseAccount',
            'disposalJournalBatch',
            'disposedByUser',
            'goodsReceivedItem.voucher',
            'supplierInvoiceItem.supplierInvoice.vendor',
            'assignments.fromUser',
            'assignments.toUser',
            'assignments.assignedBy',
            'depreciations.recorder',
            'depreciations.journalBatch',
            'maintenances.technician',
            'maintenances.recorder',
            'maintenances.expenseAccount',
            'maintenances.journalBatch',
        ]);

        $school = $this->school();
        $users = $school->users()->orderBy('name')->get();
        $warehouses = InventoryWarehouse::where('school_id', $school->id)->where('is_active', true)->get();
        
        $assetAccounts = Account::where('school_id', $school->id)
            ->where('type', 'asset')
            ->where('is_postable', true)
            ->where('is_active', true)
            ->get();

        $expenseAccounts = Account::where('school_id', $school->id)
            ->where('type', 'expense')
            ->where('is_postable', true)
            ->where('is_active', true)
            ->get();

        $bankAndCashAccounts = Account::where('school_id', $school->id)
            ->where('type', 'asset')
            ->whereIn('category', ['current_asset', 'bank', 'cash'])
            ->where('is_postable', true)
            ->where('is_active', true)
            ->get();

        return view('admin.inventory.fixed-assets.show', compact(
            'fixedAsset',
            'users',
            'warehouses',
            'assetAccounts',
            'expenseAccounts',
            'bankAndCashAccounts'
        ));
    }

    public function updateCustody(
        Request $request,
        SchoolFixedAsset $fixedAsset,
        SchoolFixedAssetService $assetService
    ) {
        $this->assertSchoolRecord($fixedAsset);
        $school = $this->school();
        $validated = $request->validate([
            'custodian_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('school_id', $school->id),
            ],
            'serial_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('school_fixed_assets', 'serial_number')
                    ->where('school_id', $school->id)
                    ->ignore($fixedAsset->id),
            ],
            'location' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $custodian = isset($validated['custodian_user_id'])
            ? $school->users()->findOrFail($validated['custodian_user_id'])
            : null;

        $assetService->updateCustody(
            $fixedAsset,
            $custodian,
            $validated['serial_number'] ?? '',
            $validated['location'],
            $validated['notes'] ?? null,
            $request->user()
        );

        return back()->with('success', 'Asset details and custody updated.');
    }

    public function updateDepreciation(
        Request $request,
        SchoolFixedAsset $fixedAsset,
        SchoolFixedAssetService $assetService
    ) {
        $this->assertSchoolRecord($fixedAsset);
        $school = $this->school();

        $validated = $request->validate([
            'depreciation_method' => ['required', 'string', Rule::in(['straight_line', 'reducing_balance'])],
            'useful_life_years' => ['required', 'numeric', 'min:0.5', 'max:100'],
            'salvage_value' => ['required', 'numeric', 'min:0'],
            'depreciation_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'accumulated_depreciation_account_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where('school_id', $school->id),
            ],
            'depreciation_expense_account_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where('school_id', $school->id),
            ],
        ]);

        $assetService->updateDepreciationParameters($fixedAsset, $validated, $request->user());

        return back()->with('success', 'Depreciation parameters updated.');
    }

    public function transfer(
        Request $request,
        SchoolFixedAsset $fixedAsset,
        SchoolFixedAssetService $assetService
    ) {
        $this->assertSchoolRecord($fixedAsset);
        $school = $this->school();

        $validated = $request->validate([
            'to_location' => ['required', 'string', 'max:255'],
            'to_custodian_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('school_id', $school->id),
            ],
            'to_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('school_id', $school->id),
            ],
            'to_warehouse_id' => [
                'nullable',
                Rule::exists('inventory_warehouses', 'id')->where('school_id', $school->id),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (empty($validated['to_custodian_user_id']) && ! empty($validated['to_user_id'])) {
            $validated['to_custodian_user_id'] = $validated['to_user_id'];
        }

        $assetService->transferAsset($fixedAsset, $validated, $request->user());

        return back()->with('success', 'Asset successfully transferred.');
    }

    public function dispose(
        Request $request,
        SchoolFixedAsset $fixedAsset,
        SchoolFixedAssetService $assetService
    ) {
        $this->assertSchoolRecord($fixedAsset);
        $school = $this->school();

        $validated = $request->validate([
            'disposal_date' => ['required', 'date'],
            'disposal_type' => ['required', 'string', Rule::in(['sale', 'scrap', 'scrapped', 'damaged', 'write_off', 'written_off', 'lost', 'donated'])],
            'disposal_proceeds' => ['nullable', 'numeric', 'min:0'],
            'proceeds_account_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where('school_id', $school->id),
            ],
            'bank_account_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where('school_id', $school->id),
            ],
            'disposal_notes' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (empty($validated['proceeds_account_id']) && ! empty($validated['bank_account_id'])) {
            $validated['proceeds_account_id'] = $validated['bank_account_id'];
        }
        if (empty($validated['notes']) && ! empty($validated['disposal_notes'])) {
            $validated['notes'] = $validated['disposal_notes'];
        }

        $assetService->disposeAsset($fixedAsset, $validated, $request->user());

        return back()->with('success', 'Asset disposal recorded successfully.');
    }

    public function depreciationIndex(Request $request)
    {
        $school = $this->school();

        $depreciations = SchoolFixedAssetDepreciation::where('school_id', $school->id)
            ->with(['asset.item', 'recorder', 'journalBatch'])
            ->latest('depreciation_date')
            ->paginate(25);

        $eligibleAssets = SchoolFixedAsset::where('school_id', $school->id)
            ->where('is_capitalized', true)
            ->whereNotIn('status', ['disposed', 'written_off'])
            ->with(['item', 'assetAccount'])
            ->get();

        $totalCapitalizedCost = $eligibleAssets->sum('capitalized_cost');
        $totalAccumulatedDepr = $eligibleAssets->sum('accumulated_depreciation');
        $totalNetBookValue = max(0, $totalCapitalizedCost - $totalAccumulatedDepr);

        return view('admin.inventory.fixed-assets.depreciation', compact(
            'depreciations',
            'eligibleAssets',
            'totalCapitalizedCost',
            'totalAccumulatedDepr',
            'totalNetBookValue'
        ));
    }

    public function runDepreciation(
        Request $request,
        SchoolFixedAssetService $assetService
    ) {
        $school = $this->school();

        $validated = $request->validate([
            'period_name' => ['required', 'string', 'max:100'],
            'depreciation_date' => ['required', 'date'],
            'asset_ids' => ['nullable', 'array'],
            'asset_ids.*' => [Rule::exists('school_fixed_assets', 'id')->where('school_id', $school->id)],
        ]);

        $result = $assetService->runDepreciationBatch(
            $school,
            $validated['period_name'],
            $validated['depreciation_date'],
            $request->user(),
            $validated['asset_ids'] ?? null
        );

        $formattedAmount = number_format($result['total_amount'], 2);

        return redirect()->route('admin.inventory.fixed-assets.depreciation')
            ->with('success', "Depreciation batch completed: {$result['assets_count']} assets processed, \${$formattedAmount} depreciated.");
    }

    public function maintenanceIndex(Request $request)
    {
        $school = $this->school();

        $query = SchoolFixedAssetMaintenance::where('school_id', $school->id)
            ->with(['asset.item', 'technician', 'recorder', 'expenseAccount']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('maintenance_type')) {
            $query->where('maintenance_type', $request->maintenance_type);
        }

        $maintenances = $query->latest('scheduled_date')->paginate(25)->withQueryString();

        $dueSoonCount = SchoolFixedAssetMaintenance::where('school_id', $school->id)
            ->where('status', 'scheduled')
            ->whereBetween('scheduled_date', [now()->subDays(7), now()->addDays(14)])
            ->count();

        $overdueCount = SchoolFixedAssetMaintenance::where('school_id', $school->id)
            ->where('status', 'scheduled')
            ->where('scheduled_date', '<', now()->toDateString())
            ->count();

        return view('admin.inventory.fixed-assets.maintenance', compact(
            'maintenances',
            'dueSoonCount',
            'overdueCount'
        ));
    }

    public function maintenanceStore(
        Request $request,
        SchoolFixedAsset $fixedAsset,
        SchoolFixedAssetService $assetService
    ) {
        $this->assertSchoolRecord($fixedAsset);
        $school = $this->school();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'maintenance_type' => ['required', 'string', Rule::in(['preventive', 'repair', 'corrective', 'inspection', 'calibration'])],
            'priority' => ['nullable', 'string', Rule::in(['routine', 'urgent', 'critical'])],
            'service_provider' => ['nullable', 'string', 'max:255'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'scheduled_date' => ['required', 'date'],
            'next_service_due' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'performed_by' => [
                'nullable',
                Rule::exists('users', 'id')->where('school_id', $school->id),
            ],
            'technician_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('school_id', $school->id),
            ],
            'expense_account_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where('school_id', $school->id),
            ],
        ]);

        $validated['priority'] = $validated['priority'] ?? 'routine';
        $validated['cost'] = $validated['cost'] ?? $validated['estimated_cost'] ?? 0.00;
        $validated['performed_by'] = $validated['performed_by'] ?? $validated['technician_user_id'] ?? null;

        $assetService->scheduleMaintenance($fixedAsset, $validated, $request->user());

        return back()->with('success', 'Maintenance scheduled successfully.');
    }

    public function maintenanceComplete(
        Request $request,
        SchoolFixedAssetMaintenance $maintenance,
        SchoolFixedAssetService $assetService
    ) {
        abort_unless((int) $maintenance->school_id === (int) $this->school()->id, 404);
        $school = $this->school();

        $validated = $request->validate([
            'completed_date' => ['required', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'actual_cost' => ['nullable', 'numeric', 'min:0'],
            'next_service_due' => ['nullable', 'date'],
            'service_provider' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'post_accounting' => ['nullable', 'boolean'],
            'post_to_gl' => ['nullable', 'boolean'],
            'expense_account_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where('school_id', $school->id),
            ],
            'credit_account_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where('school_id', $school->id),
            ],
            'payment_account_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where('school_id', $school->id),
            ],
        ]);

        $validated['cost'] = $validated['cost'] ?? $validated['actual_cost'] ?? 0.00;
        $validated['post_accounting'] = ! empty($validated['post_accounting']) || ! empty($validated['post_to_gl']);
        $validated['credit_account_id'] = $validated['credit_account_id'] ?? $validated['payment_account_id'] ?? null;
        $validated['description'] = $validated['description'] ?? $validated['notes'] ?? null;

        $assetService->completeMaintenance($maintenance, $validated, $request->user());

        return back()->with('success', 'Maintenance record completed.');
    }

    private function school(): School
    {
        $user = auth()->user();
        $school = $user?->school ?? ($user?->hasRole('super-admin') ? School::first() : null);
        abort_unless($school, 403);

        return $school;
    }

    private function assertSchoolRecord(SchoolFixedAsset $fixedAsset): void
    {
        abort_unless((int) $fixedAsset->school_id === (int) $this->school()->id, 404);
    }
}
