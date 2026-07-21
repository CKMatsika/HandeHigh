@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Edit Vendor</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $vendor->code }} - {{ $vendor->name }}</p>
        </div>
        <a href="{{ route('admin.vendors.index') }}" class="text-xs text-slate-300 hover:text-white">Back</a>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <form method="POST" action="{{ route('admin.vendors.update', $vendor) }}" class="space-y-4 text-sm">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Code</label>
                    <input name="code" value="{{ $vendor->code }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Name</label>
                    <input name="name" value="{{ $vendor->name }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Email</label>
                    <input name="email" value="{{ $vendor->email }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Phone</label>
                    <input name="phone" value="{{ $vendor->phone }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                </div>
            </div>

            <div>
                <label class="block text-slate-400 text-xs mb-1">Address</label>
                <input name="address" value="{{ $vendor->address }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Type</label>
                    <input name="vendor_type" value="{{ $vendor->vendor_type }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Payment Terms</label>
                    <input name="payment_terms" value="{{ $vendor->payment_terms }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked($vendor->is_active) class="rounded border-slate-700 bg-slate-900 text-indigo-500 focus:ring-indigo-500">
                <span class="text-slate-200 text-xs">Active</span>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.vendors.index') }}" class="rounded-full border border-slate-700 px-4 py-2 text-xs text-slate-200">Cancel</a>
                <button type="submit" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">Save</button>
            </div>
        </form>
    </div>
@endsection

