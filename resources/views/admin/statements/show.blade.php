@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Student statement</h1>
            <p class="text-xs text-slate-400 mt-1">Invoices, payments, and running balance for {{ $student->first_name }} {{ $student->last_name }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form method="GET" action="{{ route('admin.students.statement.download', $student) }}" target="_blank" class="inline">
                @csrf
                <input type="hidden" name="academic_year" value="{{ $academicYear }}">
                @if($term)
                    <input type="hidden" name="term" value="{{ $term }}">
                @endif
                <button type="submit" class="inline-flex items-center rounded-full bg-blue-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-blue-600 transition">
                    Download PDF
                </button>
            </form>
            <form method="GET" action="{{ route('admin.students.statement.print', $student) }}" target="_blank" class="inline">
                @csrf
                <input type="hidden" name="academic_year" value="{{ $academicYear }}">
                @if($term)
                    <input type="hidden" name="term" value="{{ $term }}">
                @endif
                <button type="submit" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-600 transition">Print</button>
            </form>
            <button onclick="document.getElementById('emailForm').classList.toggle('hidden')" class="inline-flex items-center rounded-full bg-purple-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-purple-600 transition">
                Email Statement
            </button>
        </div>
    </div>

    <!-- Email Form -->
    <div id="emailForm" class="hidden mb-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
            <form method="POST" action="{{ route('admin.students.statement.email', $student) }}">
                @csrf
                <input type="hidden" name="academic_year" value="{{ $academicYear }}">
                @if($term)
                    <input type="hidden" name="term" value="{{ $term }}">
                @endif
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-slate-300 mb-1">Email Address</label>
                        <input type="email" name="email" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="parent@example.com">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-purple-500 text-white rounded-lg text-xs font-medium hover:bg-purple-600 transition">
                            Send Email
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-slate-200 mb-4">
            <div><span class="text-slate-400">Student:</span> {{ $student->first_name }} {{ $student->last_name }}</div>
            <div><span class="text-slate-400">Grade/Class:</span> {{ $student->grade }} {{ $student->class_name }}</div>
            <div><span class="text-slate-400">School:</span> {{ $school->name }}</div>
            <div><span class="text-slate-400">Academic Year:</span> {{ $academicYear }}</div>
            <div><span class="text-slate-400">Term:</span> {{ $term ?? 'All terms' }}</div>
            <div><span class="text-slate-400">Opening Balance:</span> {{ number_format($openingBalance, 2) }}</div>
            <div><span class="text-slate-400">Closing Balance:</span> {{ number_format($closingBalance, 2) }}</div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Date</th>
                        <th class="px-4 py-3 text-left font-medium">Reference</th>
                        <th class="px-4 py-3 text-left font-medium">Description</th>
                        <th class="px-4 py-3 text-right font-medium">Debit</th>
                        <th class="px-4 py-3 text-right font-medium">Credit</th>
                        <th class="px-4 py-3 text-right font-medium">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @if($openingBalance != 0)
                        <tr class="bg-slate-800/50 font-medium">
                            <td colspan="3" class="px-4 py-3">Opening Balance (Brought Forward)</td>
                            <td class="px-4 py-3 text-right">{{ number_format($openingBalance, 2) }}</td>
                            <td class="px-4 py-3 text-right"></td>
                            <td class="px-4 py-3 text-right">{{ number_format($openingBalance, 2) }}</td>
                        </tr>
                    @endif
                    @forelse($events as $event)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-3">{{ $event['date']->format('M j, Y') }}</td>
                            <td class="px-4 py-3">{{ $event['reference'] }}</td>
                            <td class="px-4 py-3">{{ $event['description'] }}</td>
                            <td class="px-4 py-3 text-right">{{ $event['debit'] ? number_format($event['debit'], 2) : '' }}</td>
                            <td class="px-4 py-3 text-right">{{ $event['credit'] ? number_format($event['credit'], 2) : '' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($event['balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No activity found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
