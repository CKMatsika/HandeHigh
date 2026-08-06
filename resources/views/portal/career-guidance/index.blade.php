@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-50">Career Guidance</h1>
        <p class="text-xs text-slate-400 mt-1">View career assessments for your students.</p>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="p-4 border-b border-slate-800">
            <h3 class="text-sm font-semibold text-slate-50">Your Students</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Class</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Assessment</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($allStudents as $student)
                        <tr class="hover:bg-slate-800/50">
                            <td class="px-4 py-3">
                                <p class="text-sm text-slate-50">{{ $student->first_name }} {{ $student->last_name }}</p>
                                <p class="text-[10px] text-slate-400">{{ $student->student_id }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">{{ $student->currentEnrollment?->class?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs">
                                @php $assessment = $student->careerAssessment; @endphp
                                @if($assessment)
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                        {{ $assessment->status === 'published' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400' }}">
                                        {{ $assessment->assessment_date->format('d M Y') }}
                                    </span>
                                @else
                                    <span class="text-slate-500">Not assessed</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if($assessment)
                                    <a href="{{ route('teacher.career-guidance.show', $assessment) }}" class="text-emerald-400 hover:text-emerald-300">View Report</a>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-12 text-center text-slate-400"><p class="text-sm">No students assigned to you.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
