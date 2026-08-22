@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 print:hidden">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.reports.finance-dashboard') }}" class="text-xs text-slate-400 hover:text-slate-200">&larr; Financial Reports</a>
                <span class="text-slate-600">/</span>
                <span class="text-xs text-slate-300">Student Account Statement</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-100 mt-1">Student Account Statement</h1>
            <p class="text-xs text-slate-400 mt-0.5">Comprehensive financial ledger, running balances, and audit history</p>
        </div>
        @if($statementData)
            <button onclick="window.print()" class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-800 text-slate-200 hover:bg-slate-700 border border-slate-700 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Statement
            </button>
        @endif
    </div>

    <!-- Student Selection & Filter Bar -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 print:hidden">
        <form method="GET" action="{{ route('admin.reports.student-statement') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-400 mb-1">Select Student <span class="text-red-400">*</span></label>
                    <select name="student_id" required class="w-full text-xs rounded-xl bg-slate-950/60 border border-slate-800 text-slate-200 px-3 py-2.5 focus:ring-1 focus:ring-blue-500">
                        <option value="">-- Choose Student --</option>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}" {{ $selectedStudentId == $s->id ? 'selected' : '' }}>
                                {{ $s->first_name }} {{ $s->last_name }} (Adm: {{ $s->admission_number ?? 'N/A' }} | {{ $s->grade ?? 'Form ?' }} - {{ $s->class_name ?? 'Class ?' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Academic Year</label>
                    <select name="academic_year" class="w-full text-xs rounded-xl bg-slate-950/60 border border-slate-800 text-slate-200 px-3 py-2.5 focus:ring-1 focus:ring-blue-500">
                        <option value="">All Academic Years</option>
                        @foreach($years as $yr)
                            <option value="{{ $yr }}" {{ request('academic_year') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-400 mb-1">Term</label>
                    <select name="term" class="w-full text-xs rounded-xl bg-slate-950/60 border border-slate-800 text-slate-200 px-3 py-2.5 focus:ring-1 focus:ring-blue-500">
                        <option value="">All Terms</option>
                        <option value="Term 1" {{ request('term') == 'Term 1' ? 'selected' : '' }}>Term 1</option>
                        <option value="Term 2" {{ request('term') == 'Term 2' ? 'selected' : '' }}>Term 2</option>
                        <option value="Term 3" {{ request('term') == 'Term 3' ? 'selected' : '' }}>Term 3</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl bg-blue-600 text-white hover:bg-blue-500 transition">
                    Generate Statement
                </button>
            </div>
        </form>
    </div>

    @if($statementData)
        <!-- Official Printable Statement Container -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-8 shadow-2xl print:border-none print:bg-white print:text-slate-900 print:p-0">
            <!-- Statement Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-slate-800 print:border-slate-300 gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-100 print:text-slate-900">{{ $statementData['school']->name }}</h2>
                    <p class="text-xs text-slate-400 print:text-slate-600 mt-1">OFFICIAL STUDENT ACCOUNT STATEMENT</p>
                    <p class="text-xs text-slate-400 print:text-slate-600">Generated: {{ now()->format('d M Y, H:i') }}</p>
                </div>
                <div class="text-right sm:text-right">
                    <div class="text-xs text-slate-400 print:text-slate-600">Statement Period</div>
                    <div class="text-sm font-semibold text-slate-200 print:text-slate-900 mt-0.5">
                        {{ $statementData['academic_year'] ?? 'All Time' }} {{ $statementData['term'] ? ' - ' . $statementData['term'] : '' }}
                    </div>
                </div>
            </div>

            <!-- Student Metadata Box -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 py-5 border-b border-slate-800 print:border-slate-300">
                <div>
                    <span class="text-[11px] text-slate-400 print:text-slate-600 uppercase tracking-wider block">Student Name</span>
                    <span class="text-sm font-bold text-slate-100 print:text-slate-900">{{ $statementData['student']->first_name }} {{ $statementData['student']->last_name }}</span>
                </div>
                <div>
                    <span class="text-[11px] text-slate-400 print:text-slate-600 uppercase tracking-wider block">Admission #</span>
                    <span class="text-sm font-mono font-semibold text-slate-200 print:text-slate-800">{{ $statementData['student']->admission_number ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-[11px] text-slate-400 print:text-slate-600 uppercase tracking-wider block">Form / Class</span>
                    <span class="text-sm font-semibold text-slate-200 print:text-slate-800">{{ $statementData['student']->grade ?? 'N/A' }} ({{ $statementData['student']->class_name ?? 'N/A' }})</span>
                </div>
                <div>
                    <span class="text-[11px] text-slate-400 print:text-slate-600 uppercase tracking-wider block">Boarding Status</span>
                    <span class="text-sm font-semibold text-slate-200 print:text-slate-800">{{ $statementData['student']->is_boarding ? 'Boarder' : 'Day Scholar' }}</span>
                </div>
            </div>

            <!-- Statement Summary KPIs -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 my-6">
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 print:border-slate-300 print:bg-slate-50">
                    <span class="text-[11px] text-slate-400 print:text-slate-600 font-medium">Opening Balance</span>
                    <div class="text-lg font-bold text-slate-100 print:text-slate-900 font-mono mt-1">${{ number_format($statementData['opening_balance'], 2) }}</div>
                </div>
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 print:border-slate-300 print:bg-slate-50">
                    <span class="text-[11px] text-blue-400 print:text-blue-700 font-medium">Total Charges (Debits)</span>
                    <div class="text-lg font-bold text-blue-400 print:text-blue-700 font-mono mt-1">${{ number_format($statementData['total_debits'], 2) }}</div>
                </div>
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 print:border-slate-300 print:bg-slate-50">
                    <span class="text-[11px] text-emerald-400 print:text-emerald-700 font-medium">Total Payments / Credits</span>
                    <div class="text-lg font-bold text-emerald-400 print:text-emerald-700 font-mono mt-1">${{ number_format($statementData['total_credits'], 2) }}</div>
                </div>
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 print:border-slate-300 print:bg-slate-50">
                    <span class="text-[11px] text-red-400 print:text-red-700 font-medium">Closing Balance Due</span>
                    <div class="text-lg font-bold text-red-400 print:text-red-700 font-mono mt-1">${{ number_format($statementData['closing_balance'], 2) }}</div>
                </div>
            </div>

            <!-- Detailed Ledger Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-950/80 print:bg-slate-100 text-slate-300 print:text-slate-700 border-b border-slate-800 print:border-slate-300">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Date</th>
                            <th class="px-4 py-3 text-left font-semibold">Reference</th>
                            <th class="px-4 py-3 text-left font-semibold">Description</th>
                            <th class="px-4 py-3 text-right font-semibold">Debit ($)</th>
                            <th class="px-4 py-3 text-right font-semibold">Credit ($)</th>
                            <th class="px-4 py-3 text-right font-semibold">Running Balance ($)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 print:divide-slate-200">
                        @if($statementData['opening_balance'] != 0)
                            <tr class="bg-slate-950/40 print:bg-slate-50 font-medium">
                                <td class="px-4 py-2.5 text-slate-400 print:text-slate-600">-</td>
                                <td class="px-4 py-2.5 font-mono text-slate-400 print:text-slate-600">OPENING</td>
                                <td class="px-4 py-2.5 text-slate-300 print:text-slate-800">Opening Balance Brought Forward</td>
                                <td class="px-4 py-2.5 text-right font-mono text-slate-400">-</td>
                                <td class="px-4 py-2.5 text-right font-mono text-slate-400">-</td>
                                <td class="px-4 py-2.5 text-right font-mono font-bold text-slate-200 print:text-slate-900">${{ number_format($statementData['opening_balance'], 2) }}</td>
                            </tr>
                        @endif

                        @forelse($statementData['ledger_rows'] as $entry)
                            <tr class="hover:bg-slate-800/30 print:hover:bg-transparent transition">
                                <td class="px-4 py-3 text-slate-300 print:text-slate-800 whitespace-nowrap">{{ $entry['date'] }}</td>
                                <td class="px-4 py-3 font-mono text-blue-400 print:text-blue-700 whitespace-nowrap">{{ $entry['reference'] }}</td>
                                <td class="px-4 py-3 text-slate-200 print:text-slate-900">
                                    <div>{{ $entry['description'] }}</div>
                                    @if(isset($entry['items']) && $entry['items']->isNotEmpty())
                                        <div class="mt-1 pl-2 border-l border-slate-700 print:border-slate-300 space-y-0.5">
                                            @foreach($entry['items'] as $item)
                                                <div class="text-[10px] text-slate-400 print:text-slate-600 flex justify-between max-w-sm">
                                                    <span>{{ $item['description'] }}</span>
                                                    <span class="font-mono">${{ number_format($item['amount'], 2) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-blue-400 print:text-blue-700">
                                    {{ $entry['debit'] > 0 ? '$' . number_format($entry['debit'], 2) : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-emerald-400 print:text-emerald-700">
                                    {{ $entry['credit'] > 0 ? '$' . number_format($entry['credit'], 2) : '-' }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold {{ $entry['running_balance'] > 0 ? 'text-red-400 print:text-red-700' : 'text-emerald-400 print:text-emerald-700' }}">
                                    ${{ number_format($entry['running_balance'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">No financial transactions recorded for this student in the selected period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-slate-950/80 print:bg-slate-100 font-bold border-t-2 border-slate-800 print:border-slate-300 text-slate-200 print:text-slate-900">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-left">Totals & Final Closing Balance</td>
                            <td class="px-4 py-3 text-right font-mono text-blue-400 print:text-blue-700">${{ number_format($statementData['total_debits'], 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-emerald-400 print:text-emerald-700">${{ number_format($statementData['total_credits'], 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-red-400 print:text-red-700">${{ number_format($statementData['closing_balance'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Sign-off Section -->
            <div class="mt-12 pt-6 border-t border-slate-800 print:border-slate-300 grid grid-cols-2 gap-8 text-xs text-slate-400 print:text-slate-600">
                <div>
                    <p class="mb-8">Accounts Office Stamp & Signature:</p>
                    <div class="border-b border-dashed border-slate-700 print:border-slate-400 w-48"></div>
                </div>
                <div class="text-right">
                    <p class="mb-8">Date of Issue:</p>
                    <div class="border-b border-dashed border-slate-700 print:border-slate-400 w-48 ml-auto"></div>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-12 text-center text-slate-500">
            <svg class="w-12 h-12 mx-auto text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            <h3 class="text-base font-semibold text-slate-300">Select a Student to Generate Financial Statement</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Choose a registered student from the dropdown above to view their comprehensive invoice history and running balances.</p>
        </div>
    @endif
</div>
@endsection
