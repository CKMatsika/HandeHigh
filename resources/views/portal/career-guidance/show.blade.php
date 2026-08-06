@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('teacher.career-guidance.index') }}" class="text-slate-400 hover:text-slate-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </a>
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Career Assessment</h1>
            <p class="text-xs text-slate-400">{{ $assessment->student?->first_name }} {{ $assessment->student?->last_name }} · {{ $assessment->assessment_date->format('d M Y') }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-700 bg-indigo-500/10 p-6">
        <h3 class="text-sm font-semibold text-indigo-400 mb-3">Overall Feedback</h3>
        <p class="text-sm text-slate-100 leading-relaxed">{{ $assessment->overall_feedback }}</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @if($strengths = $assessment->strengths)
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
                <h3 class="text-sm font-semibold text-slate-50 mb-4">Strengths</h3>
                <div class="space-y-2">
                    @foreach($strengths as $s)
                        <div class="py-2 px-3 rounded-lg bg-emerald-500/10 border border-emerald-500/20">
                            <p class="text-xs text-emerald-400">{{ $s['message'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($improvements = $assessment->areas_for_improvement)
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
                <h3 class="text-sm font-semibold text-slate-50 mb-4">Growth Areas</h3>
                <div class="space-y-2">
                    @foreach($improvements as $a)
                        <div class="py-2 px-3 rounded-lg bg-amber-500/10 border border-amber-500/20">
                            <p class="text-xs text-amber-400">{{ $a['message'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @if($tips = $assessment->improvement_tips)
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-sm font-semibold text-slate-50 mb-3">Improvement Tips for the Student</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($tips as $tip)
                    <div class="flex items-start gap-2 py-2 px-3 rounded-lg bg-slate-800/50">
                        <span class="text-emerald-400 text-xs mt-0.5">✦</span>
                        <p class="text-xs text-slate-200">{{ $tip }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($careers = $assessment->suggested_careers)
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
            <div class="p-4 border-b border-slate-800">
                <h3 class="text-sm font-semibold text-slate-50">Suggested Careers</h3>
            </div>
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($careers as $career)
                    <div class="rounded-lg border border-slate-700 bg-slate-800/50 p-4">
                        <h4 class="text-sm font-medium text-slate-50">{{ $career['name'] }}</h4>
                        @if($career['match_percentage'])
                            <span class="text-xs text-blue-400">{{ $career['match_percentage'] }}% match</span>
                        @endif
                        @if(!empty($career['description']))
                            <p class="text-xs text-slate-400 mt-1">{{ $career['description'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
