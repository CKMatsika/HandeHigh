@extends('layouts.app')

@section('title', 'Accounting Periods')

@section('content')
<div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('admin.accounts.index') }}" class="hover:text-blue-600">Finance</a>
                <span>/</span>
                <span>Accounting Periods</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Accounting Periods & Period Locking</h1>
            <p class="text-sm text-gray-600">Enforce SME financial period locks, prevent backdated transaction postings, and audit period closures.</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="document.getElementById('newPeriodModal').classList.remove('hidden')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium text-sm transition-colors shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Accounting Period
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm flex items-center gap-2">
            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm space-y-1">
            <div class="font-bold flex items-center gap-1">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                Please fix the following errors:
            </div>
            <ul class="list-disc list-inside text-xs">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Current Period Status Card -->
    <div class="bg-gradient-to-r from-blue-900 to-indigo-900 text-white rounded-xl p-6 shadow-md flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <span class="text-xs uppercase tracking-wider font-semibold text-blue-200">Current Operational Period</span>
            @if($currentPeriod)
                <h2 class="text-xl font-bold mt-1 flex items-center gap-3">
                    {{ $currentPeriod->name }}
                    <span class="px-2.5 py-0.5 text-xs rounded-full {{ $currentPeriod->isClosed() ? 'bg-red-500 text-white' : 'bg-green-500 text-white' }}">
                        {{ strtoupper($currentPeriod->status) }}
                    </span>
                </h2>
                <p class="text-xs text-blue-200 mt-1">
                    {{ $currentPeriod->start_date->format('d M Y') }} – {{ $currentPeriod->end_date->format('d M Y') }} ({{ ucfirst($currentPeriod->period_type) }})
                </p>
            @else
                <h2 class="text-xl font-bold mt-1 text-yellow-300">No Active Accounting Period Set for Today</h2>
                <p class="text-xs text-blue-200 mt-1">Create an accounting period covering today's date to establish strict period boundary locks.</p>
            @endif
        </div>
        <div class="text-right text-xs text-blue-200">
            <div>Tenant: <span class="font-semibold text-white">{{ $school->name }}</span></div>
            <div>Double-Entry Standard: <span class="font-semibold text-white">ZIMSEC / IFRS SME</span></div>
        </div>
    </div>

    <!-- Periods Table -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-800">All Defined Accounting Periods</h3>
            <span class="text-xs text-gray-500">{{ $periods->total() }} total periods</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold border-b border-gray-100">
                    <tr>
                        <th class="p-4">Period Name</th>
                        <th class="p-4">Type</th>
                        <th class="p-4">Date Range</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Closing / Reopening Details</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($periods as $p)
                        <tr class="hover:bg-gray-50 transition-colors {{ $p->isClosed() ? 'bg-gray-50/50' : '' }}">
                            <td class="p-4 font-semibold text-gray-900">
                                {{ $p->name }}
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs rounded font-medium">
                                    {{ ucfirst($p->period_type) }}
                                </span>
                            </td>
                            <td class="p-4 text-xs">
                                <span class="font-mono">{{ $p->start_date->format('d M Y') }}</span>
                                <span class="text-gray-400 mx-1">to</span>
                                <span class="font-mono">{{ $p->end_date->format('d M Y') }}</span>
                            </td>
                            <td class="p-4">
                                @if($p->isOpen())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <span class="w-1.5 h-1.5 mr-1.5 bg-green-500 rounded-full"></span>
                                        Open
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <span class="w-1.5 h-1.5 mr-1.5 bg-red-500 rounded-full"></span>
                                        Locked / Closed
                                    </span>
                                @endif
                            </td>
                            <td class="p-4 text-xs text-gray-500">
                                @if($p->isClosed())
                                    <div>Closed by: <span class="font-medium text-gray-700">{{ $p->closedByUser?->name ?? 'System Admin' }}</span></div>
                                    <div class="text-[11px] text-gray-400">{{ $p->closed_at?->format('d M Y H:i') }}</div>
                                    @if($p->closing_notes)
                                        <div class="text-[11px] italic text-gray-500 truncate max-w-xs" title="{{ $p->closing_notes }}">"{{ $p->closing_notes }}"</div>
                                    @endif
                                @elseif($p->reopened_at)
                                    <div>Reopened by: <span class="font-medium text-gray-700">{{ $p->reopenedByUser?->name ?? 'Admin' }}</span></div>
                                    <div class="text-[11px] text-gray-400">{{ $p->reopened_at->format('d M Y H:i') }}</div>
                                    <div class="text-[11px] italic text-blue-600 truncate max-w-xs" title="{{ $p->reopening_notes }}">Reason: "{{ $p->reopening_notes }}"</div>
                                @else
                                    <span class="text-gray-400">Never closed</span>
                                @endif
                            </td>
                            <td class="p-4 text-right space-x-2">
                                @if($p->isOpen())
                                    <button onclick="openCloseModal({{ $p->id }}, '{{ addslashes($p->name) }}')" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs font-medium transition-colors shadow-sm">
                                        Close & Lock
                                    </button>
                                @else
                                    <button onclick="openReopenModal({{ $p->id }}, '{{ addslashes($p->name) }}')" class="px-3 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded text-xs font-medium transition-colors shadow-sm">
                                        Reopen Period
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-400">
                                No accounting periods defined yet. Click "New Accounting Period" above to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($periods->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $periods->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal: New Period -->
<div id="newPeriodModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-gray-100">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100">
            <h3 class="font-bold text-gray-900">Create Accounting Period</h3>
            <button onclick="document.getElementById('newPeriodModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form action="{{ route('admin.accounting-periods.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Period Name</label>
                <input type="text" name="name" required placeholder="e.g. August 2026" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Period Type</label>
                <select name="period_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="monthly">Monthly</option>
                    <option value="term">Term</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="annual">Annual</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Start Date</label>
                    <input type="date" name="start_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">End Date</label>
                    <input type="date" name="end_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('newPeriodModal').classList.add('hidden')" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">Create Period</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Close Period -->
<div id="closePeriodModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-lg w-full p-6 shadow-xl border border-gray-100">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100">
            <h3 class="font-bold text-red-700 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Close & Lock Accounting Period
            </h3>
            <button onclick="document.getElementById('closePeriodModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <div class="mb-4 text-sm text-gray-600">
            Closing period <strong id="closePeriodNameText" class="text-gray-900"></strong> will prevent all users from creating, modifying, or reversing transactions with dates in this period.
        </div>
        <div id="tbCheckStatus" class="p-3 bg-gray-50 rounded-lg text-xs mb-4 border border-gray-200">
            Checking trial balance balance...
        </div>
        <form id="closePeriodForm" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Closing Notes / Audit Sign-Off</label>
                <textarea name="closing_notes" rows="3" placeholder="e.g. Month-end bank reconciliations complete and approved by Bursar." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:outline-none"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('closePeriodModal').classList.add('hidden')" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="closePeriodSubmitBtn" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium">Confirm & Close Period</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Reopen Period -->
<div id="reopenPeriodModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-lg w-full p-6 shadow-xl border border-gray-100">
        <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100">
            <h3 class="font-bold text-amber-700 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Reopen Accounting Period
            </h3>
            <button onclick="document.getElementById('reopenPeriodModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <div class="mb-4 text-sm text-gray-600">
            Reopening period <strong id="reopenPeriodNameText" class="text-gray-900"></strong> requires a mandatory reason. This event will be logged in the permanent audit trail.
        </div>
        <form id="reopenPeriodForm" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Mandatory Reopening Reason <span class="text-red-500">*</span></label>
                <textarea name="reason" required rows="3" placeholder="e.g. Audit correction requested by external auditors for account adjustment." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-4 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('reopenPeriodModal').classList.add('hidden')" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium">Reopen Period</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCloseModal(id, name) {
    document.getElementById('closePeriodNameText').textContent = name;
    document.getElementById('closePeriodForm').action = '/admin/accounting-periods/' + id + '/close';
    const statusDiv = document.getElementById('tbCheckStatus');
    statusDiv.innerHTML = '<span class="text-gray-500">Checking GL trial balance integrity...</span>';
    document.getElementById('closePeriodSubmitBtn').disabled = false;
    document.getElementById('closePeriodModal').classList.remove('hidden');

    fetch('/admin/accounting-periods/' + id + '/validate-trial-balance')
        .then(r => r.json())
        .then(data => {
            if (data.is_balanced) {
                statusDiv.className = 'p-3 bg-green-50 rounded-lg text-xs mb-4 border border-green-200 text-green-800';
                statusDiv.innerHTML = '<strong>✓ Trial Balance in Equilibrium</strong>: Total Debits $' + data.debit_total.toLocaleString() + ' = Total Credits $' + data.credit_total.toLocaleString() + ' across ' + data.batch_count + ' posted batches.';
            } else {
                statusDiv.className = 'p-3 bg-red-50 rounded-lg text-xs mb-4 border border-red-200 text-red-800';
                statusDiv.innerHTML = '<strong>⚠ Out of Balance</strong>: Debits $' + data.debit_total + ' vs Credits $' + data.credit_total + ' (Difference: $' + data.difference + '). You cannot close an out-of-balance period.';
                document.getElementById('closePeriodSubmitBtn').disabled = true;
            }
        })
        .catch(err => {
            statusDiv.innerHTML = '<span class="text-gray-500">Could not check TB balance dynamically. Standard server validation will apply.</span>';
        });
}

function openReopenModal(id, name) {
    document.getElementById('reopenPeriodNameText').textContent = name;
    document.getElementById('reopenPeriodForm').action = '/admin/accounting-periods/' + id + '/reopen';
    document.getElementById('reopenPeriodModal').classList.remove('hidden');
}
</script>
@endsection
