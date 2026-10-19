<!-- Leadership Reports Drilldown Modal -->
<div id="leadership-drilldown-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity" onclick="LeadershipDrilldown.close()"></div>

    <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4 bg-slate-900/90">
                <div class="flex items-center gap-3">
                    <div id="drilldown-modal-icon-container" class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-50" id="drilldown-modal-title">Source Drilldown</h3>
                        <p class="text-xs text-slate-400" id="drilldown-modal-subtitle">Inspecting source records</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a id="drilldown-modal-source-btn" href="#" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-cyan-400 bg-cyan-500/10 hover:bg-cyan-500/20 rounded-lg transition border border-cyan-500/20">
                        <span>Open Source Page</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                    </a>
                    <button type="button" onclick="LeadershipDrilldown.close()" class="rounded-lg p-1.5 text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Search & Filter Bar -->
            <div class="border-b border-slate-800/80 px-6 py-3 bg-slate-950/40 flex items-center justify-between gap-4">
                <div class="relative flex-1">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" id="drilldown-search-input" onkeyup="LeadershipDrilldown.filter(this.value)" placeholder="Filter results by name, ID, or details..." class="w-full bg-slate-900 border border-slate-800 rounded-lg pl-9 pr-4 py-1.5 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
                <div class="text-xs text-slate-400 shrink-0">
                    Showing <span id="drilldown-visible-count" class="font-semibold text-slate-200">0</span> of <span id="drilldown-total-count" class="font-semibold text-slate-200">0</span> records
                </div>
            </div>

            <!-- Modal Content Table -->
            <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
                <div id="drilldown-loading" class="py-12 text-center hidden">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"></div>
                    <p class="mt-2 text-xs text-slate-400">Loading source records...</p>
                </div>

                <div id="drilldown-empty" class="py-12 text-center hidden">
                    <svg class="w-12 h-12 text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <p class="text-sm font-medium text-slate-300">No records found matching this criterion</p>
                    <p class="text-xs text-slate-500 mt-1">Try adjusting the filter or search term</p>
                </div>

                <div id="drilldown-table-container" class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead id="drilldown-table-head" class="text-slate-400 bg-slate-950/60 uppercase text-[10px] tracking-wider border-b border-slate-800">
                            <!-- Populated dynamically -->
                        </thead>
                        <tbody id="drilldown-table-body" class="divide-y divide-slate-800/60">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="border-t border-slate-800 px-6 py-3 bg-slate-900/60 flex items-center justify-between">
                <span class="text-xs text-slate-500">Press <kbd class="px-1.5 py-0.5 text-[10px] bg-slate-800 rounded border border-slate-700 text-slate-300">Esc</kbd> or click backdrop to close</span>
                <button type="button" onclick="LeadershipDrilldown.close()" class="px-4 py-1.5 text-xs font-medium text-slate-300 bg-slate-800 hover:bg-slate-700 rounded-lg transition">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
window.LeadershipDrilldown = (function() {
    let currentRecords = [];
    let currentType = 'students';
    let currentParams = {};
    const modal = document.getElementById('leadership-drilldown-modal');
    const titleEl = document.getElementById('drilldown-modal-title');
    const subtitleEl = document.getElementById('drilldown-modal-subtitle');
    const sourceBtn = document.getElementById('drilldown-modal-source-btn');
    const searchInput = document.getElementById('drilldown-search-input');
    const visibleCountEl = document.getElementById('drilldown-visible-count');
    const totalCountEl = document.getElementById('drilldown-total-count');
    const thead = document.getElementById('drilldown-table-head');
    const tbody = document.getElementById('drilldown-table-body');
    const loadingEl = document.getElementById('drilldown-loading');
    const emptyEl = document.getElementById('drilldown-empty');
    const tableContainer = document.getElementById('drilldown-table-container');

    function open(type, params, title, subtitle, preloadedRecords = null, defaultSourceUrl = '#') {
        currentType = type;
        currentParams = params || {};
        currentRecords = preloadedRecords || [];
        
        titleEl.textContent = title || 'Source Drilldown';
        subtitleEl.textContent = subtitle || 'Underlying records';
        sourceBtn.href = defaultSourceUrl || '#';
        searchInput.value = '';
        
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        if (currentRecords && currentRecords.length > 0) {
            loadingEl.classList.add('hidden');
            render(currentRecords);
        } else {
            fetchDrilldown(type, params);
        }
    }

    function close() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    function fetchDrilldown(type, params) {
        loadingEl.classList.remove('hidden');
        emptyEl.classList.add('hidden');
        tableContainer.classList.add('hidden');

        const query = new URLSearchParams({ type, ...params });
        fetch(`{{ route('admin.dashboard.drilldown') }}?${query.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(res => res.json())
        .then(data => {
            loadingEl.classList.add('hidden');
            if (data.source_url) {
                sourceBtn.href = data.source_url;
            }
            currentRecords = data.records || [];
            render(currentRecords);
        })
        .catch(err => {
            loadingEl.classList.add('hidden');
            console.error('Error fetching drilldown data:', err);
            emptyEl.classList.remove('hidden');
        });
    }

    function filter(term) {
        term = (term || '').toLowerCase().trim();
        if (!term) {
            render(currentRecords);
            return;
        }

        const filtered = currentRecords.filter(item => {
            return Object.values(item).some(val => 
                val !== null && val !== undefined && String(val).toLowerCase().includes(term)
            );
        });

        render(filtered, false);
    }

    function render(records, updateTotals = true) {
        if (updateTotals) {
            totalCountEl.textContent = currentRecords.length;
        }
        visibleCountEl.textContent = records.length;

        if (!records || records.length === 0) {
            tableContainer.classList.add('hidden');
            emptyEl.classList.remove('hidden');
            return;
        }

        emptyEl.classList.add('hidden');
        tableContainer.classList.remove('hidden');

        // Render header
        renderHeader(currentType);

        // Render rows
        renderRows(records, currentType);
    }

    function renderHeader(type) {
        let headers = '';
        if (type === 'students') {
            headers = `
                <tr>
                    <th class="px-4 py-3">Student Name</th>
                    <th class="px-4 py-3">Adm / Reg No</th>
                    <th class="px-4 py-3">Class & Stream</th>
                    <th class="px-4 py-3 text-center">Gender</th>
                    <th class="px-4 py-3 text-center">Type</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            `;
        } else if (type === 'teachers') {
            headers = `
                <tr>
                    <th class="px-4 py-3">Teacher Name</th>
                    <th class="px-4 py-3">Employee ID</th>
                    <th class="px-4 py-3">Specialization</th>
                    <th class="px-4 py-3">Qualification</th>
                    <th class="px-4 py-3 text-center">Gender</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            `;
        } else if (type === 'employees') {
            headers = `
                <tr>
                    <th class="px-4 py-3">Staff Name</th>
                    <th class="px-4 py-3">Employee ID</th>
                    <th class="px-4 py-3">Department</th>
                    <th class="px-4 py-3">Position</th>
                    <th class="px-4 py-3 text-center">Type</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            `;
        } else if (type === 'bank_account') {
            headers = `
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Reference</th>
                    <th class="px-4 py-3">Description</th>
                    <th class="px-4 py-3 text-center">Type</th>
                    <th class="px-4 py-3 text-right">Amount</th>
                    <th class="px-4 py-3 text-right">Balance After</th>
                </tr>
            `;
        }
        thead.innerHTML = headers;
    }

    function renderRows(records, type) {
        let html = '';
        records.forEach(r => {
            if (type === 'students') {
                const genderBadge = r.gender && r.gender.toLowerCase() === 'male'
                    ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-500/20 text-blue-400">♂ Male</span>'
                    : '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-pink-500/20 text-pink-400">♀ Female</span>';
                
                const typeBadge = r.is_boarding
                    ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-500/20 text-indigo-400">Boarder</span>'
                    : '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-700/60 text-slate-300">Day Scholar</span>';

                const statusBadge = r.status && r.status.toLowerCase() === 'active'
                    ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/20 text-emerald-400">Active</span>'
                    : `<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-800 text-slate-400">${r.status || 'Inactive'}</span>`;

                html += `
                    <tr class="hover:bg-slate-800/40 transition">
                        <td class="px-4 py-3 font-semibold text-slate-100">${r.name || 'Student'}</td>
                        <td class="px-4 py-3 font-mono text-slate-400">${r.admission_number || 'N/A'}</td>
                        <td class="px-4 py-3 text-slate-300">${r.grade || ''} ${r.stream ? ' • ' + r.stream : ''}</td>
                        <td class="px-4 py-3 text-center">${genderBadge}</td>
                        <td class="px-4 py-3 text-center">${typeBadge}</td>
                        <td class="px-4 py-3 text-center">${statusBadge}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="${r.view_url}" class="inline-flex items-center gap-1 text-cyan-400 hover:text-cyan-300 font-medium">
                                <span>Profile</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </td>
                    </tr>
                `;
            } else if (type === 'teachers') {
                const statusBadge = r.status === 'Active'
                    ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/20 text-emerald-400">Active</span>'
                    : '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-800 text-slate-400">Inactive</span>';

                html += `
                    <tr class="hover:bg-slate-800/40 transition">
                        <td class="px-4 py-3 font-semibold text-slate-100">${r.name || 'Teacher'}</td>
                        <td class="px-4 py-3 font-mono text-slate-400">${r.employee_id || 'N/A'}</td>
                        <td class="px-4 py-3 text-slate-300"><span class="px-2 py-0.5 rounded bg-slate-800 text-slate-200">${r.specialization || 'General'}</span></td>
                        <td class="px-4 py-3 text-slate-400">${r.qualification || 'N/A'}</td>
                        <td class="px-4 py-3 text-center">${r.gender || 'N/A'}</td>
                        <td class="px-4 py-3 text-center">${statusBadge}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="${r.view_url}" class="inline-flex items-center gap-1 text-cyan-400 hover:text-cyan-300 font-medium">
                                <span>Profile</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </td>
                    </tr>
                `;
            } else if (type === 'employees') {
                const statusBadge = r.employment_status === 'Active'
                    ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/20 text-emerald-400">Active</span>'
                    : `<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-500/20 text-amber-400">${r.employment_status || 'Staff'}</span>`;

                html += `
                    <tr class="hover:bg-slate-800/40 transition">
                        <td class="px-4 py-3 font-semibold text-slate-100">${r.name || 'Staff Member'}</td>
                        <td class="px-4 py-3 font-mono text-slate-400">${r.employee_id || 'N/A'}</td>
                        <td class="px-4 py-3 text-slate-300"><span class="px-2 py-0.5 rounded bg-slate-800 text-slate-200">${r.department_name || 'Administration'}</span></td>
                        <td class="px-4 py-3 text-slate-300">${r.position || 'Staff'}</td>
                        <td class="px-4 py-3 text-center text-slate-400">${r.employment_type || 'Full Time'}</td>
                        <td class="px-4 py-3 text-center">${statusBadge}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="${r.view_url}" class="inline-flex items-center gap-1 text-cyan-400 hover:text-cyan-300 font-medium">
                                <span>Profile</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </td>
                    </tr>
                `;
            } else if (type === 'bank_account') {
                const isIncome = (r.type || '').toLowerCase() === 'income';
                const typeBadge = isIncome
                    ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/20 text-emerald-400">Income</span>'
                    : '<span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-500/20 text-rose-400">Expense</span>';

                html += `
                    <tr class="hover:bg-slate-800/40 transition">
                        <td class="px-4 py-3 text-slate-400">${r.date || ''}</td>
                        <td class="px-4 py-3 font-mono text-slate-300">${r.reference || 'N/A'}</td>
                        <td class="px-4 py-3 font-medium text-slate-200">${r.description || 'Transaction'}</td>
                        <td class="px-4 py-3 text-center">${typeBadge}</td>
                        <td class="px-4 py-3 text-right font-bold ${isIncome ? 'text-emerald-400' : 'text-rose-400'}">${isIncome ? '+' : '-'}$${r.amount}</td>
                        <td class="px-4 py-3 text-right font-mono text-slate-300">$${r.balance_after}</td>
                    </tr>
                `;
            }
        });
        tbody.innerHTML = html;
    }

    // Keyboard listener for Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            close();
        }
    });

    return {
        open,
        close,
        filter
    };
})();
</script>
