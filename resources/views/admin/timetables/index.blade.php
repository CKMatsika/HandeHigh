@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-50">Timetable Management</h1>
            <p class="text-xs text-slate-400 mt-1">AI-powered scheduling with conflict detection and resolution.</p>
        </div>
        <a href="{{ route('admin.timetables.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Create Timetable
        </a>
    </div>

    <!-- Timetables Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($timetables as $timetable)
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-6 hover:bg-slate-900/90 transition-colors">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-green-500/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-50">{{ $timetable->name }}</h3>
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium
                                {{ $timetable->status === 'published' ? 'bg-emerald-500/20 text-emerald-400' : '' }}
                                {{ $timetable->status === 'generated' ? 'bg-blue-500/20 text-blue-400' : '' }}
                                {{ $timetable->status === 'draft' ? 'bg-amber-500/20 text-amber-400' : '' }}
                                {{ $timetable->status === 'archived' ? 'bg-slate-500/20 text-slate-400' : '' }}">
                                {{ ucfirst($timetable->status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center gap-2 text-slate-300">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>{{ $timetable->academic_year }} - {{ $timetable->term }}</span>
                    </div>

                    <div class="flex items-center gap-2 text-slate-300">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>{{ $timetable->slots_count }} slots</span>
                    </div>

                    @if($timetable->hasConflicts())
                        <div class="flex items-center gap-2 text-red-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            <span>{{ $timetable->getConflictCount() }} conflicts</span>
                        </div>
                    @endif

                    @if($timetable->description)
                        <div class="text-slate-400 line-clamp-2">
                            {{ $timetable->description }}
                        </div>
                    @endif
                </div>

                <div class="flex gap-2 mt-4 pt-4 border-t border-slate-800">
                    <a href="{{ route('admin.timetables.show', $timetable) }}" class="flex-1 text-center px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs transition-colors">
                        View
                    </a>
                    
                    @if($timetable->status === 'draft')
                        <form action="{{ route('admin.timetables.generate', $timetable) }}" method="POST" class="flex-1">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="w-full px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs transition-colors">
                                Generate
                            </button>
                        </form>
                    @elseif($timetable->status === 'generated')
                        @if($timetable->hasConflicts())
                            <a href="{{ route('admin.timetables.resolve-conflicts', $timetable) }}" class="flex-1 text-center px-3 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs transition-colors">
                                Fix Conflicts
                            </a>
                        @else
                            <form action="{{ route('admin.timetables.publish', $timetable) }}" method="POST" class="flex-1">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="w-full px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs transition-colors">
                                    Publish
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <div class="mb-4">
                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-slate-50 mb-2">No timetables found</h3>
                <p class="text-sm text-slate-400 mb-4">Start by creating your first AI-powered timetable.</p>
                <a href="{{ route('admin.timetables.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                    Create Timetable
                </a>
            </div>
        @endforelse
    </div>
</div>
@endsection
