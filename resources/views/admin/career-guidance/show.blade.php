@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.career-guidance.index') }}" class="text-slate-400 hover:text-slate-300">
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
            <h3 class="text-sm font-semibold text-slate-50 mb-4">Subject Performance</h3>
            @if($subjects = $assessment->subject_performance)
                <div class="space-y-3">
                    @foreach($subjects as $subj)
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-slate-100">{{ $subj['subject_name'] }}</span>
                                <span class="text-xs font-medium
                                    {{ $subj['tier'] === 'excellent' ? 'text-emerald-400' : '' }}
                                    {{ $subj['tier'] === 'good' ? 'text-blue-400' : '' }}
                                    {{ $subj['tier'] === 'fair' ? 'text-amber-400' : '' }}
                                    {{ $subj['tier'] === 'needs_improvement' ? 'text-red-400' : '' }}">
                                    {{ $subj['average_score'] }}%
                                </span>
                            </div>
                            <div class="w-full h-2 bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all
                                    {{ $subj['tier'] === 'excellent' ? 'bg-emerald-500 w-4/5' : '' }}
                                    {{ $subj['tier'] === 'good' ? 'bg-blue-500 w-3/5' : '' }}
                                    {{ $subj['tier'] === 'fair' ? 'bg-amber-500 w-2/5' : '' }}
                                    {{ $subj['tier'] === 'needs_improvement' ? 'bg-red-500 w-1/5' : '' }}">
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1 italic">"{{ $subj['feedback'] }}"</p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-slate-400">No performance data.</p>
            @endif
        </div>

        <div class="space-y-6">
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
                <h3 class="text-sm font-semibold text-slate-50 mb-4">Strengths</h3>
                @if($strengths = $assessment->strengths)
                    <div class="space-y-2">
                        @foreach($strengths as $s)
                            <div class="flex items-center gap-2 py-2 px-3 rounded-lg bg-emerald-500/10 border border-emerald-500/20">
                                <span class="text-emerald-400 text-xs">{{ $s['message'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-400">No strengths data.</p>
                @endif
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
                <h3 class="text-sm font-semibold text-slate-50 mb-4">Growth Areas</h3>
                @if($improvements = $assessment->areas_for_improvement)
                    <div class="space-y-2">
                        @foreach($improvements as $a)
                            <div class="py-2 px-3 rounded-lg bg-amber-500/10 border border-amber-500/20">
                                <p class="text-xs text-amber-400">{{ $a['message'] }}</p>
                                @if(!empty($a['tips']))
                                    <ul class="mt-1 space-y-0.5">
                                        @foreach($a['tips'] as $tip)
                                            <li class="text-[10px] text-slate-400">• {{ $tip }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-400">No areas for improvement.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="p-4 border-b border-slate-800">
            <h3 class="text-sm font-semibold text-slate-50">Suggested Careers</h3>
        </div>
        @if($careers = $assessment->suggested_careers)
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($careers as $career)
                    <div class="rounded-lg border border-slate-700 bg-slate-800/50 p-4 {{ ($career['is_student_choice'] ?? false) ? 'border-indigo-500/40 bg-indigo-500/10' : '' }}">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-medium text-slate-50">{{ $career['name'] }}</h4>
                            @if($career['match_percentage'])
                                <span class="text-xs font-medium
                                    {{ $career['match_percentage'] >= 80 ? 'text-emerald-400' : '' }}
                                    {{ $career['match_percentage'] >= 60 && $career['match_percentage'] < 80 ? 'text-blue-400' : '' }}
                                    {{ $career['match_percentage'] < 60 ? 'text-amber-400' : '' }}">
                                    {{ $career['match_percentage'] }}% match
                                </span>
                            @endif
                            @if($career['is_student_choice'] ?? false)
                                <span class="text-[10px] text-indigo-400 font-medium">Student's Choice</span>
                            @endif
                        </div>
                        @if(!empty($career['description']))
                            <p class="text-xs text-slate-400 mb-2">{{ $career['description'] }}</p>
                        @endif
                        @if(!empty($career['details']))
                            <div class="space-y-1 mt-2">
                                @foreach($career['details'] as $d)
                                    <div class="flex items-center justify-between text-[10px]">
                                        <span class="text-slate-400">{{ $d['subject'] }}</span>
                                        <span class="{{ $d['met'] ? 'text-emerald-400' : 'text-red-400' }}">
                                            {{ $d['achieved'] }}% / {{ $d['required'] }}%
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if(!empty($career['skills']))
                            <p class="text-[10px] text-slate-500 mt-2">Skills: {{ $career['skills'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-6 text-center text-slate-400"><p class="text-xs">No career suggestions available.</p></div>
        @endif
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6">
        <h3 class="text-sm font-semibold text-slate-50 mb-3">Improvement Tips</h3>
        @if($tips = $assessment->improvement_tips)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($tips as $tip)
                    <div class="flex items-start gap-2 py-2 px-3 rounded-lg bg-slate-800/50">
                        <span class="text-emerald-400 text-xs mt-0.5">✦</span>
                        <p class="text-xs text-slate-200">{{ $tip }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-xs text-slate-400">No tips available.</p>
        @endif
    </div>
</div>
@endsection
