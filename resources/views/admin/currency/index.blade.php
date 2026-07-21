@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-50">Currency Settings</h1>
            <p class="text-slate-400 mt-1">Manage your school's currency preferences</p>
        </div>
    </div>

    <!-- Current Currency -->
    <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
        <h3 class="text-lg font-semibold text-slate-100 mb-4">Current Currency</h3>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-slate-400">Your school is currently using:</p>
                <p class="text-2xl font-bold text-slate-100 mt-2">
                    {{ App\Services\CurrencyService::getCurrency($currentCurrency)['symbol'] }} 
                    {{ App\Services\CurrencyService::getCurrency($currentCurrency)['name'] }}
                </p>
            </div>
            <div class="text-right">
                <p class="text-slate-400">Exchange Rate (USD to {{ $currentCurrency }}):</p>
                <p class="text-xl font-semibold text-slate-100 mt-1">
                    {{ App\Services\CurrencyService::getExchangeRate('USD', $currentCurrency) }}
                </p>
            </div>
        </div>
    </div>

    <!-- Change Currency -->
    <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
        <h3 class="text-lg font-semibold text-slate-100 mb-4">Change Currency</h3>
        <form action="{{ route('admin.currency.update') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Select Base Currency</label>
                <select name="currency" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2">
                    @foreach($currencies as $code => $currency)
                        <option value="{{ $code }}" {{ $code === $currentCurrency ? 'selected' : '' }}>
                            {{ $currency['symbol'] }} {{ $currency['name'] }} ({{ $code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                Update Currency
            </button>
        </form>
    </div>

    <!-- Currency Converter -->
    <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
        <h3 class="text-lg font-semibold text-slate-100 mb-4">Currency Converter</h3>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Amount</label>
                    <input type="number" id="convert-amount" step="0.01" min="0" 
                           class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2"
                           placeholder="Enter amount">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">From Currency</label>
                    <select id="from-currency" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2">
                        @foreach($currencies as $code => $currency)
                            <option value="{{ $code }}" {{ $code === 'USD' ? 'selected' : '' }}>
                                {{ $currency['symbol'] }} {{ $currency['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">To Currency</label>
                    <select id="to-currency" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2">
                        @foreach($currencies as $code => $currency)
                            <option value="{{ $code }}" {{ $code === $currentCurrency ? 'selected' : '' }}>
                                {{ $currency['symbol'] }} {{ $currency['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">&nbsp;</label>
                    <button type="button" id="convert-btn" 
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
                        Convert
                    </button>
                </div>
            </div>
            
            <div id="conversion-result" class="hidden">
                <div class="bg-slate-700 rounded-lg p-4 border border-slate-600">
                    <div class="text-center">
                        <p class="text-slate-400">Conversion Result</p>
                        <p id="result-amount" class="text-3xl font-bold text-slate-100 mt-2"></p>
                        <p id="result-details" class="text-slate-400 mt-2"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Exchange Rates -->
    <div class="bg-slate-800 rounded-lg p-6 border border-slate-700">
        <h3 class="text-lg font-semibold text-slate-100 mb-4">Current Exchange Rates</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex justify-between items-center p-3 bg-slate-700 rounded-lg">
                <span class="text-slate-300">1 USD → ZIG</span>
                <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::getExchangeRate('USD', 'ZIG') }}</span>
            </div>
            <div class="flex justify-between items-center p-3 bg-slate-700 rounded-lg">
                <span class="text-slate-300">1 ZIG → USD</span>
                <span class="text-slate-100 font-medium">{{ App\Services\CurrencyService::getExchangeRate('ZIG', 'USD') }}</span>
            </div>
        </div>
        <p class="text-slate-400 text-sm mt-4">
            Exchange rates are updated hourly. In production, these would be fetched from the Central Bank of Zimbabwe API.
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const convertBtn = document.getElementById('convert-btn');
    const resultDiv = document.getElementById('conversion-result');
    const resultAmount = document.getElementById('result-amount');
    const resultDetails = document.getElementById('result-details');

    convertBtn.addEventListener('click', function() {
        const amount = document.getElementById('convert-amount').value;
        const fromCurrency = document.getElementById('from-currency').value;
        const toCurrency = document.getElementById('to-currency').value;

        if (!amount || amount <= 0) {
            alert('Please enter a valid amount');
            return;
        }

        fetch('{{ route("admin.currency.convert") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                amount: parseFloat(amount),
                from_currency: fromCurrency,
                to_currency: toCurrency
            })
        })
        .then(response => response.json())
        .then(data => {
            resultAmount.textContent = data.formatted_converted;
            resultDetails.textContent = `${data.formatted_original} = ${data.formatted_converted}`;
            resultDiv.classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred during conversion');
        });
    });
});
</script>
@endpush
