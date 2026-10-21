<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\InventoryBalance;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventorySetting;
use App\Models\InventoryStockRequest;
use App\Models\InventoryStockTake;
use App\Models\InventoryTransaction;
use App\Models\InventoryWarehouse;
use App\Models\School;
use App\Models\SchoolFixedAsset;
use App\Models\SchoolFixedAssetMaintenance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    public function dashboard()
    {
        $school = $this->school();
        $schoolId = $school->id;

        $lowStockBalances = InventoryBalance::where('school_id', $schoolId)
            ->where('reorder_level', '>', 0)
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
            ->with(['item.category', 'warehouse'])
            ->orderBy('quantity_on_hand')
            ->limit(10)
            ->get();

        $recentTransactions = InventoryTransaction::where('school_id', $schoolId)
            ->with(['item', 'warehouse', 'recorder'])
            ->latest('occurred_at')
            ->limit(10)
            ->get();

        $settings = InventorySetting::where('school_id', $schoolId)->first();
        $capitalizationThreshold = $settings?->asset_capitalization_threshold;
        $inventoryValue = InventoryBalance::where('school_id', $schoolId)
            ->where(function ($query) use ($capitalizationThreshold) {
                $query->whereHas('item', fn ($itemQuery) => $itemQuery->where('item_type', 'consumable'));
                if ($capitalizationThreshold !== null) {
                    $query->orWhere(function ($assetQuery) use ($capitalizationThreshold) {
                        $assetQuery->where('average_unit_cost', '<', $capitalizationThreshold)
                            ->whereHas('item', fn ($itemQuery) => $itemQuery->where('item_type', 'asset'));
                    });
                }
            })
            ->selectRaw('COALESCE(SUM(quantity_on_hand * average_unit_cost), 0) as inventory_value')
            ->value('inventory_value');

        $stats = [
            'items' => InventoryItem::where('school_id', $schoolId)->where('is_active', true)->count(),
            'categories' => InventoryCategory::where('school_id', $schoolId)->where('is_active', true)->count(),
            'warehouses' => InventoryWarehouse::where('school_id', $schoolId)->where('is_active', true)->count(),
            'low_stock' => InventoryBalance::where('school_id', $schoolId)
                ->where('reorder_level', '>', 0)
                ->whereColumn('quantity_on_hand', '<=', 'reorder_level')
                ->count(),
            'valuation_method' => $settings?->valuation_method ?? 'weighted_average',
            'inventory_value' => $inventoryValue,
            'fixed_asset_value' => SchoolFixedAsset::where('school_id', $schoolId)
                ->where('is_capitalized', true)
                ->where('status', '!=', 'disposed')
                ->sum('capitalized_cost'),
            'capitalization_threshold' => $capitalizationThreshold,
            'pending_requests' => InventoryStockRequest::where('school_id', $schoolId)->whereIn('status', ['submitted', 'pending'])->count(),
            'pending_stock_takes' => InventoryStockTake::where('school_id', $schoolId)->whereIn('status', ['draft', 'in_progress', 'submitted'])->count(),
            'total_fixed_assets' => SchoolFixedAsset::where('school_id', $schoolId)->where('status', '!=', 'disposed')->count(),
            'pending_maintenance' => SchoolFixedAssetMaintenance::where('school_id', $schoolId)->whereIn('status', ['scheduled', 'in_progress'])->count(),
        ];

        return view('admin.inventory.dashboard', compact('school', 'stats', 'lowStockBalances', 'recentTransactions'));
    }

    public function categories()
    {
        $school = $this->school();
        $records = InventoryCategory::where('school_id', $school->id)
            ->withCount('items')
            ->orderBy('name')
            ->paginate(25);

        return view('admin.inventory.catalog-index', [
            'kind' => 'categories',
            'records' => $records,
        ]);
    }

    public function createCategory()
    {
        return $this->form('categories');
    }

    public function storeCategory(Request $request)
    {
        $school = $this->school();
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('inventory_categories')->where('school_id', $school->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);
        if (auth()->user()->can('accounts.edit')) {
            $validated['issue_expense_account_id'] = $this->validateIssueExpenseAccount($request, $school);
            $validated['asset_account_id'] = $this->validateAssetAccount($request, $school);
        }

        InventoryCategory::create([...$validated, 'school_id' => $school->id]);

        return redirect()->route('admin.inventory.categories.index')->with('success', 'Inventory category created.');
    }

    public function editCategory(InventoryCategory $category)
    {
        $this->assertSchoolRecord($category);

        return $this->form('categories', $category);
    }

    public function updateCategory(Request $request, InventoryCategory $category)
    {
        $this->assertSchoolRecord($category);
        $school = $this->school();
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('inventory_categories')->where('school_id', $school->id)->ignore($category->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);
        if (auth()->user()->can('accounts.edit')) {
            $validated['issue_expense_account_id'] = $this->validateIssueExpenseAccount($request, $school);
            $validated['asset_account_id'] = $this->validateAssetAccount($request, $school);
        }

        $category->update($validated);

        return redirect()->route('admin.inventory.categories.index')->with('success', 'Inventory category updated.');
    }

    public function items()
    {
        $school = $this->school();
        $records = InventoryItem::where('school_id', $school->id)
            ->with('category')
            ->orderBy('name')
            ->paginate(25);

        return view('admin.inventory.catalog-index', [
            'kind' => 'items',
            'records' => $records,
        ]);
    }

    public function createItem()
    {
        return $this->form('items');
    }

    public function storeItem(Request $request)
    {
        $school = $this->school();
        $validated = $this->validateItem($request, $school);

        InventoryItem::create([...$validated, 'school_id' => $school->id]);

        return redirect()->route('admin.inventory.items.index')->with('success', 'Inventory item created.');
    }

    public function editItem(InventoryItem $item)
    {
        $this->assertSchoolRecord($item);

        return $this->form('items', $item);
    }

    public function updateItem(Request $request, InventoryItem $item)
    {
        $this->assertSchoolRecord($item);
        $validated = $this->validateItem($request, $this->school(), $item);
        $item->update($validated);

        return redirect()->route('admin.inventory.items.index')->with('success', 'Inventory item updated.');
    }

    public function warehouses()
    {
        $school = $this->school();
        $records = InventoryWarehouse::where('school_id', $school->id)
            ->with('manager')
            ->withCount('balances')
            ->orderBy('name')
            ->paginate(25);

        return view('admin.inventory.catalog-index', [
            'kind' => 'warehouses',
            'records' => $records,
        ]);
    }

    public function createWarehouse()
    {
        return $this->form('warehouses');
    }

    public function storeWarehouse(Request $request)
    {
        $school = $this->school();
        $validated = $this->validateWarehouse($request, $school);

        InventoryWarehouse::create([...$validated, 'school_id' => $school->id]);

        return redirect()->route('admin.inventory.warehouses.index')->with('success', 'Warehouse created.');
    }

    public function editWarehouse(InventoryWarehouse $warehouse)
    {
        $this->assertSchoolRecord($warehouse);

        return $this->form('warehouses', $warehouse);
    }

    public function updateWarehouse(Request $request, InventoryWarehouse $warehouse)
    {
        $this->assertSchoolRecord($warehouse);
        $validated = $this->validateWarehouse($request, $this->school(), $warehouse);
        $warehouse->update($validated);

        return redirect()->route('admin.inventory.warehouses.index')->with('success', 'Warehouse updated.');
    }

    private function form(string $kind, ?Model $record = null)
    {
        $school = $this->school();
        $categories = $kind === 'items'
            ? InventoryCategory::where('school_id', $school->id)->where('is_active', true)->orderBy('name')->get()
            : collect();
        $users = $kind === 'warehouses'
            ? $school->users()->orderBy('name')->get()
            : collect();
        $expenseAccounts = $kind === 'categories' && auth()->user()->can('accounts.edit')
            ? Account::where('school_id', $school->id)->where('type', 'expense')->where('is_active', true)->where('is_postable', true)->orderBy('code')->get()
            : collect();
        $assetAccounts = $kind === 'categories' && auth()->user()->can('accounts.edit')
            ? Account::where('school_id', $school->id)->where('type', 'asset')->where('category', 'fixed_asset')->where('is_active', true)->where('is_postable', true)->orderBy('code')->get()
            : collect();

        return view('admin.inventory.catalog-form', compact('kind', 'record', 'categories', 'users', 'expenseAccounts', 'assetAccounts'));
    }

    private function validateIssueExpenseAccount(Request $request, School $school): ?int
    {
        $validated = $request->validate([
            'issue_expense_account_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where('school_id', $school->id),
            ],
        ]);
        $accountId = $validated['issue_expense_account_id'] ?? null;
        if ($accountId === null) {
            return null;
        }

        $account = Account::where('school_id', $school->id)->findOrFail($accountId);
        if ($account->type !== 'expense' || ! $account->is_active || ! $account->is_postable) {
            throw ValidationException::withMessages([
                'issue_expense_account_id' => 'Choose an active, postable expense account.',
            ]);
        }

        return (int) $account->id;
    }

    private function validateAssetAccount(Request $request, School $school): ?int
    {
        $validated = $request->validate([
            'asset_account_id' => [
                'nullable',
                Rule::exists('accounts', 'id')
                    ->where('school_id', $school->id)
                    ->where('type', 'asset')
                    ->where('category', 'fixed_asset')
                    ->where('is_active', true)
                    ->where('is_postable', true),
            ],
        ]);

        return isset($validated['asset_account_id']) ? (int) $validated['asset_account_id'] : null;
    }

    private function validateItem(Request $request, School $school, ?InventoryItem $item = null): array
    {
        $uniqueCode = Rule::unique('inventory_items')->where('school_id', $school->id);
        if ($item) {
            $uniqueCode->ignore($item->id);
        }

        return $request->validate([
            'inventory_category_id' => ['required', Rule::exists('inventory_categories', 'id')->where('school_id', $school->id)],
            'code' => ['required', 'string', 'max:50', $uniqueCode],
            'name' => ['required', 'string', 'max:255'],
            'item_type' => ['required', Rule::in(['consumable', 'asset', 'non_stock'])],
            'unit_of_measure' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function validateWarehouse(Request $request, School $school, ?InventoryWarehouse $warehouse = null): array
    {
        $uniqueCode = Rule::unique('inventory_warehouses')->where('school_id', $school->id);
        if ($warehouse) {
            $uniqueCode->ignore($warehouse->id);
        }

        return $request->validate([
            'code' => ['required', 'string', 'max:50', $uniqueCode],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'manager_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('school_id', $school->id),
            ],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function school(): School
    {
        $user = auth()->user();
        $school = $user?->school ?? ($user?->hasRole('super-admin') ? School::first() : null);

        abort_unless($school, 403);

        return $school;
    }

    private function assertSchoolRecord(Model $record): void
    {
        abort_unless($record->school_id === $this->school()->id, 404);
    }
}
