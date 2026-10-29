<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\KioskProduct;
use App\Models\KioskSale;
use App\Models\KioskTillSession;
use App\Rules\TenantExists;
use App\Services\Kiosk\KioskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KioskController extends Controller
{
    protected KioskService $kioskService;

    public function __construct(KioskService $kioskService)
    {
        $this->kioskService = $kioskService;
    }

    public function index(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $query = KioskSale::where('school_id', $school->id)
            ->with(['items.product', 'cashier'])
            ->orderByDesc('sale_date')
            ->orderByDesc('created_at');

        if ($request->filled('start_date')) {
            $query->whereDate('sale_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('sale_date', '<=', $request->end_date);
        }

        $sales = $query->paginate(20);

        $todayTotal = (float) KioskSale::where('school_id', $school->id)
            ->whereDate('sale_date', now()->toDateString())
            ->sum('grand_total');

        $monthTotal = (float) KioskSale::where('school_id', $school->id)
            ->whereMonth('sale_date', now()->month)
            ->whereYear('sale_date', now()->year)
            ->sum('grand_total');
        $periodQuery = KioskSale::where('school_id', $school->id);
        if ($request->filled('start_date')) $periodQuery->whereDate('sale_date', '>=', $request->date('start_date'));
        if ($request->filled('end_date')) $periodQuery->whereDate('sale_date', '<=', $request->date('end_date'));
        $periodTotal = (float) $periodQuery->sum('grand_total');

        $bestSellers = \App\Models\KioskSaleItem::query()->selectRaw('kiosk_product_id, SUM(quantity) as units_sold, SUM(line_total) as revenue')
            ->whereHas('sale', function ($q) use ($school, $request) {
                $q->where('school_id', $school->id);
                if ($request->filled('start_date')) $q->whereDate('sale_date', '>=', $request->date('start_date'));
                if ($request->filled('end_date')) $q->whereDate('sale_date', '<=', $request->date('end_date'));
            })
            ->with('product')->groupBy('kiosk_product_id')->orderByDesc('units_sold')->limit(5)->get();
        $lowStock = KioskProduct::where('school_id', $school->id)->where('track_stock', true)->where('stock_quantity', '<=', 5)->orderBy('stock_quantity')->limit(10)->get();
        $openTills = KioskTillSession::where('school_id', $school->id)->where('status', 'open')->with('cashier')->get();
        return view('admin.kiosk.index', compact('sales', 'todayTotal', 'monthTotal', 'periodTotal', 'bestSellers', 'lowStock', 'openTills'));
    }

    public function products(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $products = KioskProduct::where('school_id', $school->id)
            ->orderBy('name')
            ->paginate(25);

        return view('admin.kiosk.products', compact('products'));
    }

    public function stockMovements(Request $request)
    {
        $school = Auth::user()?->school;
        abort_unless($school, 403);
        $query = \App\Models\KioskStockMovement::where('school_id', $school->id)->with(['product', 'createdBy'])->latest();
        if ($request->filled('product_id')) $query->where('kiosk_product_id', $request->integer('product_id'));
        if ($request->filled('start_date')) $query->whereDate('created_at', '>=', $request->date('start_date'));
        if ($request->filled('end_date')) $query->whereDate('created_at', '<=', $request->date('end_date'));
        $movements = $query->paginate(30)->withQueryString();
        $products = KioskProduct::where('school_id', $school->id)->orderBy('name')->get(['id', 'name', 'code']);
        return view('admin.kiosk.stock-movements', compact('movements', 'products'));
    }

    public function storeProduct(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:50'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'track_stock' => ['boolean'],
        ]);

        $this->kioskService->createProduct(array_merge($validated, [
            'school_id' => $school->id,
            'is_active' => true,
        ]));

        return redirect()->route('admin.kiosk.products')->with('success', 'Kiosk product created successfully.');
    }

    public function updateProduct(Request $request, KioskProduct $product)
    {
        $school = Auth::user()?->school;
        if (! $school || $product->school_id !== $school->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:50'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');

        \Illuminate\Support\Facades\DB::transaction(function () use ($product, $validated) {
            $before = (float) $product->stock_quantity;
            $product->update($validated);
            $after = (float) $product->fresh()->stock_quantity;
            if ($before !== $after) {
                \App\Models\KioskStockMovement::create([
                    'school_id' => $product->school_id, 'kiosk_product_id' => $product->id,
                    'created_by' => Auth::id(), 'movement_type' => 'manual_adjustment',
                    'quantity_delta' => $after - $before, 'quantity_before' => $before, 'quantity_after' => $after,
                    'reference_type' => 'product_update', 'reference_id' => $product->id,
                    'notes' => 'Stock adjusted from product management form',
                ]);
            }
        });

        return redirect()->route('admin.kiosk.products')->with('success', 'Product updated successfully.');
    }

    public function pos()
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $products = KioskProduct::where('school_id', $school->id)
            ->active()
            ->orderBy('name')
            ->get();

        $bankAccounts = BankAccount::where('school_id', $school->id)->active()->get();
        $openTill = KioskTillSession::where('school_id', $school->id)->where('cashier_id', Auth::id())->where('status', 'open')->first();

        return view('admin.kiosk.pos', compact('products', 'bankAccounts', 'openTill'));
    }

    public function storeSale(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $validated = $request->validate([
            'payment_method' => ['required', 'in:cash,mobile_money,bank_transfer,card'],
            'bank_account_id' => ['nullable', TenantExists::make('bank_accounts')],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.kiosk_product_id' => ['required', TenantExists::make('kiosk_products')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $sale = $this->kioskService->recordSale(array_merge($validated, [
                'school_id' => $school->id,
                'cashier_id' => Auth::id(),
                'sale_date' => now()->toDateString(),
            ]));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['sale' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.kiosk.sales.show', $sale)->with('success', "Sale #{$sale->receipt_number} completed successfully.");
    }

    public function showSale(KioskSale $sale)
    {
        $school = Auth::user()?->school;
        if (! $school || $sale->school_id !== $school->id) {
            abort(403);
        }
        if (! Auth::user()->can('kiosk.sales.view') && $sale->cashier_id !== Auth::id()) {
            abort(403);
        }

        $sale->load(['items.product', 'cashier', 'bankAccount', 'journalBatch.entries.account']);

        return view('admin.kiosk.receipt', compact('sale'));
    }
}
