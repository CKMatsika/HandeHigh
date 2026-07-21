@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">{{ $subject->name }}</h1>
            <p class="text-xs text-slate-400 mt-1">Subject details and assigned curricula.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.subjects.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back</a>
            <a href="{{ route('admin.subjects.edit', $subject) }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Edit</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-200">
            <div><span class="text-slate-400">Code:</span> {{ $subject->code }}</div>
            <div><span class="text-slate-400">Name:</span> {{ $subject->name }}</div>
            <div><span class="text-slate-400">Core subject:</span> {{ $subject->is_core ? 'Yes' : 'No' }}</div>
            <div><span class="text-slate-400">Description:</span> {{ $subject->description ?: '-' }}</div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-800">
            <h2 class="text-sm font-medium text-slate-50">Assigned Curricula</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Class</th>
                        <th class="px-4 py-3 text-left font-medium">Teacher</th>
                        <th class="px-4 py-3 text-left font-medium">Academic Year</th>
                        <th class="px-4 py-3 text-left font-medium">Term</th>
                        <th class="px-4 py-3 text-left font-medium">Weekly Periods</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($subject->curricula as $curriculum)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">{{ $curriculum->class->name }} ({{ $curriculum->class->grade }})</td>
                            <td class="px-4 py-3">{{ $curriculum->teacher?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $curriculum->academic_year }}</td>
                            <td class="px-4 py-3">{{ $curriculum->term ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $curriculum->weekly_periods }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No curricula assigned.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
