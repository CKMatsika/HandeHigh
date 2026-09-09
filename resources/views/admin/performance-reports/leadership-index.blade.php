@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header & Navigation -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-slate-900/80 p-6 rounded-2xl border border-slate-800 backdrop-blur-xl shadow-xl">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-indigo-400 mb-1">
                <span>Academic</span>
                <span>/</span>
                <span>Exams & Assignments</span>
                <span>/</span>
                <span class="text-slate-400">Executive Performance Reports</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-white flex items-center gap-3">
                <span>End-of-Term Performance Reports</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                    Academic Leadership
                </span>
            </h1>
            <p class="text-sm text-slate-400 mt-1">Review student progress across all subjects, initialize term reports, enter subject marks, provide executive comments, apply digital signatures and official school stamp, and finalize reports.</p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <!-- Generate / Initialize Reports Button -->
            <button type="button" onclick="document.getElementById('initialize-modal').classList.remove('hidden')" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-black bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-lg shadow-indigo-600/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Initialize / Sync Reports</span>
            </button>

            <!-- Teacher Entry Jump Button -->
            <button type="button" onclick="document.getElementById('teacher-entry-jump-modal').classList.remove('hidden')" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-200 transition border border-slate-700">
                <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                <span>Enter Subject Marks</span>
            </button>

            <!-- Release Policy Settings Button -->
            <button type="button" onclick="document.getElementById('policy-modal').classList.remove('hidden')" 
                    class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 transition border border-slate-700">
                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span>Release Policy</span>
            </button>
        </div>
    </div>

    <!-- Release Status Banner -->
    <div class="p-4 rounded-2xl border {{ $releaseStatus['is_available'] ? 'bg-emerald-950/20 border-emerald-500/20 text-emerald-300' : 'bg-amber-950/20 border-amber-500/20 text-amber-300' }} flex items-center justify-between text-xs font-semibold backdrop-blur-xl">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full {{ $releaseStatus['is_available'] ? 'bg-emerald-400' : 'bg-amber-400 animate-pulse' }}"></span>
            <span>Policy Status: {{ $releaseStatus['message'] }}</span>
        </div>
        <span class="uppercase tracking-wider px-2 py-0.5 rounded bg-slate-900/80 border border-slate-800">
            Mode: {{ str_replace('_', ' ', $releaseStatus['policy']) }}
        </span>
    </div>

    <!-- Executive KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900/90 rounded-2xl border border-slate-800 p-5 shadow-lg">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Reports</span>
            <div class="text-3xl font-black text-white mt-1">{{ $totalReportsCount }}</div>
            <p class="text-[11px] text-slate-500 mt-1">Generated student report profiles</p>
        </div>

        <div class="bg-slate-900/90 rounded-2xl border border-slate-800 p-5 shadow-lg">
            <span class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Teacher Entry</span>
            <div class="text-3xl font-black text-indigo-300 mt-1">{{ $inProgressCount }}</div>
            <p class="text-[11px] text-slate-500 mt-1">Currently being populated</p>
        </div>

        <div class="bg-slate-900/90 rounded-2xl border border-slate-800 p-5 shadow-lg">
            <span class="text-xs font-bold text-amber-400 uppercase tracking-wider">Awaiting Leadership</span>
            <div class="text-3xl font-black text-amber-300 mt-1">{{ $awaitingLeadershipCount }}</div>
            <p class="text-[11px] text-slate-500 mt-1">Subjects complete • Ready to sign</p>
        </div>

        <div class="bg-slate-900/90 rounded-2xl border border-slate-800 p-5 shadow-lg">
            <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Finalized & Locked</span>
            <div class="text-3xl font-black text-emerald-300 mt-1">{{ $finalizedCount }}</div>
            <p class="text-[11px] text-slate-500 mt-1">Signed, stamped & published</p>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <form method="GET" action="{{ route('admin.exams.performance-reports.index') }}" class="bg-slate-900/80 p-4 rounded-2xl border border-slate-800 flex flex-wrap items-center gap-3">
        <select name="academic_year" class="bg-slate-950 border border-slate-700 text-slate-200 text-xs rounded-xl px-3 py-2 font-medium" onchange="this.form.submit()">
            @foreach($academicYears as $year)
                <option value="{{ $year }}" {{ $academicYear == $year ? 'selected' : '' }}>Year {{ $year }}</option>
            @endforeach
        </select>

        <select name="term" class="bg-slate-950 border border-slate-700 text-slate-200 text-xs rounded-xl px-3 py-2 font-medium" onchange="this.form.submit()">
            @foreach($terms as $t)
                <option value="{{ $t }}" {{ $term == $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>

        <select name="class_id" class="bg-slate-950 border border-slate-700 text-slate-200 text-xs rounded-xl px-3 py-2 font-medium" onchange="this.form.submit()">
            <option value="">All Classes</option>
            @foreach($classes as $c)
                <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>

        <select name="status" class="bg-slate-950 border border-slate-700 text-slate-200 text-xs rounded-xl px-3 py-2 font-medium" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="AVAILABLE_FOR_TEACHERS" {{ $statusFilter === 'AVAILABLE_FOR_TEACHERS' ? 'selected' : '' }}>Available for Teachers</option>
            <option value="TEACHER_ENTRY_IN_PROGRESS" {{ $statusFilter === 'TEACHER_ENTRY_IN_PROGRESS' ? 'selected' : '' }}>Teacher Entry in Progress</option>
            <option value="AWAITING_LEADERSHIP" {{ $statusFilter === 'AWAITING_LEADERSHIP' ? 'selected' : '' }}>Awaiting Leadership</option>
            <option value="FINALIZED" {{ $statusFilter === 'FINALIZED' ? 'selected' : '' }}>Finalized & Locked</option>
        </select>

        @if($classId || $statusFilter)
            <a href="{{ route('admin.exams.performance-reports.index', ['academic_year' => $academicYear, 'term' => $term]) }}" class="text-xs text-rose-400 hover:underline font-semibold ml-auto">
                Clear Filters
            </a>
        @endif
    </form>

    <!-- Master Reports Roster Matrix -->
    <div class="bg-slate-900/90 rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/80 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800 font-bold">
                    <tr>
                        <th class="py-4 px-5">Student</th>
                        <th class="py-4 px-4">Class</th>
                        <th class="py-4 px-4 text-center">Subjects Completed</th>
                        <th class="py-4 px-4 text-center">Term Avg</th>
                        <th class="py-4 px-4 text-center">Signatures & Stamp</th>
                        <th class="py-4 px-4 text-center">Status</th>
                        <th class="py-4 px-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 font-medium">
                    @forelse($reports as $report)
                        @php
                            $totalSubj = $report->subjects->count();
                            $completeSubj = $report->subjects->where('status', 'complete')->count();
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-4 px-5">
                                <div class="font-bold text-white">{{ $report->student->full_name }}</div>
                                <div class="text-xs text-slate-500">{{ $report->student->admission_number }}</div>
                            </td>

                            <td class="py-4 px-4">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                                    {{ $report->schoolClass->name ?? $report->student->class_name ?? '—' }}
                                </span>
                            </td>

                            <td class="py-4 px-4 text-center">
                                <span class="text-xs font-bold {{ $completeSubj === $totalSubj && $totalSubj > 0 ? 'text-emerald-400' : 'text-amber-400' }}">
                                    {{ $completeSubj }} / {{ $totalSubj }}
                                </span>
                            </td>

                            <td class="py-4 px-4 text-center">
                                <span class="text-sm font-black text-white">
                                    {{ $report->term_average !== null ? number_format($report->term_average, 1) . '%' : '—' }}
                                </span>
                            </td>

                            <td class="py-4 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5 text-xs font-bold">
                                    <span title="Headmaster Signature" class="px-1.5 py-0.5 rounded {{ $report->isHeadmasterSigned() ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-800 text-slate-600' }}">HM</span>
                                    <span title="Deputy Headmaster Signature" class="px-1.5 py-0.5 rounded {{ $report->isDeputySigned() ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-800 text-slate-600' }}">DEP</span>
                                    <span title="Digital Stamp" class="px-1.5 py-0.5 rounded {{ $report->isStampApplied() ? 'bg-purple-500/20 text-purple-400' : 'bg-slate-800 text-slate-600' }}">STAMP</span>
                                </div>
                            </td>

                            <td class="py-4 px-4 text-center">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider
                                    {{ $report->isFinalized() ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 
                                       ($report->status === 'AWAITING_LEADERSHIP' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' : 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/30') }}">
                                    {{ str_replace('_', ' ', $report->status) }}
                                </span>
                            </td>

                            <td class="py-4 px-5 text-right space-x-2">
                                <a href="{{ route('admin.exams.performance-reports.show', $report->id) }}" 
                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-sm">
                                    <span>Review / Sign</span>
                                </a>

                                <a href="{{ route('admin.exams.performance-reports.pdf', $report->id) }}" 
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-300 transition border border-slate-700" title="Download PDF">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center">
                                <div class="max-w-md mx-auto space-y-4">
                                    <div class="w-16 h-16 rounded-full bg-slate-800/80 border border-slate-700 flex items-center justify-center mx-auto text-2xl">
                                        📋
                                    </div>
                                    <div>
                                        <h4 class="text-base font-bold text-white">No Performance Reports Initialized Yet</h4>
                                        <p class="text-xs text-slate-400 mt-1">Student report sheets have not been generated for <strong>{{ $term }} ({{ $academicYear }})</strong> yet. You can initialize them in 1-click or let teachers open their subject mark sheets.</p>
                                    </div>
                                    <form action="{{ route('admin.exams.performance-reports.initialize') }}" method="POST" class="pt-2">
                                        @csrf
                                        <input type="hidden" name="academic_year" value="{{ $academicYear }}">
                                        <input type="hidden" name="term" value="{{ $term }}">
                                        @if($classId)
                                            <input type="hidden" name="class_id" value="{{ $classId }}">
                                        @endif
                                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-black bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-xl shadow-indigo-600/20">
                                            <span>Generate Student Reports for {{ $term }} ({{ $academicYear }})</span>
                                            <span>→</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reports->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $reports->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Batch Initialize Reports Modal -->
<div id="initialize-modal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <h3 class="text-lg font-black text-white">Initialize Student Reports</h3>
            <button type="button" onclick="document.getElementById('initialize-modal').classList.add('hidden')" class="text-slate-400 hover:text-white font-bold">✕</button>
        </div>

        <form action="{{ route('admin.exams.performance-reports.initialize') }}" method="POST" class="space-y-4">
            @csrf
            <p class="text-xs text-slate-400">This will generate performance report profiles and populate subject line items for all enrolled students in the selected period.</p>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Academic Year</label>
                <select name="academic_year" class="w-full bg-slate-950 border border-slate-700 text-white text-xs rounded-xl p-3">
                    @foreach($academicYears as $year)
                        <option value="{{ $year }}" {{ $academicYear == $year ? 'selected' : '' }}>Year {{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Term</label>
                <select name="term" class="w-full bg-slate-950 border border-slate-700 text-white text-xs rounded-xl p-3">
                    @foreach($terms as $t)
                        <option value="{{ $t }}" {{ $term == $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Target Class (Optional)</label>
                <select name="class_id" class="w-full bg-slate-950 border border-slate-700 text-white text-xs rounded-xl p-3">
                    <option value="">All Classes & Grades</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ $classId == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->grade }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('initialize-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 text-slate-300">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white">Generate Reports Now</button>
            </div>
        </form>
    </div>
</div>

<!-- Teacher Entry Mode Jump Modal -->
<div id="teacher-entry-jump-modal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <h3 class="text-lg font-black text-white">Enter Subject Marks & Remarks</h3>
            <button type="button" onclick="document.getElementById('teacher-entry-jump-modal').classList.add('hidden')" class="text-slate-400 hover:text-white font-bold">✕</button>
        </div>

        <div class="space-y-4">
            <p class="text-xs text-slate-400">Select a Class and Subject to enter/edit student marks, grades, and rich formatted teacher remarks.</p>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Select Class</label>
                <select id="jump-class-id" class="w-full bg-slate-950 border border-slate-700 text-white text-xs rounded-xl p-3">
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->grade }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Select Subject</label>
                <select id="jump-subject-id" class="w-full bg-slate-950 border border-slate-700 text-white text-xs rounded-xl p-3">
                    @foreach($subjects as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->code }})</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('teacher-entry-jump-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 text-slate-300">Cancel</button>
                <button type="button" onclick="jumpToTeacherEntry()" class="px-4 py-2 rounded-xl text-xs font-bold bg-purple-600 hover:bg-purple-500 text-white">Open Marks Sheet →</button>
            </div>
        </div>
    </div>
</div>

<script>
function jumpToTeacherEntry() {
    const classId = document.getElementById('jump-class-id').value;
    const subjectId = document.getElementById('jump-subject-id').value;
    const academicYear = "{{ $academicYear }}";
    const term = "{{ $term }}";

    if (!classId || !subjectId) {
        alert('Please select both a class and a subject.');
        return;
    }

    const url = "{{ url('admin/exams/performance-reports/entry') }}/" + classId + "/" + subjectId + "?academic_year=" + academicYear + "&term=" + encodeURIComponent(term);
    window.location.href = url;
}
</script>

<!-- Release Policy Configuration Modal -->
<div id="policy-modal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <h3 class="text-lg font-black text-white">Performance Report Release Policy</h3>
            <button type="button" onclick="document.getElementById('policy-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-lg font-bold">✕</button>
        </div>

        <form action="{{ route('admin.exams.performance-reports.update-release-policy') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="academic_year" value="{{ $academicYear }}">
            <input type="hidden" name="term" value="{{ $term }}">

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1.5">Release Rule</label>
                <select name="performance_report_release_policy" class="w-full bg-slate-950 border border-slate-700 text-slate-200 text-xs rounded-xl p-3 font-medium">
                    <option value="auto_immediate">1. Automatically release immediately after final examination</option>
                    <option value="auto_delay_hours">2. Release X hours after final examination</option>
                    <option value="scheduled_datetime">3. Release on a configured date/time</option>
                    <option value="manual">4. Manual release by Examination Administrator</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1.5">Delay Window (Hours - for Option 2)</label>
                <input type="number" name="performance_report_delay_hours" value="24" min="0" max="168" class="w-full bg-slate-950 border border-slate-700 text-white text-xs rounded-xl p-3">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1.5">Scheduled Release Timestamp (for Option 3)</label>
                <input type="datetime-local" name="performance_report_scheduled_release_at" class="w-full bg-slate-950 border border-slate-700 text-white text-xs rounded-xl p-3">
            </div>

            <div class="pt-2">
                <label class="flex items-center gap-2 text-xs font-bold text-slate-300 cursor-pointer">
                    <input type="checkbox" name="manual_unlock" value="1" class="rounded bg-slate-950 border-slate-700 text-indigo-500">
                    <span>Manually unlock reports for {{ $term }} ({{ $academicYear }}) immediately</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('policy-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 text-slate-300">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white">Save Policy</button>
            </div>
        </form>
    </div>
</div>
@endsection
