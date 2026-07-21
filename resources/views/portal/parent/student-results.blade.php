@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">{{ $student->first_name }}'s Results</h1>
            <p class="text-xs text-slate-400 mt-1">Academic performance and grades for {{ $student->first_name }} {{ $student->last_name }}.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('parent.dashboard') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back to Dashboard</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Subject</th>
                        <th class="px-4 py-3 text-left font-medium">Class</th>
                        <th class="px-4 py-3 text-left font-medium">Academic Year</th>
                        <th class="px-4 py-3 text-left font-medium">Term</th>
                        <th class="px-4 py-3 text-left font-medium">Score</th>
                        <th class="px-4 py-3 text-left font-medium">Average</th>
                        <th class="px-4 py-3 text-left font-medium">Grade</th>
                        <th class="px-4 py-3 text-left font-medium">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($results as $result)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">{{ $result->subject->name }}</td>
                            <td class="px-4 py-3">{{ $result->class->name }}</td>
                            <td class="px-4 py-3">{{ $result->academic_year }}</td>
                            <td class="px-4 py-3">{{ $result->term ?? 'Final' }}</td>
                            <td class="px-4 py-3">{{ number_format($result->total_score, 1) }}/{{ number_format($result->max_total_score, 1) }}</td>
                            <td class="px-4 py-3 font-medium">{{ number_format($result->average, 1) }}%</td>
                            <td class="px-4 py-3">
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-700 text-slate-300">{{ $result->grade }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $result->remarks ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">No results found for {{ $student->first_name }}.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $results->links() }}
@endsection
