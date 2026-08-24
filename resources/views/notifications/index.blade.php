@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-50">Notifications Center</h1>
            <p class="text-xs text-slate-400 mt-1">Timetable adjustments, substitution assignments, and school operations updates.</p>
        </div>

        <div class="flex items-center gap-2.5">
            @if($unreadCount > 0)
                <form action="{{ route('notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded-lg border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-700 transition">
                        Mark All as Read ({{ $unreadCount }})
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Notifications List -->
    <div class="space-y-3">
        @forelse($notifications as $n)
            @php
                $data = $n->data;
                $isUnread = $n->unread();
            @endphp
            <div class="rounded-xl border {{ $isUnread ? 'border-indigo-500/40 bg-indigo-950/20' : 'border-slate-800 bg-slate-900/60' }} p-4 shadow-sm transition flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        @if($isUnread)
                            <span class="h-2 w-2 rounded-full bg-indigo-400"></span>
                        @endif
                        <h3 class="text-xs font-bold text-slate-100">{{ $data['title'] ?? 'Notification' }}</h3>
                        <span class="text-[10px] text-slate-500 font-mono">{{ $n->created_at->diffForHumans() }}</span>
                    </div>

                    <p class="text-xs text-slate-300 pl-4">{{ $data['message'] ?? '' }}</p>

                    @if(!empty($data['reason']))
                        <p class="text-[11px] text-slate-400 pl-4"><span class="font-semibold text-slate-300">Reason:</span> {{ $data['reason'] }}</p>
                    @endif
                </div>

                @if($isUnread)
                    <form action="{{ route('notifications.read', $n->id) }}" method="POST" class="self-center">
                        @csrf
                        <button type="submit" class="rounded bg-slate-800 px-2.5 py-1 text-[11px] font-medium text-slate-300 hover:text-white transition">
                            Mark Read
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-8 text-center text-slate-400">
                You have no notifications.
            </div>
        @endforelse

        <div class="pt-4">
            {{ $notifications->links() }}
        </div>
    </div>
</div>
@endsection
