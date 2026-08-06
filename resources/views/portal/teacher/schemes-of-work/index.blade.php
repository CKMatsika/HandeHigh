@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-lg font-semibold text-slate-50">Schemes of Work</h1>
        <p class="text-xs text-slate-400 mt-1">Manage your teaching schemes</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('teacher.schemes-of-work.create') }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">
            New Scheme
        </a>
    </div>
</div>

<div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
    @if($schemes->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Title</th>
                        <th class="px-4 py-3 text-left font-medium">Subject</th>
                        <th class="px-4 py-3 text-left font-medium">Class</th>
                        <th class="px-4 py-3 text-left font-medium">Year / Term</th>
                        <th class="px-4 py-3 text-left font-medium">Status</th>
                        <th class="px-4 py-3 text-left font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($schemes as $scheme)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-100">{{ $scheme->title }}</div>
                                @if($scheme->description)
                                    <div class="text-[11px] text-slate-400 mt-1">{{ Str::limit($scheme->description, 60) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ $scheme->subject->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $scheme->schoolClass->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $scheme->academic_year }} / {{ $scheme->term }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full border border-{{ $scheme->status_color }}-500/30 bg-{{ $scheme->status_color }}-500/10 px-2 py-0.5 text-[11px] font-medium text-{{ $scheme->status_color }}-400 capitalize">
                                    {{ $scheme->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('teacher.schemes-of-work.show', $scheme) }}" class="rounded-full bg-slate-800 px-3 py-1 text-[11px] font-medium text-slate-100 hover:bg-slate-700 transition">View</a>
                                    @if(in_array($scheme->status, ['draft', 'rejected']))
                                        <a href="{{ route('teacher.schemes-of-work.edit', $scheme) }}" class="rounded-full bg-indigo-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-indigo-600 transition">Edit</a>
                                    @endif
                                    @if(in_array($scheme->status, ['draft', 'preview', 'rejected']))
                                        <form method="POST" action="{{ route('teacher.schemes-of-work.submit', $scheme) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded-full bg-emerald-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-emerald-600 transition">Submit</button>
                                        </form>
                                    @endif
                                    @if(!in_array($scheme->status, ['submitted', 'approved']))
                                        <form method="POST" action="{{ route('teacher.schemes-of-work.destroy', $scheme) }}" class="inline" onsubmit="return confirm('Delete this scheme?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-full bg-red-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-red-600 transition">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $schemes->links() }}</div>
    @else
        <div class="text-center py-12">
            <p class="text-sm text-slate-400">No schemes of work yet.</p>
            <a href="{{ route('teacher.schemes-of-work.create') }}" class="mt-3 inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">
                Create your first scheme
            </a>
        </div>
    @endif
</div>
@endsection
