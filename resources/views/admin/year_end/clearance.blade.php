@extends('layouts.app')

@section('title', 'Graduation & Exit Clearance Center')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.year-end.index') }}" class="hover:text-slate-200">Year-End Processes</a>
                <span>/</span>
                <span class="text-slate-200 font-medium">Graduation Clearance Hub</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-100 flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                Graduation & Exit Clearance Center
            </h1>
            <p class="text-xs text-slate-400 mt-1">Multi-department obligation verification for Form 4, Form 6, and school leavers prior to permanent graduation</p>
        </div>
        <div class="flex items-center gap-2">
            @if($counts['fully_cleared'] > 0)
                <form method="POST" action="{{ route('admin.year-end.clearance.bulk-finalize-exit') }}" class="inline" onsubmit="return confirm('Finalize exit and issue certificates for all {{ $counts['fully_cleared'] }} fully cleared graduates?')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition shadow-md shadow-emerald-600/30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Bulk Finalize Exits ({{ $counts['fully_cleared'] }})</span>
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.year-end.index') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium transition">
                ← Dashboard
            </a>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 pb-3">
        <a href="{{ route('admin.year-end.clearance.index', ['status' => 'pending']) }}" 
           class="px-3 py-1.5 rounded-xl text-xs font-medium transition {{ $statusFilter === 'pending' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30 font-semibold' : 'bg-slate-900 text-slate-400 hover:text-slate-200 border border-slate-800' }}">
            Awaiting Clearance ({{ $counts['pending'] }})
        </a>
        <a href="{{ route('admin.year-end.clearance.index', ['status' => 'blocked_finance']) }}" 
           class="px-3 py-1.5 rounded-xl text-xs font-medium transition {{ $statusFilter === 'blocked_finance' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30 font-semibold' : 'bg-slate-900 text-slate-400 hover:text-slate-200 border border-slate-800' }}">
            Unsettled Fees ({{ $counts['blocked_finance'] }})
        </a>
        <a href="{{ route('admin.year-end.clearance.index', ['status' => 'blocked_library']) }}" 
           class="px-3 py-1.5 rounded-xl text-xs font-medium transition {{ $statusFilter === 'blocked_library' ? 'bg-blue-500/20 text-blue-300 border border-blue-500/30 font-semibold' : 'bg-slate-900 text-slate-400 hover:text-slate-200 border border-slate-800' }}">
            Overdue Books ({{ $counts['blocked_library'] }})
        </a>
        <a href="{{ route('admin.year-end.clearance.index', ['status' => 'blocked_assets']) }}" 
           class="px-3 py-1.5 rounded-xl text-xs font-medium transition {{ $statusFilter === 'blocked_assets' ? 'bg-purple-500/20 text-purple-300 border border-purple-500/30 font-semibold' : 'bg-slate-900 text-slate-400 hover:text-slate-200 border border-slate-800' }}">
            Pending Assets ({{ $counts['blocked_assets'] }})
        </a>
        <a href="{{ route('admin.year-end.clearance.index', ['status' => 'fully_cleared']) }}" 
           class="px-3 py-1.5 rounded-xl text-xs font-medium transition {{ $statusFilter === 'fully_cleared' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-semibold' : 'bg-slate-900 text-slate-400 hover:text-slate-200 border border-slate-800' }}">
            Fully Cleared ({{ $counts['fully_cleared'] }})
        </a>
        <a href="{{ route('admin.year-end.clearance.index', ['status' => 'permanently_exited']) }}" 
           class="px-3 py-1.5 rounded-xl text-xs font-medium transition {{ $statusFilter === 'permanently_exited' ? 'bg-slate-700 text-slate-100 border border-slate-600 font-semibold' : 'bg-slate-900 text-slate-400 hover:text-slate-200 border border-slate-800' }}">
            Graduated & Archived ({{ $counts['permanently_exited'] }})
        </a>
    </div>

    <!-- Search & Filter Bar -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 shadow-md">
        <form method="GET" action="{{ route('admin.year-end.clearance.index') }}" class="flex flex-wrap items-center gap-3">
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <div class="flex-1 min-w-[200px]">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}" 
                       placeholder="Search graduate name or admission #..." 
                       class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-slate-100 text-xs focus:ring-1 focus:ring-emerald-500 focus:outline-none">
            </div>
            <div>
                <select name="grade" onchange="this.form.submit()" class="px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-slate-200 text-xs">
                    <option value="">All Graduating Streams</option>
                    <option value="Form 4" {{ request('grade') == 'Form 4' ? 'selected' : '' }}>Form 4 (O-Level)</option>
                    <option value="Form 6" {{ request('grade') == 'Form 6' ? 'selected' : '' }}>Form 6 (A-Level)</option>
                    <option value="Grade 7" {{ request('grade') == 'Grade 7' ? 'selected' : '' }}>Grade 7 (Primary)</option>
                </select>
            </div>
            @if(request('search') || request('grade'))
                <a href="{{ route('admin.year-end.clearance.index', ['status' => $statusFilter]) }}" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-slate-200 text-xs transition">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Clearance Matrix Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <div>
                <h3 class="text-base font-semibold text-slate-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Student Clearance Matrix
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Showing {{ $clearances->total() }} graduate clearance records</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr class="border-b border-slate-800 uppercase text-[10px] tracking-wider">
                        <th class="px-3 py-2.5 text-left font-medium">Student / Admission #</th>
                        <th class="px-3 py-2.5 text-left font-medium">Stream</th>
                        <th class="px-3 py-2.5 text-center font-medium">Finance (Fees)</th>
                        <th class="px-3 py-2.5 text-center font-medium">Library (Books)</th>
                        <th class="px-3 py-2.5 text-center font-medium">Assets & Equip</th>
                        <th class="px-3 py-2.5 text-center font-medium">Boarding / Bed</th>
                        <th class="px-3 py-2.5 text-center font-medium">Overall Status</th>
                        <th class="px-3 py-2.5 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($clearances as $c)
                        @php
                            $finOk = in_array($c->finance_status, ['cleared', 'waived']);
                            $libOk = in_array($c->library_status, ['cleared', 'waived']);
                            $astOk = in_array($c->assets_status, ['cleared', 'waived']);
                            $brdOk = in_array($c->boarding_status, ['cleared', 'waived', 'not_applicable']);

                            $overallBadge = match($c->status) {
                                'pending_clearance' => ['color' => 'amber', 'label' => 'Pending Verification'],
                                'fully_cleared' => ['color' => 'emerald', 'label' => 'Fully Cleared'],
                                'permanently_exited' => ['color' => 'purple', 'label' => 'Graduated / Exited'],
                                default => ['color' => 'slate', 'label' => ucfirst($c->status)],
                            };
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition">
                            <!-- Student Info -->
                            <td class="px-3 py-2.5">
                                <a href="{{ route('admin.year-end.clearance.show', $c) }}" class="font-semibold text-slate-100 hover:text-cyan-400 transition block">
                                    {{ $c->student->full_name }}
                                </a>
                                <span class="font-mono text-[11px] text-slate-400">{{ $c->student->admission_number ?? '—' }}</span>
                            </td>

                            <!-- Stream -->
                            <td class="px-3 py-2.5 text-slate-300">
                                <span class="font-semibold text-slate-200">{{ $c->graduation_grade ?? $c->student->grade }}</span>
                                <span class="block text-[10px] text-slate-500">Year {{ $c->academic_year }}</span>
                            </td>

                            <!-- Finance Checkpoint -->
                            <td class="px-3 py-2.5 text-center">
                                @if($finOk)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px] font-semibold">
                                        ✓ Cleared
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-rose-500/20 text-rose-400 text-[10px] font-semibold" title="Unpaid balance: ${{ number_format($c->finance_balance, 2) }}">
                                        ${{ number_format($c->finance_balance, 2) }} due
                                    </span>
                                @endif
                            </td>

                            <!-- Library Checkpoint -->
                            <td class="px-3 py-2.5 text-center">
                                @if($libOk)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px] font-semibold">
                                        ✓ Cleared
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400 text-[10px] font-semibold">
                                        {{ $c->unreturned_books_count }} unreturned
                                    </span>
                                @endif
                            </td>

                            <!-- Assets Checkpoint -->
                            <td class="px-3 py-2.5 text-center">
                                @if($astOk)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px] font-semibold">
                                        ✓ Cleared
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-400 text-[10px] font-semibold">
                                        {{ $c->unreturned_assets_count }} assigned
                                    </span>
                                @endif
                            </td>

                            <!-- Boarding Checkpoint -->
                            <td class="px-3 py-2.5 text-center">
                                @if($c->boarding_status === 'not_applicable')
                                    <span class="text-slate-500 text-[10px]">Day Scholar</span>
                                @elseif($brdOk)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px] font-semibold">
                                        ✓ Bed Released
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400 text-[10px] font-semibold">
                                        Bed Assigned
                                    </span>
                                @endif
                            </td>

                            <!-- Overall Status -->
                            <td class="px-3 py-2.5 text-center">
                                <span class="px-2.5 py-0.5 text-[10px] font-semibold rounded-full bg-{{ $overallBadge['color'] }}-500/20 text-{{ $overallBadge['color'] }}-400 border border-{{ $overallBadge['color'] }}-500/30">
                                    {{ $overallBadge['label'] }}
                                </span>
                            </td>

                            <!-- Action Buttons -->
                            <td class="px-3 py-2.5 text-right space-x-1 whitespace-nowrap">
                                <a href="{{ route('admin.year-end.clearance.show', $c) }}" class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-medium transition">
                                    Checklist →
                                </a>
                                @if($c->status === 'permanently_exited')
                                    <a href="{{ route('admin.year-end.clearance.certificate', $c) }}" target="_blank" class="px-2 py-1 rounded-lg bg-indigo-600/30 hover:bg-indigo-600/50 text-indigo-300 border border-indigo-500/30 text-[11px] font-medium transition">
                                        Certificate
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">
                                No graduate clearance records found for current filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($clearances->hasPages())
            <div class="pt-3 border-t border-slate-800">
                {{ $clearances->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
