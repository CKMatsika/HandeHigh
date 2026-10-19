<!-- Enrollment Reports by Class, Gender, and Stream -->
<div class="rounded-2xl border border-slate-800 bg-slate-900/80 backdrop-blur-xl p-6 space-y-6 shadow-xl relative overflow-hidden" id="enrollment-reports-section">
    <!-- Subtle Ambient Glow -->
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Section Header & Source Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800/80 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </span>
                <h2 class="text-xl font-bold text-slate-50 tracking-tight">Enrollment Reports</h2>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20 font-medium">Class, Stream & Gender Breakdown</span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Real-time student distribution with source drill-down capabilities</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('admin.students.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-700 bg-slate-800/80 hover:bg-slate-700 text-slate-200 text-xs font-medium transition shadow-sm">
                <span>All Students</span>
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
            </a>
            <a href="{{ route('admin.enrollments.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-indigo-700/50 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 text-xs font-medium transition shadow-sm">
                <span>Enrollment Register</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Total Enrolled -->
        <div class="p-4 rounded-xl border border-slate-800 bg-slate-950/50 hover:border-slate-700 transition cursor-pointer group"
             onclick="LeadershipDrilldown.open('students', { status: 'active' }, 'Total Active Students', 'All currently active enrolled students', null, '{{ route('admin.students.index') }}')">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400 group-hover:text-blue-400 transition">Total Enrolled</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400 font-semibold border border-blue-500/20">Drill down ↗</span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-slate-50">{{ number_format($enrollmentReport['total_students']) }}</span>
                <span class="text-xs text-emerald-400 font-medium">{{ $enrollmentReport['active_students'] }} active</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">Across all registered forms & streams</p>
        </div>

        <!-- Gender Ratio -->
        <div class="p-4 rounded-xl border border-slate-800 bg-slate-950/50">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Gender Distribution</span>
                <span class="text-[10px] text-slate-500">Ratio</span>
            </div>
            <div class="mt-2 flex items-center justify-between">
                <button type="button" 
                        class="text-left hover:opacity-80 transition group"
                        onclick="LeadershipDrilldown.open('students', { gender: 'male' }, 'Male Students (Boys)', 'All enrolled male students', null, '{{ route('admin.students.index', ['gender' => 'male']) }}')">
                    <span class="text-sm font-bold text-blue-400 group-hover:underline">♂ {{ number_format($enrollmentReport['male_students']) }}</span>
                    <span class="text-[11px] text-slate-400 block">{{ $enrollmentReport['male_percentage'] }}% Boys</span>
                </button>
                <div class="h-6 w-px bg-slate-800"></div>
                <button type="button" 
                        class="text-right hover:opacity-80 transition group"
                        onclick="LeadershipDrilldown.open('students', { gender: 'female' }, 'Female Students (Girls)', 'All enrolled female students', null, '{{ route('admin.students.index', ['gender' => 'female']) }}')">
                    <span class="text-sm font-bold text-pink-400 group-hover:underline">♀ {{ number_format($enrollmentReport['female_students']) }}</span>
                    <span class="text-[11px] text-slate-400 block">{{ $enrollmentReport['female_percentage'] }}% Girls</span>
                </button>
            </div>
            <!-- Dual Visual Bar -->
            <div class="w-full bg-slate-800 rounded-full h-1.5 mt-2 overflow-hidden flex">
                <div class="bg-blue-500 h-1.5" style="width: {{ $enrollmentReport['male_percentage'] }}%"></div>
                <div class="bg-pink-500 h-1.5" style="width: {{ $enrollmentReport['female_percentage'] }}%"></div>
            </div>
        </div>

        <!-- Boarding vs Day Scholar -->
        <div class="p-4 rounded-xl border border-slate-800 bg-slate-950/50">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Residency Type</span>
                <span class="text-[10px] text-slate-500">Boarding / Day</span>
            </div>
            <div class="mt-2 flex items-center justify-between">
                <button type="button" 
                        class="text-left hover:opacity-80 transition group"
                        onclick="LeadershipDrilldown.open('students', { is_boarding: 1 }, 'Boarding Students', 'Students enrolled as boarders', null, '{{ route('admin.students.index', ['boarding' => 'yes']) }}')">
                    <span class="text-sm font-bold text-indigo-400 group-hover:underline">🏰 {{ number_format($enrollmentReport['boarders']) }}</span>
                    <span class="text-[11px] text-slate-400 block">Boarders</span>
                </button>
                <div class="h-6 w-px bg-slate-800"></div>
                <button type="button" 
                        class="text-right hover:opacity-80 transition group"
                        onclick="LeadershipDrilldown.open('students', { is_boarding: 0 }, 'Day Scholars', 'Students enrolled as day scholars', null, '{{ route('admin.students.index', ['boarding' => 'no']) }}')">
                    <span class="text-sm font-bold text-emerald-400 group-hover:underline">🚌 {{ number_format($enrollmentReport['day_scholars']) }}</span>
                    <span class="text-[11px] text-slate-400 block">Day Scholars</span>
                </button>
            </div>
            <div class="w-full bg-slate-800 rounded-full h-1.5 mt-2 overflow-hidden flex">
                <div class="bg-indigo-500 h-1.5" style="width: {{ $enrollmentReport['total_students'] > 0 ? round(($enrollmentReport['boarders'] / $enrollmentReport['total_students']) * 100) : 0 }}%"></div>
                <div class="bg-emerald-500 h-1.5" style="width: {{ $enrollmentReport['total_students'] > 0 ? round(($enrollmentReport['day_scholars'] / $enrollmentReport['total_students']) * 100) : 0 }}%"></div>
            </div>
        </div>

        <!-- Streams Aggregate -->
        <div class="p-4 rounded-xl border border-slate-800 bg-slate-950/50">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Streams Aggregate</span>
                <span class="text-[10px] text-slate-500">School-wide</span>
            </div>
            <div class="mt-2 flex items-center gap-1.5 flex-wrap">
                @foreach($enrollmentReport['stream_aggregates'] as $stAgg)
                    <button type="button" 
                            class="px-2 py-1 rounded bg-slate-800/80 hover:bg-slate-700 border border-slate-700/60 text-[11px] text-slate-300 font-medium transition"
                            onclick="LeadershipDrilldown.open('students', { stream: '{{ $stAgg['stream'] }}' }, '{{ $stAgg['stream'] }} Stream Students', 'All students across grades in {{ $stAgg['stream'] }} stream', null, '{{ route('admin.students.index', ['stream' => $stAgg['stream']]) }}')">
                        <span class="text-slate-400">{{ $stAgg['stream'] }}:</span>
                        <span class="font-bold text-slate-100">{{ $stAgg['total'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Grade Filter Tabs -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 border-b border-slate-800/60 text-xs">
        <span class="text-slate-400 font-medium shrink-0">Filter Class:</span>
        <button type="button" onclick="filterGradeTable('all')" class="grade-tab-btn active px-3 py-1 rounded-lg bg-indigo-600 text-white font-semibold transition" data-grade="all">
            All Grades ({{ count($enrollmentReport['by_grade']) }})
        </button>
        @foreach($enrollmentReport['by_grade'] as $gItem)
            <button type="button" onclick="filterGradeTable('{{ Str::slug($gItem['grade']) }}')" class="grade-tab-btn px-3 py-1 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-300 font-medium transition shrink-0" data-grade="{{ Str::slug($gItem['grade']) }}">
                {{ $gItem['grade'] }} ({{ $gItem['total'] }})
            </button>
        @endforeach
    </div>

    <!-- Breakdown Table by Grade and Stream -->
    <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-950/40">
        <table class="w-full text-left text-xs text-slate-300">
            <thead class="text-slate-400 bg-slate-950/80 uppercase text-[10px] tracking-wider border-b border-slate-800">
                <tr>
                    <th class="px-4 py-3.5">Grade / Stream</th>
                    <th class="px-4 py-3.5">Class Teacher</th>
                    <th class="px-4 py-3.5 text-center">Boys (♂)</th>
                    <th class="px-4 py-3.5 text-center">Girls (♀)</th>
                    <th class="px-4 py-3.5 text-center">Boarders</th>
                    <th class="px-4 py-3.5 text-center">Day</th>
                    <th class="px-4 py-3.5 text-center font-bold text-slate-200">Total Enrolled</th>
                    <th class="px-4 py-3.5 text-right">Drill Down</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60" id="enrollment-report-tbody">
                @foreach($enrollmentReport['by_grade'] as $gradeData)
                    <!-- Grade Summary Header Row -->
                    <tr class="bg-slate-850/90 font-semibold border-t-2 border-slate-700/60 grade-row grade-{{ Str::slug($gradeData['grade']) }}">
                        <td class="px-4 py-3 text-slate-100 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-400"></span>
                            <span class="text-sm font-bold text-indigo-200">{{ $gradeData['grade'] }}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 font-normal">All Streams</span>
                        </td>
                        <td class="px-4 py-3 text-slate-400 text-xs italic">Grade Total</td>
                        <td class="px-4 py-3 text-center">
                            <button type="button" 
                                    class="text-blue-400 font-bold hover:underline"
                                    onclick="LeadershipDrilldown.open('students', { grade: '{{ $gradeData['grade'] }}', gender: 'male' }, '{{ $gradeData['grade'] }} Boys', 'All male students in {{ $gradeData['grade'] }}', null, '{{ route('admin.students.index', ['grade' => $gradeData['grade'], 'gender' => 'male']) }}')">
                                {{ $gradeData['male'] }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button type="button" 
                                    class="text-pink-400 font-bold hover:underline"
                                    onclick="LeadershipDrilldown.open('students', { grade: '{{ $gradeData['grade'] }}', gender: 'female' }, '{{ $gradeData['grade'] }} Girls', 'All female students in {{ $gradeData['grade'] }}', null, '{{ route('admin.students.index', ['grade' => $gradeData['grade'], 'gender' => 'female']) }}')">
                                {{ $gradeData['female'] }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-center text-slate-300 font-semibold">{{ $gradeData['boarders'] }}</td>
                        <td class="px-4 py-3 text-center text-slate-300 font-semibold">{{ $gradeData['day_scholars'] }}</td>
                        <td class="px-4 py-3 text-center font-black text-slate-50 text-sm">
                            <span class="px-2.5 py-1 rounded-lg bg-indigo-500/20 text-indigo-200 border border-indigo-500/30">
                                {{ $gradeData['total'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" 
                                        onclick="LeadershipDrilldown.open('students', { grade: '{{ $gradeData['grade'] }}' }, '{{ $gradeData['grade'] }} Students', 'Complete student roster for {{ $gradeData['grade'] }}', null, '{{ route('admin.students.index', ['grade' => $gradeData['grade']]) }}')"
                                        class="px-2.5 py-1 text-[11px] rounded bg-indigo-600/30 hover:bg-indigo-600/50 text-indigo-200 font-medium transition border border-indigo-500/30">
                                    Inspect Roster
                                </button>
                                <a href="{{ route('admin.students.index', ['grade' => $gradeData['grade']]) }}" 
                                   title="Go to full students page"
                                   class="p-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-slate-200 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- Stream Detail Rows -->
                    @foreach($gradeData['streams'] as $st)
                        <tr class="hover:bg-slate-850/40 transition grade-row grade-{{ Str::slug($gradeData['grade']) }}">
                            <td class="px-4 py-2.5 pl-8 text-slate-200 flex items-center gap-2">
                                <span class="text-slate-500">↳</span>
                                <span class="font-semibold text-slate-200">{{ $gradeData['grade'] }} {{ $st['stream'] }}</span>
                                <span class="text-[10px] text-slate-400">({{ $st['stream'] }})</span>
                            </td>
                            <td class="px-4 py-2.5 text-slate-400">
                                @if($st['teacher_name'])
                                    <span class="inline-flex items-center gap-1.5 text-slate-300">
                                        <svg class="w-3 h-3 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        {{ $st['teacher_name'] }}
                                    </span>
                                @else
                                    <span class="text-slate-500 italic text-[11px]">Unassigned</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <button type="button" 
                                        class="px-2 py-0.5 rounded bg-blue-500/10 text-blue-400 font-bold hover:bg-blue-500/20 transition text-xs"
                                        onclick="LeadershipDrilldown.open('students', { grade: '{{ $gradeData['grade'] }}', stream: '{{ $st['stream'] }}', gender: 'male' }, '{{ $gradeData['grade'] }} {{ $st['stream'] }} - Boys', 'Male students in {{ $gradeData['grade'] }} {{ $st['stream'] }}', null, '{{ route('admin.students.index', ['grade' => $gradeData['grade'], 'stream' => $st['stream'], 'gender' => 'male']) }}')">
                                    {{ $st['male'] }}
                                </button>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <button type="button" 
                                        class="px-2 py-0.5 rounded bg-pink-500/10 text-pink-400 font-bold hover:bg-pink-500/20 transition text-xs"
                                        onclick="LeadershipDrilldown.open('students', { grade: '{{ $gradeData['grade'] }}', stream: '{{ $st['stream'] }}', gender: 'female' }, '{{ $gradeData['grade'] }} {{ $st['stream'] }} - Girls', 'Female students in {{ $gradeData['grade'] }} {{ $st['stream'] }}', null, '{{ route('admin.students.index', ['grade' => $gradeData['grade'], 'stream' => $st['stream'], 'gender' => 'female']) }}')">
                                    {{ $st['female'] }}
                                </button>
                            </td>
                            <td class="px-4 py-2.5 text-center text-slate-400">{{ $st['boarders'] }}</td>
                            <td class="px-4 py-2.5 text-center text-slate-400">{{ $st['day_scholars'] }}</td>
                            <td class="px-4 py-2.5 text-center font-bold text-slate-100">
                                {{ $st['total'] }}
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" 
                                            onclick='LeadershipDrilldown.open("students", { grade: "{{ $gradeData['grade'] }}", stream: "{{ $st['stream'] }}" }, "{{ $gradeData['grade'] }} {{ $st['stream'] }} Students", "Students enrolled in {{ $gradeData['grade'] }} {{ $st['stream'] }}", @json($st['students']), "{{ route('admin.students.index', ['grade' => $gradeData['grade'], 'stream' => $st['stream']]) }}")'
                                            class="px-2 py-1 text-[11px] rounded bg-slate-800 hover:bg-slate-700 text-cyan-400 font-medium transition border border-slate-700">
                                        View Stream
                                    </button>
                                    <a href="{{ route('admin.students.index', ['grade' => $gradeData['grade'], 'stream' => $st['stream']]) }}" 
                                       title="Open filtered source page"
                                       class="p-1 rounded bg-slate-800/80 hover:bg-slate-700 text-slate-400 hover:text-slate-200 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
function filterGradeTable(gradeSlug) {
    // Update active tab buttons
    document.querySelectorAll('.grade-tab-btn').forEach(btn => {
        if (btn.getAttribute('data-grade') === gradeSlug) {
            btn.classList.add('bg-indigo-600', 'text-white', 'font-semibold');
            btn.classList.remove('bg-slate-800/80', 'text-slate-300');
        } else {
            btn.classList.remove('bg-indigo-600', 'text-white', 'font-semibold');
            btn.classList.add('bg-slate-800/80', 'text-slate-300');
        }
    });

    const rows = document.querySelectorAll('#enrollment-report-tbody tr.grade-row');
    rows.forEach(row => {
        if (gradeSlug === 'all') {
            row.style.display = '';
        } else {
            if (row.classList.contains(`grade-${gradeSlug}`)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
}
</script>
