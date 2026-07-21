@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Curriculum Details</h1>
            <p class="text-xs text-slate-400 mt-1">View curriculum assignment and related exams.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.curricula.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back</a>
            <a href="{{ route('admin.curricula.edit', $curriculum) }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Edit</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-200">
            <div><span class="text-slate-400">Class:</span> {{ $curriculum->class->name }} ({{ $curriculum->class->grade }})</div>
            <div><span class="text-slate-400">Subject:</span> {{ $curriculum->subject->name }}</div>
            <div><span class="text-slate-400">Teacher:</span> {{ $curriculum->teacher?->name ?? '-' }}</div>
            <div><span class="text-slate-400">Academic Year:</span> {{ $curriculum->academic_year }}</div>
            <div><span class="text-slate-400">Term:</span> {{ $curriculum->term ?? '-' }}</div>
            <div><span class="text-slate-400">Weekly Periods:</span> {{ $curriculum->weekly_periods }}</div>
        </div>
        @if($curriculum->objectives || $curriculum->materials)
            <div class="mt-4 space-y-2">
                @if($curriculum->objectives)
                    <div>
                        <span class="text-[11px] font-medium text-slate-300">Objectives:</span>
                        <p class="text-xs text-slate-400 mt-1">{{ $curriculum->objectives }}</p>
                    </div>
                @endif
                @if($curriculum->materials)
                    <div>
                        <span class="text-[11px] font-medium text-slate-300">Materials:</span>
                        <p class="text-xs text-slate-400 mt-1">{{ $curriculum->materials }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-800">
            <h2 class="text-sm font-medium text-slate-50">Exams</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Title</th>
                        <th class="px-4 py-3 text-left font-medium">Type</th>
                        <th class="px-4 py-3 text-left font-medium">Date</th>
                        <th class="px-4 py-3 text-left font-medium">Max Score</th>
                        <th class="px-4 py-3 text-left font-medium">Published?</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($curriculum->exams as $exam)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">{{ $exam->title }}</td>
                            <td class="px-4 py-3">{{ ucfirst($exam->type) }}</td>
                            <td class="px-4 py-3">{{ $exam->exam_date->format('M j, Y') }}</td>
                            <td class="px-4 py-3">{{ number_format($exam->max_score, 2) }}</td>
                            <td class="px-4 py-3">{{ $exam->is_published ? 'Yes' : 'No' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No exams created for this curriculum.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
