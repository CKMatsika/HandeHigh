@extends('layouts.app')

@section('content')
    {{-- Breadcrumb & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <nav class="flex items-center gap-2 text-xs text-slate-400 mb-2">
                <a href="{{ route('teacher.dashboard') }}" class="hover:text-cyan-400 transition">&larr; Back to Dashboard</a>
                <span>/</span>
                <span class="text-slate-300">Class Roster</span>
                <span>/</span>
                <span class="text-cyan-400 font-medium">{{ $class->name }}</span>
            </nav>
            <h1 class="text-xl font-bold text-slate-50 flex items-center gap-3">
                <span>{{ $class->name }}</span>
                @if($class->grade)
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 font-medium">
                        Grade {{ $class->grade }}
                    </span>
                @endif
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-300 border border-slate-700">
                    {{ $students->count() }} Students
                </span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Academic Year: {{ $class->academic_year ?? date('Y') }} · {{ $class->term ?? 'All Terms' }}
                @if($class->teacher)
                    · Class Teacher: <span class="text-slate-200 font-medium">{{ $class->teacher->name }}</span>
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 print:hidden">
            <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition shadow-sm">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print Roster
            </button>
            <a href="{{ route('admin.exams.performance-reports.my-subjects') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white transition shadow-lg shadow-cyan-600/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Enter Student Marks
            </a>
            <a href="{{ route('teacher.timetable.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition">
                My Timetable
            </a>
        </div>
    </div>

    {{-- Subjects Taught in this Class --}}
    @if($teacherCurricula->isNotEmpty())
        <div class="mb-6 rounded-2xl border border-cyan-500/20 bg-gradient-to-r from-cyan-950/30 via-slate-900 to-slate-900/90 p-4">
            <div class="flex items-center gap-2 mb-2">
                <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                <span class="text-xs font-semibold uppercase tracking-wider text-cyan-300">Your Teaching Allocations in {{ $class->name }}</span>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($teacherCurricula as $curr)
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-800/80 border border-slate-700/80 text-xs">
                        <span class="font-bold text-cyan-300">{{ $curr->subject->name ?? 'Subject' }}</span>
                        <span class="text-slate-400">· {{ $curr->weekly_periods }} periods/wk</span>
                        <span class="text-slate-500">· {{ $curr->term ?? 'All Terms' }} ({{ $curr->academic_year }})</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Student Roster Card --}}
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-3 print:hidden">
            <div>
                <h2 class="text-sm font-semibold text-slate-100 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    Class Register & Enrolled Students
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Showing all students registered under {{ $class->name }}</p>
            </div>

            {{-- Live Search Input --}}
            <div class="relative w-full md:w-72">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" id="rosterSearchInput" onkeyup="filterRosterTable()" placeholder="Search student name or admission..."
                    class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-800/80 border border-slate-700 text-slate-200 placeholder-slate-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500">
            </div>
        </div>

        {{-- Printable Header (only visible on print) --}}
        <div class="hidden print:block p-6 border-b border-slate-300 text-slate-900">
            <h1 class="text-xl font-bold text-center">{{ $school->name }}</h1>
            <h2 class="text-base font-semibold text-center mt-1">CLASS REGISTER / ROSTER: {{ strtoupper($class->name) }}</h2>
            <p class="text-xs text-center text-slate-600 mt-1">
                Grade: {{ $class->grade }} · Year: {{ $class->academic_year ?? date('Y') }} · Generated on {{ now()->format('d M Y') }}
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs" id="rosterTable">
                <thead class="bg-slate-950/60 border-b border-slate-800 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">#</th>
                        <th class="py-3 px-4">Admission / Reg No</th>
                        <th class="py-3 px-4">Student Full Name</th>
                        <th class="py-3 px-4">Gender</th>
                        <th class="py-3 px-4">Residency</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right print:hidden">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-200">
                    @forelse($students as $index => $student)
                        <tr class="hover:bg-slate-800/40 transition roster-row"
                            data-search="{{ strtolower($student->first_name . ' ' . $student->last_name . ' ' . ($student->admission_number ?? '') . ' ' . ($student->registration_number ?? '')) }}">
                            <td class="py-3 px-4 text-center text-slate-500 font-mono text-[11px]">
                                {{ $index + 1 }}
                            </td>
                            <td class="py-3 px-4 font-mono font-medium text-slate-300">
                                {{ $student->admission_number ?? $student->registration_number ?? '—' }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-100">
                                    {{ $student->first_name }} {{ $student->last_name }}
                                </div>
                                @if($student->other_names)
                                    <div class="text-[10px] text-slate-400">{{ $student->other_names }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4 capitalize text-slate-300">
                                {{ $student->gender ?? '—' }}
                            </td>
                            <td class="py-3 px-4">
                                @if($student->is_boarding)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                        Boarding
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-800 text-slate-300 border border-slate-700">
                                        Day Scholar
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium capitalize {{ 
                                    $student->status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400' 
                                }}">
                                    {{ $student->status }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right print:hidden">
                                <a href="{{ route('admin.exams.performance-reports.my-subjects') }}" class="inline-flex items-center gap-1 text-[11px] font-medium text-cyan-400 hover:text-cyan-300 transition">
                                    Marks &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="max-w-sm mx-auto">
                                    <svg class="w-10 h-10 mx-auto text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    <p class="text-sm font-semibold text-slate-300">No students found in {{ $class->name }}</p>
                                    <p class="text-xs text-slate-500 mt-1">Students enrolled in this class or matching "{{ $class->name }}" will appear here.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-3 border-t border-slate-800 bg-slate-950/40 flex items-center justify-between text-xs text-slate-400">
            <span>Total Students: <strong class="text-slate-200">{{ $students->count() }}</strong></span>
            <span class="print:hidden">Tip: Click "Print Roster" to export an official paper register</span>
        </div>
    </div>

    <script>
        function filterRosterTable() {
            const query = document.getElementById('rosterSearchInput').value.toLowerCase().trim();
            const rows = document.querySelectorAll('.roster-row');
            rows.forEach(row => {
                const text = row.getAttribute('data-search') || '';
                if (!query || text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
@endsection
