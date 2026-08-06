@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-lg font-semibold text-slate-50">Flash Cards</h1>
        <p class="text-xs text-slate-400 mt-1">Study and review with digital flashcards</p>
    </div>
</div>

@if($previousSessions->count() > 0)
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 mb-4">
        <h2 class="text-sm font-semibold text-slate-100 mb-3">Recent Study Sessions</h2>
        <div class="space-y-2">
            @foreach($previousSessions as $session)
                <div class="flex items-center justify-between rounded-xl border border-slate-700 bg-slate-950/60 px-4 py-2">
                    <div>
                        <span class="text-xs text-slate-200">{{ $session->flashCardSet->title }}</span>
                        <span class="text-[11px] text-slate-400 ml-2">{{ $session->cards_studied }} cards · {{ $session->cards_confident }} confident</span>
                    </div>
                    <span class="text-[11px] text-slate-500">{{ $session->completed_at->diffForHumans() }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4">
    <h2 class="text-sm font-semibold text-slate-100 mb-3">Available Sets</h2>
    @if($sets->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($sets as $set)
                <a href="{{ route('student.flash-cards.study', $set) }}" class="block rounded-xl border border-slate-700 bg-slate-950/60 p-4 hover:border-indigo-500/50 hover:bg-slate-950 transition group">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-sm font-medium text-slate-100 group-hover:text-indigo-300 transition">{{ $set->title }}</h3>
                            @if($set->subject)
                                <p class="text-[11px] text-slate-400 mt-1">{{ $set->subject->name }}</p>
                            @endif
                        </div>
                        <span class="inline-flex items-center rounded-full bg-indigo-500/10 px-2 py-0.5 text-[11px] font-medium text-indigo-400">
                            {{ $set->items->count() }} cards
                        </span>
                    </div>
                    @if($set->description)
                        <p class="text-[11px] text-slate-500 mt-2">{{ Str::limit($set->description, 80) }}</p>
                    @endif
                </a>
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <p class="text-sm text-slate-400">No flash cards available yet.</p>
            <p class="text-xs text-slate-500 mt-1">Check back later when your teacher publishes new sets.</p>
        </div>
    @endif
</div>
@endsection
