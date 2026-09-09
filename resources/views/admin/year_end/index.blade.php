@extends('layouts.app')

@section('title', 'Year-End Processes & Bulk Promotions')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-100 flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-cyan-500/20 text-cyan-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                Year-End Processes & Bulk Promotions
            </h1>
            <p class="text-xs text-slate-400 mt-1">Manage academic year transitions, automated class promotions, and Form 4 & 6 graduation clearance workflows</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.year-end.clearance.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-300 border border-emerald-500/30 text-xs font-semibold transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Clearance Center</span>
                @if($pendingClearanceCount > 0)
                    <span class="px-1.5 py-0.5 rounded-full bg-amber-500 text-slate-950 font-bold text-[10px]">{{ $pendingClearanceCount }}</span>
                @endif
            </a>
            <button onclick="document.getElementById('newDraftModal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-semibold transition shadow-md shadow-cyan-600/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Generate Year-End Transition</span>
            </button>
        </div>
    </div>

    <!-- Metric Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Active Students -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Active Students</span>
                <div class="w-7 h-7 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold font-mono text-slate-100">{{ $totalActiveStudents }}</div>
                <p class="text-xs text-slate-400 mt-1">Eligible for promotion or graduation</p>
            </div>
        </div>

        <!-- Graduating Candidates -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-purple-400">Exit Candidates (F4, F6, G7)</span>
                <div class="w-7 h-7 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold font-mono text-purple-300">{{ $graduatingCandidateCount }}</div>
                <p class="text-xs text-slate-400 mt-1">Auto-routed to Clearance Center</p>
            </div>
        </div>

        <!-- Awaiting Clearance -->
        <div class="rounded-2xl border border-amber-500/30 bg-amber-950/20 p-5 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-300">Awaiting Clearance</span>
                <div class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold font-mono text-amber-200">{{ $pendingClearanceCount }}</div>
                <p class="text-xs text-amber-400/80 mt-1">Obligations pending verification</p>
            </div>
        </div>

        <!-- Fully Cleared / Exited -->
        <div class="rounded-2xl border border-emerald-500/30 bg-emerald-950/20 p-5 shadow-lg relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-300">Cleared & Exited</span>
                <div class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="mt-3">
                <div class="text-2xl font-bold font-mono text-emerald-300">{{ $fullyClearedCount + $permanentlyExitedCount }}</div>
                <p class="text-xs text-emerald-400/80 mt-1">{{ $permanentlyExitedCount }} permanently graduated</p>
            </div>
        </div>
    </div>

    <!-- Active Draft Banner (if any) -->
    @if($activeDraft)
        <div class="rounded-2xl border border-cyan-500/30 bg-gradient-to-r from-cyan-950/40 via-slate-900 to-slate-900 p-6 shadow-xl relative overflow-hidden flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="space-y-1 z-10">
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-cyan-500/20 border border-cyan-500/30 text-cyan-300 text-xs font-semibold uppercase">
                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-pulse"></span>
                    Active Transition Draft in Progress
                </div>
                <h3 class="text-lg font-bold text-slate-100">
                    Academic Transition: {{ $activeDraft->source_academic_year }} → {{ $activeDraft->target_academic_year }}
                </h3>
                <p class="text-xs text-slate-400">
                    Summary: {{ $activeDraft->summary['total_students'] ?? 0 }} total students ({{ $activeDraft->summary['promotions_count'] ?? 0 }} promotions, {{ $activeDraft->summary['clearance_count'] ?? 0 }} moving to clearance)
                </p>
            </div>
            <div class="flex items-center gap-2 z-10">
                <a href="{{ route('admin.year-end.draft', $activeDraft) }}" class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-semibold transition shadow-md">
                    Review & Customize Draft →
                </a>
            </div>
        </div>
    @endif

    <!-- Transition History Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <div>
                <h3 class="text-base font-semibold text-slate-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                    Year-End Transition Batches & History
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Recorded annual promotions and exit executions</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr class="border-b border-slate-800 uppercase text-[10px] tracking-wider">
                        <th class="px-3 py-2.5 text-left font-medium">Batch #</th>
                        <th class="px-3 py-2.5 text-left font-medium">Transition Period</th>
                        <th class="px-3 py-2.5 text-center font-medium">Total Students</th>
                        <th class="px-3 py-2.5 text-center font-medium">Promoted</th>
                        <th class="px-3 py-2.5 text-center font-medium">Clearance / Leavers</th>
                        <th class="px-3 py-2.5 text-center font-medium">Status</th>
                        <th class="px-3 py-2.5 text-left font-medium">Executed By / Date</th>
                        <th class="px-3 py-2.5 text-right font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($processes as $proc)
                        @php
                            $statusBadge = match($proc->status) {
                                'draft' => ['color' => 'cyan', 'label' => 'Draft Pending Review'],
                                'executed' => ['color' => 'emerald', 'label' => 'Executed & Completed'],
                                'cancelled' => ['color' => 'rose', 'label' => 'Cancelled'],
                                default => ['color' => 'slate', 'label' => ucfirst($proc->status)],
                            };
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-3 py-2.5 font-mono font-medium text-slate-200">
                                #{{ str_pad($proc->id, 4, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-3 py-2.5 font-medium text-slate-100">
                                {{ $proc->source_academic_year }} → <span class="text-cyan-400 font-bold">{{ $proc->target_academic_year }}</span>
                            </td>
                            <td class="px-3 py-2.5 text-center font-mono font-semibold text-slate-200">
                                {{ $proc->summary['total_students'] ?? count($proc->draft_payload ?? []) }}
                            </td>
                            <td class="px-3 py-2.5 text-center font-mono text-emerald-400 font-medium">
                                {{ $proc->summary['promotions_count'] ?? '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-center font-mono text-purple-400 font-medium">
                                {{ $proc->summary['clearance_count'] ?? '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="px-2.5 py-0.5 text-[10px] font-semibold rounded-full bg-{{ $statusBadge['color'] }}-500/20 text-{{ $statusBadge['color'] }}-400 border border-{{ $statusBadge['color'] }}-500/30">
                                    {{ $statusBadge['label'] }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-slate-400">
                                @if($proc->executed_at)
                                    <span>{{ $proc->executed_at->format('d M Y H:i') }}</span>
                                    <span class="block text-[10px] text-slate-500">{{ $proc->approver?->name ?? 'Administrator' }}</span>
                                @else
                                    <span class="text-slate-500 italic">Pending approval</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-right">
                                <a href="{{ route('admin.year-end.draft', $proc) }}" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-medium transition">
                                    {{ $proc->status === 'draft' ? 'Review & Execute' : 'View Details' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">
                                No year-end transition processes recorded yet. Click "Generate Year-End Transition" above to start.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($processes->hasPages())
            <div class="pt-3 border-t border-slate-800">
                {{ $processes->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal: Generate New Transition Draft -->
<div id="newDraftModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="flex items-center justify-between p-4 border-b border-slate-800">
            <h4 class="text-sm font-semibold text-slate-100 flex items-center gap-2">
                <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Generate Year-End Transition Draft
            </h4>
            <button type="button" onclick="document.getElementById('newDraftModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.year-end.draft.create') }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Source Academic Year (Current)</label>
                <input type="text" name="source_academic_year" value="{{ $currentYear }}" required class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-slate-100 text-xs focus:ring-1 focus:ring-cyan-500 focus:outline-none font-mono">
                <p class="text-[11px] text-slate-400 mt-1">The current academic year being closed.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Target Academic Year (New)</label>
                <input type="text" name="target_academic_year" value="{{ $nextYear }}" required class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-slate-100 text-xs focus:ring-1 focus:ring-cyan-500 focus:outline-none font-mono">
                <p class="text-[11px] text-slate-400 mt-1">The upcoming academic year students will be promoted into.</p>
            </div>

            <div class="p-3 bg-cyan-950/30 border border-cyan-500/20 rounded-xl text-[11px] text-cyan-300 space-y-1">
                <p class="font-semibold">How this works:</p>
                <ul class="list-disc list-inside space-y-0.5 text-slate-300">
                    <li>Forms 1-3 & Form 5 are mapped to their next grade stream.</li>
                    <li>Form 4 & Form 6 leavers are staged for graduation clearance.</li>
                    <li>Generates a draft for review before applying changes.</li>
                </ul>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('newDraftModal').classList.add('hidden')" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium transition">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-semibold transition">
                    Generate Draft Plan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
