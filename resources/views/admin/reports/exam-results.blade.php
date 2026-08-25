@extends('layouts.app')

@section('title', 'Examination Results Register - ' . $school->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Examination Results Register</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Formal examination marks ledger and grade sheets for {{ $school->name }}.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button onclick="window.print()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Ledger
            </button>
            <a href="{{ route('admin.reports.exam-results.export', request()->query()) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium shadow-sm transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export Excel
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <form method="GET" action="{{ route('admin.reports.exam-results') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Academic Year</label>
                <input type="text" name="academic_year" value="{{ request('academic_year') }}" placeholder="e.g. 2026" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Term</label>
                <select name="term" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
                    <option value="">All Terms</option>
                    <option value="Term 1" {{ request('term') === 'Term 1' ? 'selected' : '' }}>Term 1</option>
                    <option value="Term 2" {{ request('term') === 'Term 2' ? 'selected' : '' }}>Term 2</option>
                    <option value="Term 3" {{ request('term') === 'Term 3' ? 'selected' : '' }}>Term 3</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Class</label>
                <select name="class_id" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
                    <option value="">All Classes</option>
                    @foreach($available_classes as $c)
                        <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Subject</label>
                <select name="subject_id" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
                    <option value="">All Subjects</option>
                    @foreach($available_subjects as $sub)
                        <option value="{{ $sub->id }}" {{ request('subject_id') == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Grade</label>
                <select name="grade" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg">
                    <option value="">All Grades</option>
                    <option value="A" {{ request('grade') === 'A' ? 'selected' : '' }}>A (75%+)</option>
                    <option value="B" {{ request('grade') === 'B' ? 'selected' : '' }}>B (65-74%)</option>
                    <option value="C" {{ request('grade') === 'C' ? 'selected' : '' }}>C (50-64%)</option>
                    <option value="D" {{ request('grade') === 'D' ? 'selected' : '' }}>D (40-49%)</option>
                    <option value="E" {{ request('grade') === 'E' ? 'selected' : '' }}>E (30-39%)</option>
                    <option value="U" {{ request('grade') === 'U' ? 'selected' : '' }}>U (&lt;30%)</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">Filter</button>
                <a href="{{ route('admin.reports.exam-results') }}" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-600 dark:text-gray-300 rounded-lg text-sm">Reset</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 overflow-hidden space-y-4">
        <x-documents.school-header 
            :school="$school ?? null"
            title="Official Examination Results Register"
            :subtitle="(request('academic_year') ? 'Year: ' . request('academic_year') : 'All Academic Years') . (request('term') ? ' · ' . request('term') : '')"
            reference="EXAM-LEDGER-{{ date('Ymd') }}"
            :date="now()"
        />

        <div class="px-2 py-2 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Exam Results Entries ({{ number_format($total_results) }} Total)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs font-semibold text-gray-500 uppercase border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-4 py-3">Academic Period</th>
                        <th class="px-4 py-3">Adm #</th>
                        <th class="px-4 py-3">Student Name</th>
                        <th class="px-4 py-3">Class</th>
                        <th class="px-4 py-3">Subject</th>
                        <th class="px-4 py-3 text-right">Score</th>
                        <th class="px-4 py-3 text-right">Max</th>
                        <th class="px-4 py-3 text-right">Percentage</th>
                        <th class="px-4 py-3 text-center">Grade</th>
                        <th class="px-4 py-3">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($rows as $r)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $r['academic_year'] }} &bull; {{ $r['term'] }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $r['admission_number'] }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $r['student_name'] }}</td>
                        <td class="px-4 py-3 font-medium text-indigo-600 dark:text-indigo-400">{{ $r['class_name'] }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $r['subject_name'] }} <span class="text-xs text-gray-400">({{ $r['subject_code'] }})</span></td>
                        <td class="px-4 py-3 text-right text-gray-900 dark:text-white font-medium">{{ $r['score'] }}</td>
                        <td class="px-4 py-3 text-right text-gray-500">{{ $r['max_score'] }}</td>
                        <td class="px-4 py-3 text-right font-bold text-indigo-600 dark:text-indigo-400">{{ $r['percentage'] }}%</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 text-xs font-bold rounded-md {{ in_array($r['grade'], ['A', 'B', 'C']) ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' }}">{{ $r['grade'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">{{ $r['remarks'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-5 py-6 text-center text-gray-500 text-sm">No exam results match the selected criteria.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($paginator)
        <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $paginator->links() }}
        </div>
        @endif

        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
            <x-documents.school-footer :school="$school ?? null" />
        </div>
    </div>
</div>
@endsection
