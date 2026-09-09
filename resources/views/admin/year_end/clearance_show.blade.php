@extends('layouts.app')

@section('title', "Clearance Checklist: {$student->full_name}")

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.year-end.index') }}" class="hover:text-slate-200">Year-End Processes</a>
                <span>/</span>
                <a href="{{ route('admin.year-end.clearance.index') }}" class="hover:text-slate-200">Clearance Center</a>
                <span>/</span>
                <span class="text-slate-200 font-medium">{{ $student->full_name }}</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-100 flex items-center gap-3">
                <span>Clearance Verification: {{ $student->full_name }}</span>
                @if($clearance->status === 'permanently_exited')
                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30">
                        Permanently Graduated & Exited
                    </span>
                @elseif($clearance->isFullyCleared())
                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                        Fully Cleared (Ready to Graduate)
                    </span>
                @else
                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30">
                        Pending Department Clearance
                    </span>
                @endif
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.year-end.clearance.index') }}" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium transition">
                ← Back to Clearance Center
            </a>
            @if($clearance->status === 'permanently_exited')
                <a href="{{ route('admin.year-end.clearance.certificate', $clearance) }}" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition shadow-lg shadow-indigo-600/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Print Clearance Certificate</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Student Metadata Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 shadow-xl grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
        <div>
            <span class="text-slate-400 uppercase text-[10px] block">Admission Number</span>
            <span class="font-mono font-bold text-slate-100 text-sm">{{ $student->admission_number ?? '—' }}</span>
        </div>
        <div>
            <span class="text-slate-400 uppercase text-[10px] block">Graduation Stream</span>
            <span class="font-semibold text-slate-200 text-sm">{{ $clearance->graduation_grade ?? $student->grade }}</span>
        </div>
        <div>
            <span class="text-slate-400 uppercase text-[10px] block">Academic Year</span>
            <span class="font-mono text-slate-200 text-sm">{{ $clearance->academic_year }}</span>
        </div>
        <div>
            <span class="text-slate-400 uppercase text-[10px] block">Student Profile Link</span>
            <a href="{{ route('admin.students.show', ['student' => $student, 'tab' => 'finance']) }}" class="text-cyan-400 hover:underline font-medium inline-flex items-center gap-1">
                <span>View Full Finance Profile</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
        </div>
    </div>

    <!-- Department Clearance Checkpoint Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 1. Finance & Debtor Checklist -->
        @php
            $finCleared = in_array($clearance->finance_status, ['cleared', 'waived']);
            $finBorder = $finCleared ? 'border-emerald-500/30' : 'border-rose-500/30';
            $finBg = $finCleared ? 'bg-emerald-950/10' : 'bg-rose-950/10';
        @endphp
        <div class="rounded-2xl border {{ $finBorder }} {{ $finBg }} p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg {{ $finCleared ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400' }} flex items-center justify-center font-bold">
                        1
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-100">Finance & Fee Balance Check</h3>
                        <p class="text-[11px] text-slate-400">Debtor account outstanding balance verification</p>
                    </div>
                </div>
                <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full {{ $finCleared ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30' }}">
                    {{ ucfirst($clearance->finance_status) }}
                </span>
            </div>

            <div class="space-y-3 text-xs">
                <div class="flex justify-between items-center bg-slate-950/60 p-3 rounded-xl border border-slate-800">
                    <span class="text-slate-300">Live Outstanding Fee Debt:</span>
                    <span class="font-mono font-bold text-sm {{ $clearance->finance_balance > 0 ? 'text-rose-400' : 'text-emerald-400' }}">
                        ${{ number_format($clearance->finance_balance, 2) }}
                    </span>
                </div>

                @if($clearance->finance_remarks)
                    <p class="text-[11px] text-slate-400 italic bg-slate-950/40 p-2.5 rounded-lg border border-slate-800">
                        Remarks: {{ $clearance->finance_remarks }}
                    </p>
                @endif

                @if($clearance->status !== 'permanently_exited')
                    <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-800/80">
                        <form method="POST" action="{{ route('admin.year-end.clearance.clear-department', $clearance) }}" class="inline">
                            @csrf
                            <input type="hidden" name="department" value="finance">
                            <input type="hidden" name="action" value="cleared">
                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition">
                                Mark Fees Cleared (Paid in Full)
                            </button>
                        </form>

                        <button type="button" 
                                onclick="openWaiverModal('finance')"
                                class="px-3 py-1.5 rounded-xl bg-amber-600/30 hover:bg-amber-600/50 text-amber-300 border border-amber-500/30 text-xs font-medium transition">
                            Grant Fee Waiver
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- 2. Library Checklist -->
        @php
            $libCleared = in_array($clearance->library_status, ['cleared', 'waived']);
            $libBorder = $libCleared ? 'border-emerald-500/30' : 'border-blue-500/30';
            $libBg = $libCleared ? 'bg-emerald-950/10' : 'bg-blue-950/10';
        @endphp
        <div class="rounded-2xl border {{ $libBorder }} {{ $libBg }} p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg {{ $libCleared ? 'bg-emerald-500/20 text-emerald-400' : 'bg-blue-500/20 text-blue-400' }} flex items-center justify-center font-bold">
                        2
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-100">Library Books Return Check</h3>
                        <p class="text-[11px] text-slate-400">All borrowed school textbooks & literature</p>
                    </div>
                </div>
                <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full {{ $libCleared ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30' }}">
                    {{ ucfirst($clearance->library_status) }}
                </span>
            </div>

            <div class="space-y-3 text-xs">
                @if($borrowedBooks->isNotEmpty())
                    <div class="space-y-1.5">
                        <span class="text-[11px] font-semibold text-slate-300">Unreturned Borrowed Books:</span>
                        <div class="space-y-1 max-h-36 overflow-y-auto">
                            @foreach($borrowedBooks as $b)
                                <div class="flex justify-between items-center bg-slate-950/60 p-2 rounded-lg border border-slate-800 text-[11px]">
                                    <span class="text-slate-200 font-medium">{{ $b->book->title ?? 'Library Book #' . $b->book_id }}</span>
                                    <span class="text-amber-400 font-mono text-[10px]">Due: {{ $b->due_date ? $b->due_date->format('d M Y') : '—' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="p-3 bg-emerald-950/20 border border-emerald-500/20 rounded-xl text-emerald-300 text-[11px]">
                        ✓ No unreturned library books registered for this student.
                    </div>
                @endif

                @if($clearance->library_remarks)
                    <p class="text-[11px] text-slate-400 italic bg-slate-950/40 p-2.5 rounded-lg border border-slate-800">
                        Remarks: {{ $clearance->library_remarks }}
                    </p>
                @endif

                @if($clearance->status !== 'permanently_exited')
                    <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-800/80">
                        <form method="POST" action="{{ route('admin.year-end.clearance.clear-department', $clearance) }}" class="inline">
                            @csrf
                            <input type="hidden" name="department" value="library">
                            <input type="hidden" name="action" value="cleared">
                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition">
                                Mark All Books Returned
                            </button>
                        </form>

                        <button type="button" 
                                onclick="openWaiverModal('library')"
                                class="px-3 py-1.5 rounded-xl bg-amber-600/30 hover:bg-amber-600/50 text-amber-300 border border-amber-500/30 text-xs font-medium transition">
                            Grant Library Waiver
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- 3. School Assets & Uniforms Checklist -->
        @php
            $astCleared = in_array($clearance->assets_status, ['cleared', 'waived']);
            $astBorder = $astCleared ? 'border-emerald-500/30' : 'border-purple-500/30';
            $astBg = $astCleared ? 'bg-emerald-950/10' : 'bg-purple-950/10';
        @endphp
        <div class="rounded-2xl border {{ $astBorder }} {{ $astBg }} p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg {{ $astCleared ? 'bg-emerald-500/20 text-emerald-400' : 'bg-purple-500/20 text-purple-400' }} flex items-center justify-center font-bold">
                        3
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-100">Assets & Equipment Return</h3>
                        <p class="text-[11px] text-slate-400">School uniforms, lab kits, and sports gear</p>
                    </div>
                </div>
                <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full {{ $astCleared ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-purple-500/20 text-purple-400 border border-purple-500/30' }}">
                    {{ ucfirst($clearance->assets_status) }}
                </span>
            </div>

            <div class="space-y-3 text-xs">
                @if($allocatedAssets->isNotEmpty())
                    <div class="space-y-1.5">
                        <span class="text-[11px] font-semibold text-slate-300">Allocated Assets:</span>
                        <div class="space-y-1 max-h-36 overflow-y-auto">
                            @foreach($allocatedAssets as $a)
                                <div class="flex justify-between items-center bg-slate-950/60 p-2 rounded-lg border border-slate-800 text-[11px]">
                                    <span class="text-slate-200 font-medium">{{ $a->schoolAsset->name ?? 'Asset Item' }} (Qty: {{ $a->quantity }})</span>
                                    <span class="text-purple-300 font-mono text-[10px]">Cost: ${{ number_format($a->replacement_cost ?? 0, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="p-3 bg-emerald-950/20 border border-emerald-500/20 rounded-xl text-emerald-300 text-[11px]">
                        ✓ No pending equipment or asset returns registered.
                    </div>
                @endif

                @if($clearance->assets_remarks)
                    <p class="text-[11px] text-slate-400 italic bg-slate-950/40 p-2.5 rounded-lg border border-slate-800">
                        Remarks: {{ $clearance->assets_remarks }}
                    </p>
                @endif

                @if($clearance->status !== 'permanently_exited')
                    <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-800/80">
                        <form method="POST" action="{{ route('admin.year-end.clearance.clear-department', $clearance) }}" class="inline">
                            @csrf
                            <input type="hidden" name="department" value="assets">
                            <input type="hidden" name="action" value="cleared">
                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition">
                                Mark All Assets Returned
                            </button>
                        </form>

                        <button type="button" 
                                onclick="openWaiverModal('assets')"
                                class="px-3 py-1.5 rounded-xl bg-amber-600/30 hover:bg-amber-600/50 text-amber-300 border border-amber-500/30 text-xs font-medium transition">
                            Grant Asset Waiver
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- 4. Boarding & Hostel Checklist -->
        @php
            $brdCleared = in_array($clearance->boarding_status, ['cleared', 'waived', 'not_applicable']);
            $brdBorder = $brdCleared ? 'border-emerald-500/30' : 'border-amber-500/30';
            $brdBg = $brdCleared ? 'bg-emerald-950/10' : 'bg-amber-950/10';
        @endphp
        <div class="rounded-2xl border {{ $brdBorder }} {{ $brdBg }} p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg {{ $brdCleared ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400' }} flex items-center justify-center font-bold">
                        4
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-100">Boarding & Dormitory Clearance</h3>
                        <p class="text-[11px] text-slate-400">Hostel room inspection and bed assignment release</p>
                    </div>
                </div>
                <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full {{ $brdCleared ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30' }}">
                    {{ ucfirst($clearance->boarding_status) }}
                </span>
            </div>

            <div class="space-y-3 text-xs">
                @if($activeBed)
                    <div class="p-3 bg-slate-950/60 rounded-xl border border-slate-800 space-y-1">
                        <p class="text-slate-300">
                            <strong>Hostel / Dorm:</strong> {{ $activeBed->bed?->dormitory?->hostel?->name ?? 'Hostel' }} - {{ $activeBed->bed?->dormitory?->name ?? 'Dorm' }}
                        </p>
                        <p class="text-slate-400 font-mono text-[11px]">
                            Bed #{{ $activeBed->bed?->bed_number ?? 'Bed' }} (Assigned {{ $activeBed->assigned_date ? $activeBed->assigned_date->format('d M Y') : 'Active' }})
                        </p>
                    </div>
                @else
                    <div class="p-3 bg-emerald-950/20 border border-emerald-500/20 rounded-xl text-emerald-300 text-[11px]">
                        ✓ No active bed assignment or Day Scholar status.
                    </div>
                @endif

                @if($clearance->status !== 'permanently_exited' && $activeBed)
                    <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-800/80">
                        <form method="POST" action="{{ route('admin.year-end.clearance.clear-department', $clearance) }}" class="inline">
                            @csrf
                            <input type="hidden" name="department" value="boarding">
                            <input type="hidden" name="action" value="cleared">
                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition">
                                Release Bed & Clear Hostel
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Final Execution & Exit Approval Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
            <div>
                <h3 class="text-base font-bold text-slate-100 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full {{ $clearance->isFullyCleared() ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    Final Graduation & Permanent School Exit
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    @if($clearance->status === 'permanently_exited')
                        Student has successfully graduated and archived under Certificate #{{ $clearance->certificate_number }}.
                    @elseif($clearance->isFullyCleared())
                        All 4 clearance checkpoints have been satisfied. Ready to finalize permanent exit and issue certificate.
                    @else
                        One or more department checkpoints are still pending verification or require administrative waiver.
                    @endif
                </p>
            </div>

            @if($clearance->status !== 'permanently_exited')
                <form method="POST" action="{{ route('admin.year-end.clearance.finalize-exit', $clearance) }}" onsubmit="return confirm('Approve permanent exit and graduate {{ $student->full_name }}?')">
                    @csrf
                    @if(!$clearance->isFullyCleared())
                        <input type="hidden" name="force_override" value="1">
                    @endif
                    <button type="submit" class="px-5 py-2.5 rounded-xl {{ $clearance->isFullyCleared() ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-600/30' : 'bg-amber-600 hover:bg-amber-500 text-white' }} font-bold text-xs transition shadow-lg flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ $clearance->isFullyCleared() ? 'Approve Permanent Exit & Graduate' : 'Override & Force Graduate' }}</span>
                    </button>
                </form>
            @else
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.year-end.clearance.certificate', $clearance) }}" target="_blank" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition shadow-md flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>View / Print Official Certificate</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal: Grant Department Waiver -->
<div id="waiverModal" class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md overflow-hidden shadow-2xl">
        <div class="flex items-center justify-between p-4 border-b border-slate-800">
            <h4 class="text-sm font-semibold text-slate-100 flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Grant Department Clearance Waiver
            </h4>
            <button type="button" onclick="closeWaiverModal()" class="text-slate-400 hover:text-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.year-end.clearance.clear-department', $clearance) }}" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="department" id="waiver_department">
            <input type="hidden" name="action" value="waived">

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Reason / Authorization Notes for Waiver</label>
                <textarea name="remarks" required rows="3" placeholder="Explain waiver justification (e.g. Scholarship clearance approved by Headmaster)..." class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-slate-200 text-xs focus:ring-1 focus:ring-amber-500 focus:outline-none"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeWaiverModal()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium transition">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold transition">
                    Grant Official Waiver
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openWaiverModal(dept) {
        document.getElementById('waiver_department').value = dept;
        const modal = document.getElementById('waiverModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeWaiverModal() {
        const modal = document.getElementById('waiverModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection
