@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Teacher Details</h1>
            <p class="text-xs text-slate-400 mt-1">View teacher information and assignments.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.teachers.edit', $teacher) }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">Edit Teacher</a>
            <a href="{{ route('admin.teachers.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back to Teachers</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
                <h2 class="text-sm font-medium text-slate-50 mb-3">Personal Information</h2>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Name:</span>
                        <span class="text-slate-200">{{ $teacher->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Email:</span>
                        <span class="text-slate-200">{{ $teacher->email }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Phone:</span>
                        <span class="text-slate-200">{{ $teacher->phone ?? 'Not provided' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Address:</span>
                        <span class="text-slate-200">{{ $teacher->address ?? 'Not provided' }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 mt-4">
                <h2 class="text-sm font-medium text-slate-50 mb-3">Professional Details</h2>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Qualification:</span>
                        <span class="text-slate-200">{{ $teacher->metadata['qualification'] ?? 'Not specified' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Specialization:</span>
                        <span class="text-slate-200">{{ $teacher->metadata['specialization'] ?? 'Not specified' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Employment Date:</span>
                        <span class="text-slate-200">{{ $teacher->metadata['employment_date'] ? \Carbon\Carbon::parse($teacher->metadata['employment_date'])->format('M j, Y') : 'Not specified' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Status:</span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400">Active</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800">
                    <h2 class="text-sm font-medium text-slate-50">Assigned Classes</h2>
                </div>
                <div class="p-4">
                    @forelse($teacherClasses as $class)
                        <div class="flex items-center justify-between py-2 border-b border-slate-800 last:border-0">
                            <div>
                                <p class="text-xs font-medium text-slate-50">{{ $class->name }}</p>
                                <p class="text-[10px] text-slate-400">{{ $class->grade }} • {{ $class->academic_year }}</p>
                            </div>
                            <span class="text-xs text-slate-400">{{ $class->term ?? 'All Year' }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-4">No classes assigned</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-800">
                    <h2 class="text-sm font-medium text-slate-50">Subject Assignments</h2>
                </div>
                <div class="p-4">
                    @forelse($curricula as $curriculum)
                        <div class="flex items-center justify-between py-2 border-b border-slate-800 last:border-0">
                            <div>
                                <p class="text-xs font-medium text-slate-50">{{ $curriculum->subject->name }}</p>
                                <p class="text-[10px] text-slate-400">{{ $curriculum->class->name }} • {{ $curriculum->weekly_periods }} periods/week</p>
                            </div>
                            <span class="text-xs text-slate-400">{{ $curriculum->term ?? 'All Year' }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-4">No subjects assigned</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
