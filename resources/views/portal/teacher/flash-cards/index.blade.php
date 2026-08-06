@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-lg font-semibold text-slate-50">Flash Card Sets</h1>
        <p class="text-xs text-slate-400 mt-1">Create and manage study flashcards</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('teacher.flash-cards.create') }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">
            New Set
        </a>
    </div>
</div>

<div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
    @if($sets->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Title</th>
                        <th class="px-4 py-3 text-left font-medium">Cards</th>
                        <th class="px-4 py-3 text-left font-medium">Subject</th>
                        <th class="px-4 py-3 text-left font-medium">Class</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-left font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($sets as $set)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-100">{{ $set->title }}</div>
                                @if($set->description)
                                    <div class="text-[11px] text-slate-400 mt-1">{{ Str::limit($set->description, 60) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ $set->items_count ?? $set->items->count() }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $set->subject->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $set->schoolClass->name ?? 'All' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium capitalize
                                    {{ $set->status === 'published' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400' : '' }}
                                    {{ $set->status === 'draft' ? 'border-slate-500/30 bg-slate-500/10 text-slate-400' : '' }}
                                    {{ $set->status === 'archived' ? 'border-rose-500/30 bg-rose-500/10 text-rose-400' : '' }}">
                                    {{ $set->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('teacher.flash-cards.show', $set) }}" class="rounded-full bg-slate-800 px-3 py-1 text-[11px] font-medium text-slate-100 hover:bg-slate-700 transition">View</a>
                                    <a href="{{ route('teacher.flash-cards.edit', $set) }}" class="rounded-full bg-indigo-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-indigo-600 transition">Edit</a>
                                    @if($set->status === 'draft')
                                        <form method="POST" action="{{ route('teacher.flash-cards.publish', $set) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded-full bg-emerald-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-emerald-600 transition">Publish</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('teacher.flash-cards.destroy', $set) }}" class="inline" onsubmit="return confirm('Delete this set?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-full bg-red-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-red-600 transition">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $sets->links() }}</div>
    @else
        <div class="text-center py-12">
            <p class="text-sm text-slate-400">No flash card sets yet.</p>
            <a href="{{ route('teacher.flash-cards.create') }}" class="mt-3 inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">
                Create your first set
            </a>
        </div>
    @endif
</div>
@endsection
