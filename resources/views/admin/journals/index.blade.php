@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Journal Entries</h1>
            <p class="text-xs text-slate-400 mt-1">View and post manual journal batches.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.journals.create') }}" class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">New Journal</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 mb-3">
        <form method="GET" class="flex items-center gap-3 text-xs">
            <select name="status" class="rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All Statuses</option>
                @foreach(['draft','posted','reversed'] as $s)
                    <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button class="rounded-full bg-indigo-500 px-4 py-2 font-medium text-white hover:bg-indigo-600 transition" type="submit">Filter</button>
            <a href="{{ route('admin.journals.index') }}" class="text-slate-300 hover:text-white text-xs">Reset</a>
        </form>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Batch #</th>
                        <th class="px-4 py-3 text-left font-medium">Date</th>
                        <th class="px-4 py-3 text-left font-medium">Reference</th>
                        <th class="px-4 py-3 text-left font-medium">Description</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-left font-medium">Entries</th>
                        <th class="px-4 py-3 text-left font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($batches as $batch)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-mono">{{ $batch->batch_number }}</td>
                            <td class="px-4 py-3">{{ $batch->transaction_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">{{ $batch->reference_number ?? '-' }}</td>
                            <td class="px-4 py-3 max-w-md truncate">{{ $batch->description }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full {{ $batch->status === 'posted' ? 'bg-emerald-500/20 text-emerald-200' : 'bg-amber-500/20 text-amber-200' }}">
                                    {{ ucfirst($batch->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">{{ $batch->entries_count }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.journals.show', $batch) }}" class="text-indigo-400 hover:text-indigo-300">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-500">No journal batches found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">
            {{ $batches->links() }}
        </div>
    </div>
@endsection

