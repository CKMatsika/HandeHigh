@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-semibold tracking-tight text-slate-50">{{ $dormitory->name }}</h1>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full uppercase tracking-wider {{ $dormitory->gender === 'female' ? 'bg-pink-500/10 text-pink-400 border border-pink-500/20' : 'bg-blue-500/10 text-blue-400 border border-blue-500/20' }}">
                    {{ $dormitory->gender }}
                </span>
                @if($dormitory->hostel)
                    <span class="text-xs text-slate-400">in {{ $dormitory->hostel->name }}</span>
                @endif
            </div>
            <p class="text-xs text-slate-400 mt-1">{{ $dormitory->description ?? 'No description provided.' }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dormitories.index') }}" class="text-slate-400 hover:text-slate-200 text-sm">
                &larr; Back to Dormitories
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Add Bed Form Modal / Inline -->
    <div class="bg-slate-900/80 rounded-xl border border-slate-800 p-4 flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h4 class="text-sm font-semibold text-slate-100">Add Individual Bed</h4>
            <p class="text-xs text-slate-400">Add additional custom bed numbers or replacement beds to this dormitory</p>
        </div>
        <form method="POST" action="{{ route('admin.dormitories.beds.store', $dormitory) }}" class="flex gap-2">
            @csrf
            <input type="text" name="bed_number" placeholder="Bed Number (e.g. Bed-21)" required
                class="px-3 py-1.5 border border-slate-700 rounded-lg bg-slate-800 text-slate-200 text-xs focus:ring-blue-500 focus:border-blue-500">
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-1.5 rounded-lg text-xs font-medium transition">
                + Add Bed
            </button>
        </form>
    </div>

    <!-- Bed Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
        @forelse($dormitory->beds as $bed)
            @php
                $assignment = $bed->currentAssignment;
                $isOccupied = (bool) $assignment;
            @endphp
            <div class="bg-slate-900/80 rounded-xl border {{ $isOccupied ? 'border-blue-500/40 bg-blue-950/10' : 'border-slate-800' }} p-4 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-bold text-sm {{ $isOccupied ? 'text-blue-400' : 'text-slate-300' }}">{{ $bed->bed_number }}</span>
                        <span class="h-2 w-2 rounded-full {{ $isOccupied ? 'bg-blue-400' : 'bg-emerald-400' }}" title="{{ $isOccupied ? 'Occupied' : 'Available' }}"></span>
                    </div>

                    @if($isOccupied && $assignment->student)
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-slate-100 block truncate">{{ $assignment->student->full_name }}</span>
                            <span class="text-[11px] text-slate-400 block">{{ $assignment->student->admission_number }}</span>
                            <span class="text-[10px] text-slate-500 block">{{ $assignment->academic_year }} T{{ $assignment->term }}</span>
                        </div>
                    @else
                        <span class="text-xs text-emerald-400 font-medium block">Available</span>
                    @endif
                </div>

                <div class="pt-3 mt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px]">
                    @if($isOccupied && $assignment->student)
                        <a href="{{ route('admin.students.manage-boarding', $assignment->student) }}" class="text-blue-400 hover:underline">
                            Student &rarr;
                        </a>
                    @else
                        <span class="text-slate-500">Unassigned</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full bg-slate-900/80 border border-slate-800 rounded-xl p-12 text-center text-slate-500 text-sm">
                No beds registered in this dormitory yet.
            </div>
        @endforelse
    </div>
</div>
@endsection
