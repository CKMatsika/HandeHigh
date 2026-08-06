@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Assess Students</h1>
            <p class="text-xs text-slate-400 mt-1">{{ $assessedStudents }} students have been assessed · {{ $students->total() }} students pending.</p>
        </div>
        <a href="{{ route('admin.career-guidance.index') }}" class="inline-flex items-center rounded-lg bg-slate-800 px-3 py-2 text-xs font-medium text-slate-300 hover:bg-slate-700">Back to Dashboard</a>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-800/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Grade</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Class</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-slate-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($students as $student)
                        <tr class="hover:bg-slate-800/50">
                            <td class="px-4 py-3">
                                <p class="text-sm text-slate-50">{{ $student->first_name }} {{ $student->last_name }}</p>
                                <p class="text-[10px] text-slate-400">{{ $student->student_id }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-300">{{ $student->grade ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-300">{{ $student->currentEnrollment?->class?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs">
                                <form method="POST" action="{{ route('admin.career-guidance.assess') }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="student_id" value="{{ $student->id }}">
                                    <button type="submit" class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700" onclick="return confirm('Generate career assessment for {{ $student->first_name }}?')">
                                        Generate Assessment
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-12 text-center text-slate-400"><p class="text-sm">All students have been assessed!</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($students->hasPages())<div class="px-4 py-3 border-t border-slate-800">{{ $students->links() }}</div>@endif
    </div>
</div>
@endsection
