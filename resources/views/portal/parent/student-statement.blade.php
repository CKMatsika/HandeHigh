@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Student Statement</h1>
            <p class="text-gray-600 mt-1">Transaction history for {{ $student->first_name }} {{ $student->last_name }}</p>
        </div>
        <div class="flex gap-2">
            <form method="GET" action="{{ route('parent.students.statement', $student) }}" class="inline">
                @csrf
                <input type="hidden" name="academic_year" value="{{ $academicYear }}">
                @if($term)
                    <input type="hidden" name="term" value="{{ $term }}">
                @endif
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                    📥 Download PDF
                </button>
            </form>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('parent.students.statement', $student) }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Academic Year</label>
                <select name="academic_year" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ $academicYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Term</label>
                <select name="term" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Terms</option>
                    @foreach($terms as $termOption)
                        <option value="{{ $termOption }}" {{ $term == $termOption ? 'selected' : '' }}>{{ $termOption }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition">
                    Apply Filters
                </button>
            </div>
            <div class="flex items-end">
                <a href="{{ route('parent.dashboard') }}" class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium text-center transition">
                    Back to Dashboard
                </a>
            </div>
        </form>
    </div>

    <!-- Student Info -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <span class="text-sm font-medium text-gray-500">Student:</span>
                <p class="text-lg font-semibold">{{ $student->first_name }} {{ $student->last_name }}</p>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-500">Grade/Class:</span>
                <p class="text-lg font-semibold">{{ $student->grade }} {{ $student->class_name }}</p>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-500">Period:</span>
                <p class="text-lg font-semibold">{{ $academicYear }} {{ $term ? '- ' . $term : ' (All Terms)' }}</p>
            </div>
        </div>
    </div>

    <!-- Balance Summary -->
    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-md p-6 mb-6 text-white">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-lg font-semibold mb-2">Opening Balance</h3>
                <p class="text-3xl font-bold">{{ number_format($openingBalance, 2) }}</p>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-2">Closing Balance</h3>
                <p class="text-3xl font-bold">{{ number_format($closingBalance, 2) }}</p>
                @if($closingBalance > 0)
                    <span class="inline-block mt-2 bg-yellow-400 text-yellow-900 px-3 py-1 rounded-full text-xs font-medium">Amount Due</span>
                @elseif($closingBalance < 0)
                    <span class="inline-block mt-2 bg-green-400 text-green-900 px-3 py-1 rounded-full text-xs font-medium">Credit Balance</span>
                @else
                    <span class="inline-block mt-2 bg-green-400 text-green-900 px-3 py-1 rounded-full text-xs font-medium">Paid Up</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @if($openingBalance != 0)
                        <tr class="bg-gray-50 font-medium">
                            <td colspan="3" class="px-6 py-4">Opening Balance (Brought Forward)</td>
                            <td class="px-6 py-4 text-right font-medium">{{ number_format($openingBalance, 2) }}</td>
                            <td class="px-6 py-4 text-right"></td>
                            <td class="px-6 py-4 text-right font-medium">{{ number_format($openingBalance, 2) }}</td>
                        </tr>
                    @endif
                    @forelse($events as $event)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $event['date']->format('M j, Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $event['reference'] }}</td>
                            <td class="px-6 py-4 text-sm">{{ $event['description'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">{{ $event['debit'] ? number_format($event['debit'], 2) : '' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">{{ $event['credit'] ? number_format($event['credit'], 2) : '' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium">{{ number_format($event['balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-lg font-medium">No transactions found</p>
                                    <p class="text-sm mt-1">No activity found for the selected period.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08.402-2.599 1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Invoices</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $events->where('type', 'invoice')->count() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Payments</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $events->where('type', 'payment')->count() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M15 7h6m0 10v-3m-3 3h.01M15 17h.01M10 3v4m0 4h.01M6 7h6m0 10v-3m-3 3h.01M6 17h.01" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Net Balance</p>
                    <p class="text-2xl font-bold {{ $closingBalance > 0 ? 'text-red-600' : ($closingBalance < 0 ? 'text-green-600' : 'text-gray-900') }}">
                        {{ number_format($closingBalance, 2) }}
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
