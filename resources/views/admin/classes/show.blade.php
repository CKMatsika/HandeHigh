@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">{{ $class->name }}</h1>
            <p class="text-xs text-slate-400 mt-1">Class details and assigned curricula.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.classes.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back</a>
            <a href="{{ route('admin.classes.edit', $class) }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Edit</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-200">
            <div><span class="text-slate-400">Name:</span> {{ $class->name }}</div>
            <div><span class="text-slate-400">Grade:</span> {{ $class->grade }}</div>
            <div><span class="text-slate-400">Academic Year:</span> {{ $class->academic_year }}</div>
            <div><span class="text-slate-400">Term:</span> {{ $class->term ?? '-' }}</div>
            <div><span class="text-slate-400">Teacher:</span> {{ $class->teacher?->name ?? '-' }}</div>
            <div><span class="text-slate-400">Students enrolled:</span> {{ $class->enrollments->count() }}</div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-800">
            <h2 class="text-sm font-medium text-slate-50">Curricula</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Subject</th>
                        <th class="px-4 py-3 text-left font-medium">Teacher</th>
                        <th class="px-4 py-3 text-left font-medium">Weekly Periods</th>
                        <th class="px-4 py-3 text-left font-medium">Term</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($class->curricula as $curriculum)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">{{ $curriculum->subject->name }}</td>
                            <td class="px-4 py-3">{{ $curriculum->teacher?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $curriculum->weekly_periods }}</td>
                            <td class="px-4 py-3">{{ $curriculum->term ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500">No curricula assigned.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
