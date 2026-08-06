@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-lg font-semibold text-slate-50">{{ $set->title }}</h1>
        <p class="text-xs text-slate-400 mt-1">{{ $set->subject->name ?? 'All Subjects' }} | {{ $set->schoolClass->name ?? 'All Classes' }} | {{ $set->items->count() }} cards</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('teacher.flash-cards.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
            Back
        </a>
        <a href="{{ route('teacher.flash-cards.edit', $set) }}" class="inline-flex items-center rounded-full bg-indigo-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-600 transition">
            Edit
        </a>
        @if($set->status === 'draft')
            <form method="POST" action="{{ route('teacher.flash-cards.publish', $set) }}" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-600 transition">
                    Publish
                </button>
            </form>
        @endif
    </div>
</div>

<div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
    <div class="flex items-center gap-3 mb-4">
        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium capitalize
            {{ $set->status === 'published' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400' : '' }}
            {{ $set->status === 'draft' ? 'border-slate-500/30 bg-slate-500/10 text-slate-400' : '' }}
            {{ $set->status === 'archived' ? 'border-rose-500/30 bg-rose-500/10 text-rose-400' : '' }}">
            {{ $set->status }}
        </span>
        <span class="text-[11px] text-slate-400">Source: {{ $set->source_type }}</span>
        <span class="text-[11px] text-slate-400">Created: {{ $set->created_at->format('d M Y') }}</span>
    </div>

    @if($set->description)
        <p class="text-xs text-slate-300 mb-4">{{ $set->description }}</p>
    @endif

    <div class="space-y-2">
        @foreach($set->items as $item)
            <div class="rounded-xl border border-slate-700 bg-slate-950/60 p-3">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-[11px] font-medium text-indigo-400">Front</span>
                        <p class="text-xs text-slate-100 mt-1">{{ $item->front_text }}</p>
                    </div>
                    <div>
                        <span class="text-[11px] font-medium text-emerald-400">Back</span>
                        <p class="text-xs text-slate-100 mt-1">{{ $item->back_text }}</p>
                    </div>
                </div>
                @if($item->hint)
                    <div class="mt-2 text-[11px] text-slate-400">
                        <span class="font-medium">Hint:</span> {{ $item->hint }}
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
