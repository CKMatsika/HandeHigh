@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Edit Account</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $account->code }} - {{ $account->name }}</p>
        </div>
        <a href="{{ route('admin.accounts.index') }}" class="text-xs text-slate-300 hover:text-white">Back</a>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
        <form method="POST" action="{{ route('admin.accounts.update', $account) }}" class="space-y-4 text-sm">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Code</label>
                    <input name="code" value="{{ old('code', $account->code) }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Name</label>
                    <input name="name" value="{{ old('name', $account->name) }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Type</label>
                    <select name="type" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required>
                        @foreach(['asset','liability','equity','revenue','expense'] as $t)
                            <option value="{{ $t }}" @selected($account->type === $t)>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Category</label>
                    <input name="category" value="{{ old('category', $account->category) }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" required />
                </div>
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Parent</label>
                    <select name="parent_id" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100">
                        <option value="">None</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}" @selected($account->parent_id === $parent->id)>{{ $parent->code }} - {{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-slate-400 text-xs mb-1">Description</label>
                <textarea name="description" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" rows="3">{{ old('description', $account->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-slate-400 text-xs mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $account->sort_order) }}" class="w-full rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100" />
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked($account->is_active) class="rounded border-slate-700 bg-slate-900 text-indigo-500 focus:ring-indigo-500">
                    <span class="text-slate-200 text-xs">Active</span>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.accounts.index') }}" class="rounded-full border border-slate-700 px-4 py-2 text-xs text-slate-200">Cancel</a>
                <button type="submit" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">Save Changes</button>
            </div>
        </form>
    </div>
@endsection

