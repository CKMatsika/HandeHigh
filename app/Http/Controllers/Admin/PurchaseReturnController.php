<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceivedVoucher;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\School;
use App\Services\PurchaseReturnService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurchaseReturnController extends Controller
{
    protected function school(): School
    {
        return app(TenantContext::class)->school()
            ?? auth()->user()?->school
            ?? abort(404, 'School context missing.');
    }

    public function index(Request $request)
    {
        $school = $this->school();
        $returns = PurchaseReturn::where('school_id', $school->id)
            ->with(['goodsReceivedVoucher', 'purchaseOrder.vendor', 'warehouse', 'creator', 'items.item'])
            ->latest('return_date')
            ->paginate(15);

        return view('admin.inventory.returns.index', compact('school', 'returns'));
    }

    public function create(GoodsReceivedVoucher $voucher)
    {
        $school = $this->school();
        abort_unless((int) $voucher->school_id === (int) $school->id, 404);

        $voucher->load(['items.item', 'purchaseOrder.vendor', 'warehouse']);

        // Calculate already returned quantities
        $returnedQuantities = PurchaseReturnItem::whereIn('goods_received_voucher_item_id', $voucher->items->pluck('id'))
            ->whereHas('purchaseReturn', fn ($q) => $q->where('status', 'posted'))
            ->groupBy('goods_received_voucher_item_id')
            ->selectRaw('goods_received_voucher_item_id, SUM(quantity_returned) as total_returned')
            ->pluck('total_returned', 'goods_received_voucher_item_id')
            ->all();

        return view('admin.inventory.returns.create', compact('school', 'voucher', 'returnedQuantities'));
    }

    public function store(Request $request, GoodsReceivedVoucher $voucher)
    {
        $school = $this->school();
        abort_unless((int) $voucher->school_id === (int) $school->id, 404);

        $validated = $request->validate([
            'return_date' => ['required', 'date'],
            'reason' => ['required', 'string', Rule::in(['damaged', 'defective', 'wrong_specification', 'excess_delivery', 'expired', 'other'])],
            'reason_details' => ['nullable', 'string', 'max:2000'],
            'debit_note_number' => ['nullable', 'string', 'max:50'],
            'return_quantities' => ['required', 'array', 'min:1'],
            'return_quantities.*' => ['required', 'numeric', 'min:0'],
        ]);

        $purchaseReturn = app(PurchaseReturnService::class)->processReturn(
            $voucher,
            $validated['return_quantities'],
            $validated['return_date'],
            $validated['reason'],
            $validated['reason_details'] ?? null,
            $validated['debit_note_number'] ?? null,
            $request->user(),
            $school
        );

        return redirect()->route('admin.inventory.returns.show', $purchaseReturn)
            ->with('success', 'Purchase return posted. Stock balances updated and debit note generated.');
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        $school = $this->school();
        abort_unless((int) $purchaseReturn->school_id === (int) $school->id, 404);

        $purchaseReturn->load([
            'goodsReceivedVoucher',
            'purchaseOrder.vendor',
            'warehouse',
            'creator',
            'items.item',
            'items.goodsReceivedVoucherItem',
        ]);

        return view('admin.inventory.returns.show', compact('school', 'purchaseReturn'));
    }

    public function print(PurchaseReturn $purchaseReturn)
    {
        $school = $this->school();
        abort_unless((int) $purchaseReturn->school_id === (int) $school->id, 404);

        $purchaseReturn->load([
            'goodsReceivedVoucher',
            'purchaseOrder.vendor',
            'warehouse',
            'creator',
            'items.item',
            'items.goodsReceivedVoucherItem',
        ]);
        $branding = app(\App\Services\Branding\SchoolDocumentBrandingService::class)->getBrandingPayload($school);

        return view('admin.inventory.returns.print', compact('school', 'purchaseReturn', 'branding'));
    }
}
