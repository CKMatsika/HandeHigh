<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CurrencyController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school) {
            abort(403, 'No school associated with your account');
        }

        $currencies = CurrencyService::getCurrencies();
        $currentCurrency = $school->currency;

        return view('admin.currency.index', compact('currencies', 'currentCurrency', 'school'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $school = $user?->school;

        if (!$school) {
            abort(403, 'No school associated with your account');
        }

        $request->validate([
            'currency' => 'required|string|in:' . implode(',', array_keys(CurrencyService::getCurrencies())),
        ]);

        $school->update([
            'currency' => $request->currency,
        ]);

        return redirect()->route('admin.currency.index')
            ->with('success', 'Currency updated successfully');
    }

    public function convert(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'from_currency' => 'required|string|in:' . implode(',', array_keys(CurrencyService::getCurrencies())),
            'to_currency' => 'required|string|in:' . implode(',', array_keys(CurrencyService::getCurrencies())),
        ]);

        $convertedAmount = CurrencyService::convert(
            $request->amount,
            $request->from_currency,
            $request->to_currency
        );

        return response()->json([
            'original_amount' => $request->amount,
            'from_currency' => $request->from_currency,
            'to_currency' => $request->to_currency,
            'converted_amount' => $convertedAmount,
            'formatted_original' => CurrencyService::format($request->amount, $request->from_currency),
            'formatted_converted' => CurrencyService::format($convertedAmount, $request->to_currency),
        ]);
    }
}
