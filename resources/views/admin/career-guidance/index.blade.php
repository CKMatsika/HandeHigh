@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Career Guidance</h1>
            <p class="text-xs text-slate-400 mt-1">AI-powered student career assessments and guidance.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.career-guidance.students') }}" class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                Assess Students
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <p class="text-xs text-slate-400">Assessments Done</p>
            <p class="text-2xl font-bold text-slate-50 mt-1">{{ $assessedCount }}</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <p class="text-xs text-slate-400">Career Paths</p>
            <p class="text-2xl font-bold text-slate-50 mt-1">{{ $pathCount }}</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <p class="text-xs text-slate-400">This Month</p>
            <p class="text-2xl font-bold text-slate-50 mt-1">{{ $recentCount }}</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <p class="text-xs text-slate-400">Active Paths</p>
            <p class="text-2xl font-bold text-slate-50 mt-1">{{ \App\Models\CareerPath::where('school_id', $school->id)->where('is_active', true)->count() }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="{{ route('admin.career-guidance.paths') }}" class="rounded-xl border border-slate-800 bg-slate-900/80 p-5 hover:bg-slate-800/80 transition">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-slate-50">Career Paths</h3>
                    <p class="text-xs text-slate-400">Define career paths with subject requirements</p>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.career-guidance.interests') }}" class="rounded-xl border border-slate-800 bg-slate-900/80 p-5 hover:bg-slate-800/80 transition">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-slate-50">Student Interests</h3>
                    <p class="text-xs text-slate-400">View what careers students are interested in</p>
                </div>
            </div>
        </a>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="p-4 border-b border-slate-800">
            <h3 class="text-sm font-semibold text-slate-50">Recent Assessments</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Strengths</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Careers Suggested</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($assessments as $assessment)
                        <tr class="hover:bg-slate-800/50">
                            <td class="px-4 py-3">
                                <p class="text-sm text-slate-50">{{ $assessment->student?->first_name ?? 'N/A' }} {{ $assessment->student?->last_name ?? '' }}</p>
                                <p class="text-[10px] text-slate-400">{{ $assessment->student?->grade ?? '' }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">{{ $assessment->assessment_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-xs">
                                @php $strengths = $assessment->strengths ?? []; @endphp
                                @foreach(array_slice($strengths, 0, 2) as $s)
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium bg-emerald-500/20 text-emerald-400 mr-1">{{ $s['subject'] ?? '' }}</span>
                                @endforeach
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @php $careers = $assessment->suggested_careers ?? []; @endphp
                                @foreach(array_slice($careers, 0, 2) as $c)
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium bg-indigo-500/20 text-indigo-400 mr-1">{{ $c['name'] ?? '' }}</span>
                                @endforeach
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                    {{ $assessment->status === 'published' ? 'bg-emerald-500/20 text-emerald-400' : '' }}
                                    {{ $assessment->status === 'draft' ? 'bg-amber-500/20 text-amber-400' : '' }}">
                                    {{ ucfirst($assessment->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <a href="{{ route('admin.career-guidance.show', $assessment) }}" class="text-emerald-400 hover:text-emerald-300">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-slate-400"><p class="text-sm">No assessments yet. <a href="{{ route('admin.career-guidance.students') }}" class="text-emerald-400 hover:text-emerald-300">Assess a student</a></p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($assessments->hasPages())<div class="px-4 py-3 border-t border-slate-800">{{ $assessments->links() }}</div>@endif
    </div>
</div>
@endsection
