@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Edit Bank Account</h1>
            <p class="text-xs text-slate-400 mt-1">Update bank account information.</p>
        </div>
        <a href="{{ route('admin.bank-accounts.show', $bankAccount) }}" class="inline-flex items-center rounded-full bg-slate-700 px-4 py-1.5 text-xs font-medium text-white hover:bg-slate-600 transition">
            Back to Account
        </a>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <form action="{{ route('admin.bank-accounts.update', $bankAccount) }}" method="POST">
            @method('PUT')
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Account Name *</label>
                    <input type="text" name="name" value="{{ $bankAccount->name }}" required
                           class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Account Number</label>
                    <input type="text" value="{{ $bankAccount->code }}" disabled
                           class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-400 px-3 py-2 text-sm focus:outline-none cursor-not-allowed">
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Account Type</label>
                    <input type="text" value="{{ ucfirst($bankAccount->type) }}" disabled
                           class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-400 px-3 py-2 text-sm focus:outline-none cursor-not-allowed">
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Category</label>
                    <input type="text" value="{{ ucfirst($bankAccount->category) }}" disabled
                           class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-400 px-3 py-2 text-sm focus:outline-none cursor-not-allowed">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-300 mb-2">Description</label>
                    <textarea name="description" rows="3"
                              class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ $bankAccount->description }}</textarea>
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-2">Status</label>
                    <select name="is_active"
                            class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="1" {{ $bankAccount->is_active ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ !$bankAccount->is_active ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="submit" class="flex-1 bg-indigo-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-indigo-600 transition">
                    Update Account
                </button>
                <a href="{{ route('admin.bank-accounts.show', $bankAccount) }}" class="flex-1 bg-slate-700 text-slate-300 rounded-lg px-4 py-2 text-sm font-medium hover:bg-slate-600 transition text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
