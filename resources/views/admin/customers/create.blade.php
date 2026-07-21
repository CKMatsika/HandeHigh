@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Add Customer</h1>
            <p class="text-xs text-slate-400 mt-1">Create a non-student customer.</p>
        </div>
        <a href="{{ route('admin.customers.index') }}" class="text-xs text-slate-300 hover:text-white">Back</a>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <form method="POST" action="{{ route('admin.customers.store') }}" class="space-y-4 text-sm">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Code</label>
                    <input name="code" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Name</label>
                    <input name="name" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Email</label>
                    <input name="email" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Phone</label>
                    <input name="phone" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                </div>
            </div>

            <div>
                <label class="block text-slate-400 text-xs mb-1">Address</label>
                <input name="address" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Type</label>
                    <select name="customer_type" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100">
                        <option value="individual">Individual</option>
                        <option value="business">Business</option>
                        <option value="organization">Organization</option>
                    </select>
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Payment Terms</label>
                    <input name="payment_terms" value="net_30" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Credit Limit</label>
                    <input type="number" step="0.01" name="credit_limit" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.customers.index') }}" class="rounded-full border border-slate-700 px-4 py-2 text-xs text-slate-200">Cancel</a>
                <button type="submit" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">Save</button>
            </div>
        </form>
    </div>
@endsection

