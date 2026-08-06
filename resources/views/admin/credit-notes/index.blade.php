@extends('layouts.app')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Credit Notes</h1>
            <p class="text-xs text-slate-400 mt-1">Issue and manage credit notes for students and customers.</p>
        </div>
        <a href="{{ route('admin.credit-notes.create') }}"
           class="rounded-full bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">
            + New Credit Note
        </a>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-900/50 border border-green-700 text-green-200 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-3 bg-red-900/50 border border-red-700 text-red-200 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach([
            ['label'=>'Draft',     'key'=>'draft',     'color'=>'text-slate-400', 'bg'=>'bg-slate-800/60'],
            ['label'=>'Issued',    'key'=>'issued',    'color'=>'text-amber-400',  'bg'=>'bg-amber-900/20'],
            ['label'=>'Applied',   'key'=>'applied',   'color'=>'text-green-400',  'bg'=>'bg-green-900/20'],
            ['label'=>'Cancelled', 'key'=>'cancelled', 'color'=>'text-red-400',    'bg'=>'bg-red-900/20'],
        ] as $card)
        <div class="{{ $card['bg'] }} border border-slate-700 rounded-xl p-4">
            <p class="text-xs text-slate-400">{{ $card['label'] }}</p>
            <p class="text-lg font-bold {{ $card['color'] }} mt-1">
                {{ number_format($totals[$card['key']], 2) }}
            </p>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search number or reason…"
               class="bg-slate-800 border border-slate-700 text-slate-200 text-xs rounded-lg px-3 py-2 w-52 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        <select name="status" class="bg-slate-800 border border-slate-700 text-slate-200 text-xs rounded-lg px-3 py-2 focus:outline-none">
            <option value="">All Statuses</option>
            @foreach(['draft','issued','applied','cancelled'] as $s)
                <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <select name="type" class="bg-slate-800 border border-slate-700 text-slate-200 text-xs rounded-lg px-3 py-2 focus:outline-none">
            <option value="">All Types</option>
            <option value="student"  @selected(request('type')==='student')>Student</option>
            <option value="customer" @selected(request('type')==='customer')>Customer</option>
        </select>
        <button class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium px-4 py-2 rounded-lg transition">Filter</button>
        <a href="{{ route('admin.credit-notes.index') }}" class="text-slate-400 hover:text-slate-200 text-xs py-2 px-2">Clear</a>
    </form>

    {{-- Table --}}
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Credit Note #</th>
                        <th class="px-4 py-3 text-left font-medium">Date</th>
                        <th class="px-4 py-3 text-left font-medium">Type</th>
                        <th class="px-4 py-3 text-left font-medium">Recipient</th>
                        <th class="px-4 py-3 text-left font-medium">Invoice</th>
                        <th class="px-4 py-3 text-right font-medium">Amount</th>
                        <th class="px-4 py-3 text-right font-medium">Balance</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($creditNotes as $cn)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3 font-mono text-indigo-300">{{ $cn->credit_note_number }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $cn->credit_note_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs
                                    {{ $cn->type === 'student' ? 'bg-blue-900/40 text-blue-300' : 'bg-purple-900/40 text-purple-300' }}">
                                    {{ ucfirst($cn->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-300">
                                @if($cn->type === 'student' && $cn->student)
                                    {{ $cn->student->first_name }} {{ $cn->student->last_name }}
                                @elseif($cn->customer)
                                    {{ $cn->customer->name }}
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-400 font-mono">
                                {{ $cn->invoice?->invoice_number ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right text-slate-200 font-medium">
                                {{ number_format($cn->total_amount, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right {{ $cn->balance > 0 ? 'text-amber-300' : 'text-slate-400' }} font-medium">
                                {{ number_format($cn->balance, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $colors = [
                                        'draft'     => 'bg-slate-700 text-slate-300',
                                        'issued'    => 'bg-amber-900/50 text-amber-300',
                                        'applied'   => 'bg-green-900/50 text-green-300',
                                        'cancelled' => 'bg-red-900/50 text-red-400',
                                    ];
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs {{ $colors[$cn->status] ?? '' }}">
                                    {{ ucfirst($cn->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.credit-notes.show', $cn) }}"
                                   class="text-indigo-400 hover:text-indigo-200 transition">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-slate-500">No credit notes found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($creditNotes->hasPages())
            <div class="px-4 py-3 border-t border-slate-800 bg-slate-950/30">
                {{ $creditNotes->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
