<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KioskProduct;
use App\Models\KioskStockMovement;
use App\Models\KioskStockTake;
use App\Models\KioskStockTakeItem;
use App\Models\KioskStockTakeReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class KioskStockTakeController extends Controller
{
    public function index()
    {
        $takes = KioskStockTake::where('school_id', Auth::user()->school_id)->with(['items', 'reviews'])->latest()->paginate(20);
        return view('admin.kiosk.stock-takes.index', compact('takes'));
    }

    public function create()
    {
        $products = KioskProduct::where('school_id', Auth::user()->school_id)->where('track_stock', true)->where('is_active', true)->orderBy('name')->get();
        return view('admin.kiosk.stock-takes.create', compact('products'));
    }

    public function store(Request $request)
    {
        $schoolId = Auth::user()->school_id;
        $data = $request->validate(['stock_take_date' => ['required', 'date'], 'notes' => ['nullable', 'string', 'max:2000'], 'counts' => ['required', 'array', 'min:1'], 'counts.*' => ['required', 'integer', 'min:0']]);
        $take = DB::transaction(function () use ($schoolId, $data) {
            $products = KioskProduct::where('school_id', $schoolId)->where('track_stock', true)->whereIn('id', array_keys($data['counts']))->lockForUpdate()->get()->keyBy('id');
            if ($products->count() !== count($data['counts'])) {
                throw ValidationException::withMessages(['counts' => 'The count list contains a product outside this school or a non-stock product. Refresh and try again.']);
            }
            $take = KioskStockTake::create(['school_id' => $schoolId, 'stock_take_number' => 'KST-' . now()->format('YmdHis') . '-' . Auth::id(), 'status' => 'pending_review', 'taken_by' => Auth::id(), 'stock_take_date' => $data['stock_take_date'], 'notes' => $data['notes'] ?? null, 'submitted_at' => now()]);
            foreach ($data['counts'] as $id => $counted) {
                KioskStockTakeItem::create(['kiosk_stock_take_id' => $take->id, 'kiosk_product_id' => $id, 'system_quantity' => $products[$id]->stock_quantity, 'counted_quantity' => $counted]);
            }
            return $take;
        });
        return redirect()->route('admin.kiosk.stock-takes.index')->with('success', "Stock take {$take->stock_take_number} submitted for independent review.");
    }

    public function approve(Request $request, KioskStockTake $take)
    {
        abort_unless($take->school_id === Auth::user()->school_id, 404);
        $data = $request->validate(['comments' => ['nullable', 'string', 'max:2000']]);
        DB::transaction(function () use ($take, $data) {
            $locked = KioskStockTake::whereKey($take->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending_review') {
                throw ValidationException::withMessages(['stock_take' => 'Only pending stock takes can be approved.']);
            }
            if ($locked->taken_by === Auth::id()) {
                throw ValidationException::withMessages(['stock_take' => 'The person who counted the stock cannot approve the same stock take.']);
            }
            foreach ($locked->items as $item) {
                $product = KioskProduct::where('school_id', $locked->school_id)->whereKey($item->kiosk_product_id)->lockForUpdate()->firstOrFail();
                $before = (float) $product->stock_quantity;
                $counted = (float) $item->counted_quantity;
                if ($before !== (float) $item->system_quantity) {
                    throw ValidationException::withMessages(['stock_take' => 'Stock changed after this count was submitted. Start a fresh stock take before applying it.']);
                }
                $product->update(['stock_quantity' => $counted]);
                if ($before !== $counted) {
                    KioskStockMovement::create(['school_id' => $locked->school_id, 'kiosk_product_id' => $product->id, 'created_by' => Auth::id(), 'movement_type' => 'stock_take_adjustment', 'quantity_delta' => $counted - $before, 'quantity_before' => $before, 'quantity_after' => $counted, 'reference_type' => 'kiosk_stock_take', 'reference_id' => $locked->id, 'notes' => 'Approved stock take adjustment']);
                }
            }
            KioskStockTakeReview::create(['kiosk_stock_take_id' => $locked->id, 'reviewed_by' => Auth::id(), 'decision' => 'approved', 'comments' => $data['comments'] ?? null, 'reviewed_at' => now()]);
            $locked->update(['status' => 'approved', 'reviewed_by' => Auth::id(), 'reviewed_at' => now(), 'review_notes' => $data['comments'] ?? null, 'posted_at' => now()]);
        });
        return redirect()->route('admin.kiosk.stock-takes.index')->with('success', 'Stock take approved and differences applied to kiosk stock.');
    }
}
