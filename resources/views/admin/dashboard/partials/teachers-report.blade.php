<!-- Teachers Reports Component -->
<div class="rounded-2xl border border-slate-800 bg-slate-900/80 backdrop-blur-xl p-6 space-y-6 shadow-xl relative overflow-hidden" id="teachers-reports-section">
    <!-- Subtle Ambient Glow -->
    <div class="absolute -top-24 -right-24 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Section Header & Source Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800/80 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </span>
                <h2 class="text-xl font-bold text-slate-50 tracking-tight">Teachers Reports</h2>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-medium">Academic Staff & Allocations</span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Teaching staff profile, specializations, qualifications, and scheme submission tracking</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.teachers.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-700 bg-slate-800/80 hover:bg-slate-700 text-slate-200 text-xs font-medium transition shadow-sm">
                <span>View Full Teachers Directory</span>
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
            </a>
            <a href="{{ route('admin.schemes-of-work.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-cyan-700/50 bg-cyan-600/20 hover:bg-cyan-600/30 text-cyan-300 text-xs font-medium transition shadow-sm">
                <span>Schemes of Work</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Total Teachers -->
        <div class="p-4 rounded-xl border border-slate-800 bg-slate-950/50 hover:border-slate-700 transition cursor-pointer group"
             onclick="LeadershipDrilldown.open('teachers', { status: 1 }, 'Active Teachers', 'All currently active teaching staff', null, '{{ route('admin.teachers.index', ['status' => 'active']) }}')">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400 group-hover:text-emerald-400 transition">Total Teachers</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 font-semibold border border-emerald-500/20">Drill down ↗</span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-slate-50">{{ number_format($teachersReport['total_teachers']) }}</span>
                <span class="text-xs text-emerald-400 font-medium">{{ $teachersReport['active_teachers'] }} Active</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">{{ $teachersReport['inactive_teachers'] }} Inactive or on leave</p>
        </div>

        <!-- Gender Split -->
        <div class="p-4 rounded-xl border border-slate-800 bg-slate-950/50">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Gender Ratio</span>
                <span class="text-[10px] text-slate-500">Staff Split</span>
            </div>
            <div class="mt-2 flex items-center justify-between">
                <button type="button" 
                        class="text-left hover:opacity-80 transition group"
                        onclick="LeadershipDrilldown.open('teachers', { gender: 'male' }, 'Male Teachers', 'Teaching staff (male)', null, '{{ route('admin.teachers.index', ['gender' => 'male']) }}')">
                    <span class="text-sm font-bold text-blue-400 group-hover:underline">♂ {{ number_format($teachersReport['male_teachers']) }}</span>
                    <span class="text-[11px] text-slate-400 block">Male</span>
                </button>
                <div class="h-6 w-px bg-slate-800"></div>
                <button type="button" 
                        class="text-right hover:opacity-80 transition group"
                        onclick="LeadershipDrilldown.open('teachers', { gender: 'female' }, 'Female Teachers', 'Teaching staff (female)', null, '{{ route('admin.teachers.index', ['gender' => 'female']) }}')">
                    <span class="text-sm font-bold text-pink-400 group-hover:underline">♀ {{ number_format($teachersReport['female_teachers']) }}</span>
                    <span class="text-[11px] text-slate-400 block">Female</span>
                </button>
            </div>
            @php
                $totT = max(1, $teachersReport['total_teachers']);
                $malePct = round(($teachersReport['male_teachers'] / $totT) * 100);
                $femalePct = round(($teachersReport['female_teachers'] / $totT) * 100);
            @endphp
            <div class="w-full bg-slate-800 rounded-full h-1.5 mt-2 overflow-hidden flex">
                <div class="bg-blue-500 h-1.5" style="width: {{ $malePct }}%"></div>
                <div class="bg-pink-500 h-1.5" style="width: {{ $femalePct }}%"></div>
            </div>
        </div>

        <!-- Class Allocation / Class Teachers -->
        <div class="p-4 rounded-xl border border-slate-800 bg-slate-950/50">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Class Teachers</span>
                <span class="text-[10px] text-slate-500">Assignments</span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-purple-400">{{ $teachersReport['class_teachers_count'] }}</span>
                <span class="text-xs text-slate-400">Classes assigned</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">Homeroom / form tutors appointed</p>
        </div>

        <!-- Schemes of Work Submission -->
        <div class="p-4 rounded-xl border border-slate-800 bg-slate-950/50">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-400">Schemes of Work</span>
                <span class="text-[10px] text-cyan-400">Term Progress</span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-cyan-400">{{ $teachersReport['schemes_stats']['approved'] }}</span>
                <span class="text-xs text-slate-400">of {{ $teachersReport['schemes_stats']['total'] }} Approved</span>
            </div>
            <p class="text-[11px] text-amber-400 mt-1">{{ $teachersReport['schemes_stats']['pending'] }} Pending review</p>
        </div>
    </div>

    <!-- Specialization & Qualification Breakdowns -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- By Specialization -->
        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-slate-200">Teachers by Specialization</h3>
                <span class="text-xs text-slate-500">{{ count($teachersReport['by_specialization']) }} Subject Areas</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                @forelse($teachersReport['by_specialization'] as $specItem)
                    <div class="p-3 rounded-lg border border-slate-800/80 bg-slate-900/60 hover:bg-slate-850 hover:border-slate-700 transition flex items-center justify-between cursor-pointer group"
                         onclick='LeadershipDrilldown.open("teachers", { specialization: "{{ $specItem['name'] }}" }, "{{ $specItem['name'] }} Teachers", "Teachers specializing in {{ $specItem['name'] }}", @json($specItem['teachers']), "{{ route('admin.teachers.index', ['specialization' => $specItem['name']]) }}")'>
                        <div>
                            <p class="text-xs font-semibold text-slate-200 group-hover:text-emerald-400 transition">{{ $specItem['name'] }}</p>
                            <p class="text-[10px] text-slate-500">{{ $specItem['active_count'] }} active</p>
                        </div>
                        <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">
                            {{ $specItem['count'] }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 italic col-span-2 py-4 text-center">No specialization records configured</p>
                @endforelse
            </div>
        </div>

        <!-- By Qualification -->
        <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-sm font-bold text-slate-200">Teachers by Qualification</h3>
                <span class="text-xs text-slate-500">{{ count($teachersReport['by_qualification']) }} Levels</span>
            </div>
            <div class="space-y-2.5">
                @forelse($teachersReport['by_qualification'] as $qualItem)
                    <div class="p-3 rounded-lg border border-slate-800/80 bg-slate-900/60 hover:bg-slate-850 hover:border-slate-700 transition flex items-center justify-between cursor-pointer group"
                         onclick='LeadershipDrilldown.open("teachers", { qualification: "{{ $qualItem['name'] }}" }, "Teachers: {{ $qualItem['name'] }}", "Teachers holding {{ $qualItem['name'] }}", @json($qualItem['teachers']), "{{ route('admin.teachers.index', ['qualification' => $qualItem['name']]) }}")'>
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                            <div>
                                <p class="text-xs font-semibold text-slate-200 group-hover:text-cyan-400 transition">{{ $qualItem['name'] }}</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-cyan-500/10 text-cyan-300 border border-cyan-500/20">
                            {{ $qualItem['count'] }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 italic py-4 text-center">No qualifications recorded</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Searchable Teachers Roster Table -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-200">Teaching Staff Directory</h3>
            <span class="text-xs text-slate-400">Click any teacher to view full profile & assignments</span>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-950/40">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="text-slate-400 bg-slate-950/80 uppercase text-[10px] tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Teacher Name</th>
                        <th class="px-4 py-3">Employee ID</th>
                        <th class="px-4 py-3">Specialization</th>
                        <th class="px-4 py-3">Qualification</th>
                        <th class="px-4 py-3 text-center">Classes</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($teachersReport['roster'] as $tItem)
                        <tr class="hover:bg-slate-850/40 transition">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-xs">
                                        {{ substr($tItem['name'], 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-100">{{ $tItem['name'] }}</p>
                                        <p class="text-[10px] text-slate-400">{{ $tItem['email'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-400">{{ $tItem['employee_id'] }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-200 font-medium">
                                    {{ $tItem['specialization'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-400">{{ $tItem['qualification'] }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300">
                                    {{ $tItem['classes_count'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($tItem['status'] === 'Active')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/20 text-emerald-400">Active</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-800 text-slate-400">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ $tItem['view_url'] }}" class="inline-flex items-center gap-1 text-cyan-400 hover:text-cyan-300 font-medium">
                                    <span>Profile</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500 italic">No teachers records found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
