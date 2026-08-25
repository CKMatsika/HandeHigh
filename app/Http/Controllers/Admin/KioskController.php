<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\KioskProduct;
use App\Models\KioskSale;
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

        return view('admin.kiosk.index', compact('sales', 'todayTotal', 'monthTotal'));
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

        $product->update($validated);

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

        return view('admin.kiosk.pos', compact('products', 'bankAccounts'));
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
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
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

        $sale->load(['items.product', 'cashier', 'bankAccount', 'journalBatch.entries.account']);

        return view('admin.kiosk.receipt', compact('sale'));
    }
}
