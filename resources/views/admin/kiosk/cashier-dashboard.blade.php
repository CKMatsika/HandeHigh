@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div><h1 class="text-2xl font-semibold text-slate-50">{{ $isManager ? 'Kiosk Operations' : 'Cashier Shift' }}</h1><p class="mt-1 text-sm text-slate-400">Till control, shift close and signed-off sales readings.</p></div>
        <div class="flex gap-2"><a href="{{ route('admin.kiosk.pos') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Open POS</a>@can('kiosk.backoffice.view')<a href="{{ route('admin.kiosk.index') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-200">Back office</a>@endcan</div>
    </div>
    @if(session('success'))<div class="rounded-lg border border-emerald-700 bg-emerald-950/50 p-3 text-sm text-emerald-300">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-lg border border-rose-700 bg-rose-950/40 p-3 text-sm text-rose-200">{{ $errors->first() }}</div>@endif
    @if(!$openTill)
        <section class="rounded-xl border border-slate-800 bg-slate-900 p-5">
            <h2 class="font-semibold text-slate-100">Open your till</h2><p class="mb-4 mt-1 text-sm text-slate-400">Record the float physically placed in the till before accepting sales.</p>
            <form method="POST" action="{{ route('admin.kiosk.tills.open') }}" class="grid gap-3 md:grid-cols-3">@csrf
                <label class="text-xs text-slate-400">Till name<input name="till_label" required maxlength="80" value="{{ old('till_label', 'Main Kiosk') }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 p-2 text-sm text-white"></label>
                <label class="text-xs text-slate-400">Opening cash float ($)<input name="opening_float" type="number" min="0" step="0.01" required value="{{ old('opening_float', '0.00') }}" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 p-2 text-sm text-white"></label>
                <label class="text-xs text-slate-400">Opening notes<input name="opening_notes" maxlength="1000" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 p-2 text-sm text-white"></label>
                <div class="md:col-span-3"><button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Open Till</button></div>
            </form>
        </section>
    @else
        <section class="rounded-xl border border-emerald-800 bg-slate-900 p-5">
            <div class="flex flex-wrap justify-between gap-4"><div><div class="text-xs uppercase tracking-wide text-emerald-300">Till open</div><h2 class="mt-1 text-lg font-semibold text-white">{{ $openTill->till_label }}</h2><p class="text-sm text-slate-400">Opened {{ $openTill->opened_at->format('d M Y H:i') }} · Float ${{ number_format($openTill->opening_float, 2) }}</p></div><a href="{{ route('admin.kiosk.pos') }}" class="self-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Continue to POS</a></div>
            @can('kiosk.till.close')
            <form method="POST" action="{{ route('admin.kiosk.tills.close', $openTill) }}" class="mt-5 grid gap-3 border-t border-slate-800 pt-4 md:grid-cols-3">@csrf
                <label class="text-xs text-slate-400">Physically counted cash ($)<input name="counted_cash" type="number" min="0" step="0.01" required class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 p-2 text-sm text-white"></label>
                <label class="text-xs text-slate-400 md:col-span-2">Closing notes<input name="closing_notes" maxlength="1000" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-800 p-2 text-sm text-white"></label>
                <div class="md:col-span-3"><button class="rounded-lg border border-rose-700 bg-rose-950/50 px-4 py-2 text-sm font-semibold text-rose-200" onclick="return confirm('Close this till and generate its Z reading?')">Close Till & Generate Z Reading</button></div>
            </form>@endcan
        </section>
    @endif
    <section class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900"><div class="border-b border-slate-800 p-4"><h2 class="font-semibold text-slate-100">Recent shifts</h2></div><div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="text-xs uppercase text-slate-400"><tr><th class="p-3">Till</th><th class="p-3">Cashier</th><th class="p-3">Opened</th><th class="p-3">Status</th><th class="p-3">Sales</th><th class="p-3">Z Reading</th></tr></thead><tbody class="divide-y divide-slate-800">@forelse($sessions as $session)<tr><td class="p-3 text-white">{{ $session->till_label }}</td><td class="p-3 text-slate-300">{{ $session->cashier?->name }}</td><td class="p-3 text-slate-300">{{ $session->opened_at->format('d M H:i') }}</td><td class="p-3"><span class="rounded-full px-2 py-1 text-xs {{ $session->status === 'open' ? 'bg-emerald-950 text-emerald-300' : 'bg-slate-800 text-slate-300' }}">{{ ucfirst($session->status) }}</span></td><td class="p-3 text-slate-200">${{ number_format($session->total_sales,2) }}</td><td class="p-3">@if($session->status === 'closed')<a class="text-indigo-300 underline" href="{{ route('admin.kiosk.z-reading', $session) }}">{{ $session->z_reading_number }}</a>@else—@endif</td></tr>@empty<tr><td colspan="6" class="p-8 text-center text-slate-500">No till shifts recorded yet.</td></tr>@endforelse</tbody></table></div>
    </section>
</div>
@endsection
