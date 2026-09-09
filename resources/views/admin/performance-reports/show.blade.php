@extends('layouts.app')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900/80 p-5 rounded-2xl border border-slate-800 backdrop-blur-xl shadow-xl">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.exams.performance-reports.index', ['academic_year' => $report->academic_year, 'term' => $report->term]) }}" 
               class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition border border-slate-700">
                ←
            </a>
            <div>
                <span class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Student Performance Report</span>
                <h1 class="text-xl font-black text-white flex items-center gap-2">
                    <span>{{ $report->student->full_name }}</span>
                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-800 text-slate-300 border border-slate-700">v{{ $report->version }}</span>
                </h1>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('admin.exams.performance-reports.stream-pdf', $report->id) }}" target="_blank" 
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-200 transition border border-slate-700">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <span>Print / Preview</span>
            </a>

            <a href="{{ route('admin.exams.performance-reports.pdf', $report->id) }}" 
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-lg shadow-indigo-600/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <span>Download PDF</span>
            </a>

            @if(!$report->isFinalized() && auth()->user()->hasAnyRole(['super-admin', 'school-admin', 'headmaster', 'deputy-headmaster']))
                <form action="{{ route('admin.exams.performance-reports.finalize', $report->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to finalize and lock this report? Once finalized, edits will require an authorised reopening audit.')">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-lg shadow-emerald-600/20">
                        Finalize & Lock Report 🔒
                    </button>
                </form>
            @elseif($report->isFinalized() && auth()->user()->hasAnyRole(['super-admin', 'school-admin', 'headmaster']))
                <button type="button" onclick="document.getElementById('reopen-modal').classList.remove('hidden')" 
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-500 text-white transition shadow-lg shadow-amber-600/20">
                    Reopen for Amendment 🔓
                </button>
            @endif
        </div>
    </div>

    <!-- Status Banner -->
    <div class="p-4 rounded-2xl border {{ $report->isFinalized() ? 'bg-emerald-950/20 border-emerald-500/30 text-emerald-300' : 'bg-slate-900 border-slate-800 text-slate-300' }} flex items-center justify-between text-xs font-semibold backdrop-blur-xl">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full {{ $report->isFinalized() ? 'bg-emerald-400' : 'bg-amber-400 animate-pulse' }}"></span>
            <span>Lifecycle State: <strong>{{ str_replace('_', ' ', $report->status) }}</strong></span>
            @if($report->finalized_at)
                <span class="text-slate-400">• Finalized on {{ $report->finalized_at->format('d M Y H:i') }}</span>
            @endif
        </div>
        <span class="px-2.5 py-0.5 rounded-md bg-slate-950 border border-slate-800 text-slate-400 font-mono">
            Report Ref: REP-{{ $report->id }}-{{ $report->academic_year }}-T{{ substr($report->term, -1) }}
        </span>
    </div>

    <!-- Student Metadata Card -->
    <div class="bg-slate-900/90 rounded-2xl border border-slate-800 p-6 shadow-xl space-y-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 border-b border-slate-800 gap-2">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $school->name }}</span>
                <h2 class="text-lg font-black text-white">{{ $report->student->full_name }}</h2>
            </div>
            <div class="text-right">
                <span class="text-xs font-semibold text-slate-400">Admission No:</span>
                <span class="text-sm font-bold text-indigo-400 font-mono ml-1">{{ $report->student->admission_number }}</span>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-slate-500 font-semibold">Class / Stream:</span>
                <div class="font-bold text-slate-200 mt-0.5">{{ $report->schoolClass->name ?? $report->student->class_name ?? '—' }}</div>
            </div>
            <div>
                <span class="text-slate-500 font-semibold">Academic Level:</span>
                <div class="font-bold text-slate-200 mt-0.5">{{ $report->schoolClass->grade ?? $report->student->grade ?? '—' }}</div>
            </div>
            <div>
                <span class="text-slate-500 font-semibold">Period:</span>
                <div class="font-bold text-slate-200 mt-0.5">{{ $report->term }} • {{ $report->academic_year }}</div>
            </div>
            <div>
                <span class="text-slate-500 font-semibold">Grading Standard:</span>
                <div class="font-bold text-indigo-300 mt-0.5">{{ $report->gradeScheme->name ?? 'ZIMSEC O-Level Standard' }}</div>
            </div>
        </div>
    </div>

    <!-- Subject Performance Breakdown Table -->
    <div class="bg-slate-900/90 rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-base font-black text-white">Subject Academic Performance</h3>
            <span class="text-xs font-semibold text-slate-400">{{ $report->subjects->count() }} Subjects Enrolled</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/80 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800 font-bold">
                    <tr>
                        <th class="py-3.5 px-4">Subject</th>
                        <th class="py-3.5 px-3 text-center">Score</th>
                        <th class="py-3.5 px-3 text-center">Max</th>
                        <th class="py-3.5 px-3 text-center">%</th>
                        <th class="py-3.5 px-3 text-center">Grade</th>
                        <th class="py-3.5 px-3 text-center">Status</th>
                        <th class="py-3.5 px-4">Teacher & Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80 font-medium">
                    @forelse($report->subjects as $subjItem)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="py-3.5 px-4 font-bold text-white">
                                {{ $subjItem->subject->name }}
                            </td>
                            <td class="py-3.5 px-3 text-center font-mono font-bold text-slate-200">
                                {{ $subjItem->mark_obtained !== null ? $subjItem->mark_obtained : '—' }}
                            </td>
                            <td class="py-3.5 px-3 text-center font-mono text-slate-400">
                                {{ $subjItem->max_mark ?? 100 }}
                            </td>
                            <td class="py-3.5 px-3 text-center font-mono font-bold text-white">
                                {{ $subjItem->percentage !== null ? number_format($subjItem->percentage, 1) . '%' : '—' }}
                            </td>
                            <td class="py-3.5 px-3 text-center font-black {{ $subjItem->is_pass === false ? 'text-rose-500' : ($subjItem->is_pass ? 'text-emerald-400' : 'text-slate-400') }}">
                                {{ $subjItem->grade ?? '—' }}
                            </td>
                            <td class="py-3.5 px-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $subjItem->is_pass === false ? 'bg-rose-500/10 text-rose-400 border border-rose-500/30' : ($subjItem->is_pass ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400') }}">
                                    {{ $subjItem->is_pass === false ? 'FAIL' : ($subjItem->is_pass ? 'PASS' : '—') }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-xs">
                                <div class="text-[11px] font-semibold text-indigo-400">{{ $subjItem->effective_teacher_name }}</div>
                                <div class="text-slate-300 mt-1 italic" style="font-family: {{ $subjItem->comment_font ?? 'Arial' }}; font-size: {{ $subjItem->comment_font_size ?? 10 }}pt; {{ !empty($subjItem->comment_formatting['bold']) ? 'font-weight: bold;' : '' }} {{ !empty($subjItem->comment_formatting['italic']) ? 'font-style: italic;' : '' }}">
                                    {{ $subjItem->comment ?: 'No comment entered.' }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">No subject lines registered for this report.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Term Aggregates & Overall Standing Card -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-slate-900/90 rounded-2xl border border-slate-800 p-5 shadow-lg">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Term Average</span>
            <div class="text-3xl font-black text-white mt-1">
                {{ $report->term_average !== null ? number_format($report->term_average, 1) . '%' : '—' }}
            </div>
            <p class="text-[11px] text-slate-500 mt-1">Across all assessed subjects</p>
        </div>

        <div class="bg-slate-900/90 rounded-2xl border border-slate-800 p-5 shadow-lg">
            <span class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Overall Grade</span>
            <div class="text-3xl font-black text-indigo-300 mt-1">{{ $report->overall_grade ?? '—' }}</div>
            <p class="text-[11px] text-slate-500 mt-1">Standardized aggregate grade</p>
        </div>

        <div class="bg-slate-900/90 rounded-2xl border border-slate-800 p-5 shadow-lg">
            <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Passed / Failed</span>
            <div class="text-3xl font-black mt-1">
                <span class="text-emerald-400">{{ $report->subjects_passed }}</span>
                <span class="text-slate-600 text-xl">/</span>
                <span class="text-rose-400">{{ $report->subjects_failed }}</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">{{ $report->total_subjects }} total subjects evaluated</p>
        </div>

        <div class="bg-slate-900/90 rounded-2xl border border-slate-800 p-5 shadow-lg">
            <span class="text-xs font-bold text-purple-400 uppercase tracking-wider">Overall Status</span>
            <div class="text-base font-black text-purple-300 mt-2">{{ $report->overall_status ?? 'Pending' }}</div>
            <p class="text-[11px] text-slate-500 mt-1">Academic classification</p>
        </div>
    </div>

    <!-- School Leadership Comments & Digital Signatures -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Headmaster Section -->
        <div class="bg-slate-900/90 rounded-2xl border border-slate-800 p-6 shadow-xl space-y-4 flex flex-col justify-between">
            <div class="space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-sm font-black text-white uppercase tracking-wider">Headmaster's Evaluation</h3>
                    @if($report->isHeadmasterSigned())
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            ✓ Digitally Signed
                        </span>
                    @endif
                </div>

                @if(!$report->isFinalized() && auth()->user()->hasAnyRole(['super-admin', 'school-admin', 'headmaster']))
                    <form action="{{ route('admin.exams.performance-reports.save-comment', $report->id) }}" method="POST" class="space-y-3">
                        @csrf
                        <input type="hidden" name="type" value="headmaster">
                        <textarea name="comment" rows="3" placeholder="Enter Headmaster's executive comment on academic progress and conduct..." 
                                  class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl p-3 focus:ring-2 focus:ring-indigo-500">{{ $report->headmaster_comment }}</textarea>
                        <div class="text-right">
                            <button type="submit" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-200 transition border border-slate-700">
                                Save Headmaster Comment
                            </button>
                        </div>
                    </form>
                @else
                    <div class="p-3 bg-slate-950/60 rounded-xl border border-slate-800 text-xs text-slate-300 italic min-h-[60px]">
                        {{ $report->headmaster_comment ?: 'No Headmaster comment entered.' }}
                    </div>
                @endif
            </div>

            <!-- Headmaster Signature Block -->
            <div class="pt-4 border-t border-slate-800/80 flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-semibold text-slate-400">Headmaster Digital Signature</span>
                    @if($report->isHeadmasterSigned())
                        <div class="text-xs font-bold text-emerald-400 mt-0.5">{{ $report->headmasterUser->name ?? 'Headmaster' }}</div>
                        <div class="text-[10px] text-slate-500">{{ $report->headmaster_signed_at->format('d M Y H:i') }}</div>
                    @else
                        <div class="text-xs font-medium text-slate-500 mt-0.5">Signature pending</div>
                    @endif
                </div>

                @if(!$report->isFinalized() && !$report->isHeadmasterSigned() && auth()->user()->hasAnyRole(['super-admin', 'school-admin', 'headmaster']))
                    <form action="{{ route('admin.exams.performance-reports.sign', $report->id) }}" method="POST" onsubmit="return confirm('Confirm digital signature as Headmaster for this student report?')">
                        @csrf
                        <input type="hidden" name="role_type" value="headmaster">
                        <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-sm">
                            Sign Report ✍️
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Deputy Headmaster Section -->
        <div class="bg-slate-900/90 rounded-2xl border border-slate-800 p-6 shadow-xl space-y-4 flex flex-col justify-between">
            <div class="space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <h3 class="text-sm font-black text-white uppercase tracking-wider">Deputy Headmaster's Remarks</h3>
                    @if($report->isDeputySigned())
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            ✓ Digitally Signed
                        </span>
                    @endif
                </div>

                @if(!$report->isFinalized() && auth()->user()->hasAnyRole(['super-admin', 'school-admin', 'deputy-headmaster']))
                    <form action="{{ route('admin.exams.performance-reports.save-comment', $report->id) }}" method="POST" class="space-y-3">
                        @csrf
                        <input type="hidden" name="type" value="deputy">
                        <textarea name="comment" rows="3" placeholder="Enter Deputy Headmaster's remarks..." 
                                  class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-xs rounded-xl p-3 focus:ring-2 focus:ring-indigo-500">{{ $report->deputy_comment }}</textarea>
                        <div class="text-right">
                            <button type="submit" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-slate-200 transition border border-slate-700">
                                Save Deputy Comment
                            </button>
                        </div>
                    </form>
                @else
                    <div class="p-3 bg-slate-950/60 rounded-xl border border-slate-800 text-xs text-slate-300 italic min-h-[60px]">
                        {{ $report->deputy_comment ?: 'No Deputy Headmaster comment entered.' }}
                    </div>
                @endif
            </div>

            <!-- Deputy Signature Block -->
            <div class="pt-4 border-t border-slate-800/80 flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-semibold text-slate-400">Deputy Digital Signature</span>
                    @if($report->isDeputySigned())
                        <div class="text-xs font-bold text-emerald-400 mt-0.5">{{ $report->deputyUser->name ?? 'Deputy Headmaster' }}</div>
                        <div class="text-[10px] text-slate-500">{{ $report->deputy_signed_at->format('d M Y H:i') }}</div>
                    @else
                        <div class="text-xs font-medium text-slate-500 mt-0.5">Signature pending</div>
                    @endif
                </div>

                @if(!$report->isFinalized() && !$report->isDeputySigned() && auth()->user()->hasAnyRole(['super-admin', 'school-admin', 'deputy-headmaster']))
                    <form action="{{ route('admin.exams.performance-reports.sign', $report->id) }}" method="POST" onsubmit="return confirm('Confirm digital signature as Deputy Headmaster for this student report?')">
                        @csrf
                        <input type="hidden" name="role_type" value="deputy">
                        <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-sm">
                            Sign Report ✍️
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Official Stamp & Audit Trail Section -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Official School Stamp Card -->
        <div class="bg-slate-900/90 rounded-2xl border border-slate-800 p-6 shadow-xl flex flex-col justify-between">
            <div class="space-y-2">
                <h3 class="text-sm font-black text-white uppercase tracking-wider">Official Digital Stamp</h3>
                <p class="text-xs text-slate-400">Authenticates official release of this academic report.</p>

                <div class="py-4 text-center">
                    @if($report->isStampApplied())
                        <div class="inline-flex flex-col items-center p-3 rounded-2xl bg-purple-500/10 border border-purple-500/30 text-purple-300">
                            <div class="w-12 h-12 rounded-full border-2 border-dashed border-purple-400 flex items-center justify-center font-black text-xs">
                                STAMP
                            </div>
                            <span class="text-[10px] font-bold uppercase mt-2">{{ $school->name }}</span>
                            <span class="text-[9px] text-slate-400">{{ $report->stamp_applied_at->format('d M Y') }}</span>
                        </div>
                    @else
                        <div class="p-4 border-2 border-dashed border-slate-800 rounded-xl text-slate-600 text-xs font-medium">
                            Stamp Not Applied
                        </div>
                    @endif
                </div>
            </div>

            @if(!$report->isFinalized() && !$report->isStampApplied() && auth()->user()->hasAnyRole(['super-admin', 'school-admin', 'headmaster', 'deputy-headmaster']))
                <form action="{{ route('admin.exams.performance-reports.stamp', $report->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2 rounded-xl text-xs font-bold bg-purple-600 hover:bg-purple-500 text-white transition shadow-md">
                        Apply Official Stamp 🔏
                    </button>
                </form>
            @endif
        </div>

        <!-- Audit Trail Card -->
        <div class="md:col-span-2 bg-slate-900/90 rounded-2xl border border-slate-800 p-6 shadow-xl space-y-3">
            <h3 class="text-sm font-black text-white uppercase tracking-wider">Audit Trail History</h3>
            <div class="space-y-2 max-h-48 overflow-y-auto text-xs pr-2">
                @forelse($report->audits as $audit)
                    <div class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800 flex items-start justify-between gap-2">
                        <div>
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-slate-800 text-indigo-300 border border-slate-700">
                                {{ $audit->action }}
                            </span>
                            <p class="text-slate-300 mt-1 text-[11px]">{{ $audit->description }}</p>
                        </div>
                        <div class="text-right text-[10px] text-slate-500 whitespace-nowrap">
                            <div>{{ $audit->user->name ?? 'System' }}</div>
                            <div>{{ $audit->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-500">No audit events recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Reopen Modal -->
<div id="reopen-modal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <h3 class="text-base font-black text-white">Reopen Report for Amendment</h3>
            <button type="button" onclick="document.getElementById('reopen-modal').classList.add('hidden')" class="text-slate-400 hover:text-white font-bold">✕</button>
        </div>

        <form action="{{ route('admin.exams.performance-reports.reopen', $report->id) }}" method="POST" class="space-y-3">
            @csrf
            <p class="text-xs text-slate-400">Reopening this report will unlock subject sections, bump the report version from <strong>v{{ $report->version }}</strong> to <strong>v{{ number_format((float)$report->version + 1.0, 1) }}</strong>, and log an audit trail entry.</p>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Reason for Reopening *</label>
                <textarea name="reason" rows="3" required placeholder="State why this finalized report is being reopened for amendments..." class="w-full bg-slate-950 border border-slate-700 text-white text-xs rounded-xl p-3"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                <button type="button" onclick="document.getElementById('reopen-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 text-slate-300">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-500 text-white">Confirm & Reopen</button>
            </div>
        </form>
    </div>
</div>
@endsection
