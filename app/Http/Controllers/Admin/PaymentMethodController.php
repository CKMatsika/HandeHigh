<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403);
        }

        $paymentMethods = PaymentMethod::orderBy('type')->orderBy('name')->paginate(20);
        
        return view('admin.payment-methods.index', compact('paymentMethods'));
    }

    public function create()
    {
        return view('admin.payment-methods.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:payment_methods,code'],
            'type' => ['required', 'in:mobile_money,card,bank_transfer,online'],
            'provider' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['nullable', 'string'],
            'transaction_fee_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'fixed_transaction_fee' => ['required', 'numeric', 'min:0'],
            'minimum_amount' => ['required', 'numeric', 'min:0'],
            'maximum_amount' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        PaymentMethod::create($validated);

        return redirect()->route('admin.payment-methods.index')
            ->with('success', 'Payment method created successfully.');
    }

    public function show(PaymentMethod $paymentMethod)
    {
        $paymentMethod->load(['payments' => function($query) {
            $query->latest()->take(10);
        }]);

        return view('admin.payment-methods.show', compact('paymentMethod'));
    }

    public function edit(PaymentMethod $paymentMethod)
    {
        return view('admin.payment-methods.edit', compact('paymentMethod'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:payment_methods,code,' . $paymentMethod->id],
            'type' => ['required', 'in:mobile_money,card,bank_transfer,online'],
            'provider' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['nullable', 'string'],
            'transaction_fee_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'fixed_transaction_fee' => ['required', 'numeric', 'min:0'],
            'minimum_amount' => ['required', 'numeric', 'min:0'],
            'maximum_amount' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $paymentMethod->update($validated);

        return redirect()->route('admin.payment-methods.show', $paymentMethod)
            ->with('success', 'Payment method updated successfully.');
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        // Check if payment method has any payments
        if ($paymentMethod->payments()->exists()) {
            return redirect()->route('admin.payment-methods.index')
                ->with('error', 'Cannot delete payment method with existing payments.');
        }

        $paymentMethod->delete();

        return redirect()->route('admin.payment-methods.index')
            ->with('success', 'Payment method deleted successfully.');
    }

    public function toggle(PaymentMethod $paymentMethod)
    {
        $paymentMethod->update(['is_active' => !$paymentMethod->is_active]);

        $status = $paymentMethod->is_active ? 'activated' : 'deactivated';
        
        return redirect()->route('admin.payment-methods.index')
            ->with('success', "Payment method {$status} successfully.");
    }

    // API methods for frontend
    public function getActivePaymentMethods(Request $request)
    {
        $currency = $request->get('currency', 'USD');
        $type = $request->get('type');
        
        $query = PaymentMethod::active()->byCurrency($currency);
        
        if ($type) {
            $query->byType($type);
        }
        
        $paymentMethods = $query->get()->map(function ($method) {
            return [
                'id' => $method->id,
                'name' => $method->name,
                'code' => $method->code,
                'type' => $method->type,
                'type_label' => $method->type_label,
                'provider' => $method->provider,
                'description' => $method->description,
                'currency' => $method->currency,
                'currency_symbol' => $method->currency_symbol,
                'transaction_fee_percentage' => $method->transaction_fee_percentage,
                'fixed_transaction_fee' => $method->fixed_transaction_fee,
                'minimum_amount' => $method->minimum_amount,
                'maximum_amount' => $method->maximum_amount,
                'icon' => $method->type_icon,
            ];
        });

        return response()->json($paymentMethods);
    }

    public function calculateFee(Request $request)
    {
        $validated = $request->validate([
            'payment_method_id' => ['required', 'exists:payment_methods,id'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $paymentMethod = PaymentMethod::findOrFail($validated['payment_method_id']);
        $amount = $validated['amount'];

        $fee = $paymentMethod->calculateTransactionFee($amount);
        $total = $paymentMethod->getTotalAmountWithFee($amount);
        $canProcess = $paymentMethod->canProcessAmount($amount);

        return response()->json([
            'amount' => $amount,
            'transaction_fee' => $fee,
            'total_amount' => $total,
            'can_process' => $canProcess,
            'currency' => $paymentMethod->currency,
            'currency_symbol' => $paymentMethod->currency_symbol,
        ]);
    }
}
