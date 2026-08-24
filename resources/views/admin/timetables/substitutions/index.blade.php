@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.timetables.operations') }}" class="text-slate-400 hover:text-slate-200 transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-50">Teacher Substitutions</h1>
                <p class="text-xs text-slate-400">Review, approve, and audit teacher cover assignments across all active classes.</p>
            </div>
        </div>
    </div>

    <!-- Substitutions Table -->
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/60 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-800/80 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Class & Subject</th>
                        <th class="px-4 py-3">Original Teacher</th>
                        <th class="px-4 py-3">Substitute Teacher</th>
                        <th class="px-4 py-3">Recommendation Score</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($substitutions as $sub)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-mono text-[11px] text-indigo-300">{{ $sub->date->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-100">{{ $sub->slot?->schoolClass?->name ?? '—' }} &bull; {{ $sub->slot?->subject?->name ?? '—' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $sub->slot?->day_of_week }} {{ $sub->slot?->getFormattedTime() }}</div>
                            </td>
                            <td class="px-4 py-3 text-rose-400 font-medium">{{ $sub->originalTeacher?->full_name }}</td>
                            <td class="px-4 py-3 text-purple-300 font-semibold">{{ $sub->substituteTeacher?->full_name }}</td>
                            <td class="px-4 py-3">
                                @if($sub->score !== null)
                                    <span class="rounded bg-indigo-500/10 px-2 py-0.5 font-bold text-indigo-400 border border-indigo-500/20">{{ $sub->score }} / 100</span>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($sub->status === 'approved')
                                    <span class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-400 border border-emerald-500/20">Approved</span>
                                @elseif($sub->status === 'pending')
                                    <span class="rounded-full bg-amber-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-amber-400 border border-amber-500/20">Pending Review</span>
                                @elseif($sub->status === 'rejected')
                                    <span class="rounded-full bg-rose-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-rose-400 border border-rose-500/20">Rejected</span>
                                @else
                                    <span class="rounded-full bg-slate-800 px-2.5 py-0.5 text-[11px] font-semibold text-slate-400">{{ ucfirst($sub->status) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($sub->status === 'pending')
                                    <div class="flex items-center justify-end gap-1.5">
                                        <form action="{{ route('admin.timetables.substitutions.approve', $sub) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-500 transition shadow-sm">Approve</button>
                                        </form>
                                        <form action="{{ route('admin.timetables.substitutions.reject', $sub) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded bg-slate-800 px-2 py-1 text-xs text-slate-300 hover:text-rose-400 transition">Reject</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-[11px] text-slate-500">{{ $sub->approvedBy?->name ? 'By ' . $sub->approvedBy->name : 'Completed' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                                No substitution records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
