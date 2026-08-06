@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex flex-col gap-6">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Issue New Loan</h1>
            <p class="text-xs text-slate-400 mt-1">Create a loan record for an employee.</p>
        </div>

        <form method="POST" action="{{ route('admin.loans.store') }}">
            @csrf
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6 space-y-6">
                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Employee</label>
                    <select name="employee_id" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs" required>
                        <option value="">Select Employee</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_number }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Loan Type</label>
                        <select name="loan_type" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs" required>
                            <option value="school">School Loan</option>
                            <option value="bank">Bank Loan</option>
                            <option value="sacco">SACCO</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Loan Provider</label>
                        <input type="text" name="loan_provider" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs" placeholder="e.g. School, CABS, etc.">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Loan Amount ($)</label>
                        <input type="number" step="0.01" name="loan_amount" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Interest Rate (%)</label>
                        <input type="number" step="0.01" name="interest_rate" value="0" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Repayment Period (months)</label>
                        <input type="number" name="repayment_period_months" min="1" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs" required>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Disbursed Date</label>
                        <input type="date" name="disbursed_date" value="{{ now()->format('Y-m-d') }}" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs" required>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">First Payment Date</label>
                    <input type="date" name="first_payment_date" value="{{ now()->addMonth()->format('Y-m-d') }}" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Purpose</label>
                    <textarea name="purpose" rows="2" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs" placeholder="e.g. School fees, emergency, etc."></textarea>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-50 text-xs"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-6">
                <a href="{{ route('admin.loans.index') }}" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-slate-700">Cancel</a>
                <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2 text-sm font-medium text-white hover:bg-emerald-700">Create Loan</button>
            </div>
        </form>
    </div>
</div>
@endsection
