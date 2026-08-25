@extends('layouts.app')

@section('content')
    <style>
        @media print {
            aside, header, nav, .no-print { display: none !important; }
            main { padding: 0 !important; }
            body { background: white !important; color: black !important; }
            table { width: 100% !important; }
            .bg-slate-900, .bg-slate-950 { background: white !important; border: 1px solid #333 !important; }
            .text-slate-100, .text-slate-200, .text-slate-300, .text-slate-50 { color: black !important; }
            .text-slate-400, .text-slate-500 { color: #555 !important; }
            .border-slate-800, .border-slate-700 { border-color: #333 !important; }
        }
    </style>

    <div class="no-print flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Student Statement Printout</h1>
            <p class="text-xs text-slate-400 mt-1">Official running balance ledger for {{ $student->first_name }} {{ $student->last_name }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button onclick="window.print()" class="inline-flex items-center rounded-full bg-emerald-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-emerald-600 transition">Print Statement</button>
            <a href="{{ route('admin.students.show', $student) }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back</a>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-6 py-6 shadow-xl">
        <x-documents.school-header 
            :school="$school ?? null"
            title="Official Student Account Statement"
            :subtitle="'Academic Year ' . $academicYear . ($term ? ' · ' . $term : ' · Full Year')"
            :reference="'STMT-' . $student->admission_number"
            :date="now()"
        />

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 py-3 px-4 rounded-xl bg-slate-950/50 border border-slate-800 text-xs mb-4">
            <div>
                <span class="text-slate-400 block text-[11px] uppercase tracking-wider">Student Name</span>
                <strong class="text-slate-100">{{ $student->first_name }} {{ $student->last_name }}</strong>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px] uppercase tracking-wider">Admission #</span>
                <span class="font-mono text-slate-200">{{ $student->admission_number ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px] uppercase tracking-wider">Grade / Class</span>
                <span class="text-slate-200">{{ $student->grade }} {{ $student->class_name }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px] uppercase tracking-wider">Current Balance</span>
                <span class="font-bold font-mono text-sm {{ $closingBalance > 0 ? 'text-rose-400' : 'text-emerald-400' }}">${{ number_format($closingBalance, 2) }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-950/60 text-slate-300">
                    <tr class="border-b border-slate-800 uppercase text-[10px] tracking-wider">
                        <th class="px-4 py-2.5 text-left font-medium">Date</th>
                        <th class="px-4 py-2.5 text-left font-medium">Reference</th>
                        <th class="px-4 py-2.5 text-left font-medium">Description</th>
                        <th class="px-4 py-2.5 text-right font-medium">Debit ($)</th>
                        <th class="px-4 py-2.5 text-right font-medium">Credit ($)</th>
                        <th class="px-4 py-2.5 text-right font-medium">Running Balance ($)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @if($openingBalance != 0)
                        <tr class="bg-slate-800/50 font-medium">
                            <td colspan="3" class="px-4 py-2.5">Opening Balance (Brought Forward)</td>
                            <td class="px-4 py-2.5 text-right font-mono">{{ number_format($openingBalance, 2) }}</td>
                            <td class="px-4 py-2.5 text-right font-mono"></td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold">{{ number_format($openingBalance, 2) }}</td>
                        </tr>
                    @endif
                    @forelse($events as $event)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-4 py-2.5 text-slate-300">{{ $event['date']->format('d M Y') }}</td>
                            <td class="px-4 py-2.5 font-mono text-slate-400">{{ $event['reference'] }}</td>
                            <td class="px-4 py-2.5 text-slate-200">{{ $event['description'] }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-rose-300">{{ $event['debit'] ? number_format($event['debit'], 2) : '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-mono text-emerald-300">{{ $event['credit'] ? number_format($event['credit'], 2) : '—' }}</td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold text-slate-100">{{ number_format($event['balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-slate-500">No transactions recorded for the selected period.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t-2 border-slate-700 bg-slate-950/60 font-bold">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right uppercase tracking-wider text-slate-300">Closing Account Balance:</td>
                        <td class="px-4 py-3 text-right font-mono text-sm {{ $closingBalance > 0 ? 'text-rose-400' : 'text-emerald-400' }}">${{ number_format($closingBalance, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <x-documents.school-footer 
            :school="$school ?? null"
            :showBanking="true"
        />
    </div>
@endsection
