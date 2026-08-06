<div id="aiResourceFinder" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" onclick="closeAiFinder()"></div>
    
    <div class="fixed inset-4 md:inset-8 lg:inset-16 bg-slate-900 rounded-2xl border border-slate-700 flex flex-col overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-slate-50">AI Resource Finder</h2>
                    <p class="text-xs text-slate-400">Zimbabwe Curriculum Aligned Resources</p>
                </div>
            </div>
            <button onclick="closeAiFinder()" class="p-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <div class="px-6 py-4 bg-slate-800/50 border-b border-slate-800">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Level</label>
                    <select id="ai-level" onchange="onAiLevelChange()" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="primary">Primary (Grade 1-7)</option>
                        <option value="secondary" selected>Secondary (Form 1-4)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Grade/Form</label>
                    <select id="ai-grade" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"></select>
                </div>
                <div>
                    <label class="block text-xs text-slate-400 mb-1">Subject</label>
                    <select id="ai-subject" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="english">English</option>
                        <option value="mathematics">Mathematics</option>
                        <option value="science">Science</option>
                        <option value="history">History</option>
                        <option value="geography">Geography</option>
                        <option value="shona">ChiShona</option>
                        <option value="ndebele">IsiNdebele</option>
                        <option value="ict">ICT</option>
                        <option value="physical_education">Physical Education</option>
                        <option value="environmental_science">Environmental Science</option>
                        <option value="social_science">Social Science</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button id="ai-search-btn" onclick="doAiSearch()" class="w-full bg-gradient-to-r from-violet-500 to-indigo-600 text-white rounded-lg px-4 py-2 text-sm font-medium hover:from-violet-600 hover:to-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="ai-search-text">Search Resources</span>
                        <span id="ai-search-loading" class="hidden flex items-center justify-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Searching...
                        </span>
                    </button>
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-xs text-slate-400 mb-1">Topic (what are you teaching?)</label>
                <input type="text" id="ai-topic" placeholder="e.g. Introduction to Algebra, Photosynthesis, The Colonial Period..." class="w-full bg-slate-800 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 placeholder-slate-500">
            </div>
        </div>
        
        <div id="ai-results-area" class="flex-1 overflow-y-auto">
            <div id="ai-empty-state" class="flex flex-col items-center justify-center h-full text-center p-8">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-violet-500/20 to-indigo-500/20 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-slate-200 mb-2">Find Resources for Your Lesson</h3>
                <p class="text-sm text-slate-400 max-w-md">Select your level, grade, subject, and enter a topic to find Zimbabwe curriculum-aligned textbooks, revision materials, and online resources.</p>
            </div>
            
            <div id="ai-loading-state" class="hidden flex flex-col items-center justify-center h-full">
                <div class="relative">
                    <div class="w-12 h-12 rounded-full border-4 border-slate-700 border-t-violet-500 animate-spin"></div>
                </div>
                <p class="mt-4 text-sm text-slate-400">Searching for resources...</p>
                <p class="text-xs text-slate-500 mt-1">This may take a moment</p>
            </div>
            
            <div id="ai-results-content" class="hidden p-6"></div>
        </div>
        
        <div id="ai-footer" class="hidden px-6 py-4 bg-slate-800 border-t border-slate-700">
            <div class="flex items-center justify-between mb-3">
                <span id="ai-selected-count" class="text-sm text-slate-300">0 resource(s) selected</span>
                <button onclick="aiClearSelection()" class="text-xs text-slate-400 hover:text-slate-200 transition">Clear All</button>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="aiAddToScheme()" class="flex-1 bg-gradient-to-r from-emerald-500 to-green-600 text-white rounded-lg px-4 py-2 text-sm font-medium hover:from-emerald-600 hover:to-green-700 transition">
                    Add to Scheme of Work
                </button>
                <button onclick="closeAiFinder()" class="px-4 py-2 rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800 transition text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const aiState = {
        selectedResources: [],
        results: { set_books: [], web_resources: { web: [], videos: [], papers: [], notes: [] } }
    };

    const gradeOptions = {
        primary: ['grade_1','grade_2','grade_3','grade_4','grade_5','grade_6','grade_7'],
        secondary: ['form_1','form_2','form_3','form_4']
    };

    window.openAiFinder = function() {
        document.getElementById('aiResourceFinder').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };

    window.closeAiFinder = function() {
        document.getElementById('aiResourceFinder').classList.add('hidden');
        document.body.style.overflow = '';
    };

    window.onAiLevelChange = function() {
        const level = document.getElementById('ai-level').value;
        const gradeSelect = document.getElementById('ai-grade');
        gradeSelect.innerHTML = '';
        (gradeOptions[level] || []).forEach(function(g) {
            const opt = document.createElement('option');
            opt.value = g;
            opt.textContent = g.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
            gradeSelect.appendChild(opt);
        });
    };

    window.doAiSearch = function() {
        const topic = document.getElementById('ai-topic').value.trim();
        if (!topic) return;

        document.getElementById('ai-empty-state').classList.add('hidden');
        document.getElementById('ai-loading-state').classList.remove('hidden');
        document.getElementById('ai-results-content').classList.add('hidden');
        document.getElementById('ai-search-text').classList.add('hidden');
        document.getElementById('ai-search-loading').classList.remove('hidden');
        document.getElementById('ai-search-btn').disabled = true;

        const payload = {
            subject: document.getElementById('ai-subject').value,
            topic: topic,
            grade: document.getElementById('ai-grade').value,
            level: document.getElementById('ai-level').value
        };

        fetch('{{ route("teacher.ai-resources.search") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                aiState.results = data.data;
                aiRenderResults(data.data);
            }
        })
        .catch(function(err) {
            console.error('Search failed:', err);
            document.getElementById('ai-results-content').innerHTML = '<div class="text-center py-12"><p class="text-red-400">Search failed. Please try again.</p></div>';
            document.getElementById('ai-results-content').classList.remove('hidden');
        })
        .finally(function() {
            document.getElementById('ai-loading-state').classList.add('hidden');
            document.getElementById('ai-search-text').classList.remove('hidden');
            document.getElementById('ai-search-loading').classList.add('hidden');
            document.getElementById('ai-search-btn').disabled = false;
        });
    };

    function aiRenderResults(data) {
        const container = document.getElementById('ai-results-content');
        let html = '';

        if (data.set_books.length === 0 && data.web_resources.web.length === 0 && data.web_resources.videos.length === 0 && data.web_resources.papers.length === 0 && data.web_resources.notes.length === 0) {
            html = '<div class="text-center py-12"><svg class="w-12 h-12 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><h3 class="text-lg font-medium text-slate-300 mb-2">No Results Found</h3><p class="text-sm text-slate-400">Try different keywords or check your selection.</p></div>';
            container.innerHTML = html;
            container.classList.remove('hidden');
            return;
        }

        if (data.set_books.length > 0) {
            html += '<div class="mb-8"><div class="flex items-center gap-2 mb-4"><span class="text-lg">\uD83D\uDCDA</span><h3 class="text-sm font-semibold text-slate-200">Textbooks (Zimbabwe Curriculum)</h3><span class="px-2 py-0.5 text-xs rounded-full bg-emerald-500/20 text-emerald-400">' + data.set_books.length + '</span></div><div class="grid grid-cols-1 md:grid-cols-2 gap-3">';
            data.set_books.forEach(function(r, i) {
                html += '<div class="bg-slate-800/50 rounded-xl border border-slate-700 p-4 hover:border-emerald-500/50 transition"><div class="flex items-start justify-between"><div class="flex-1"><p class="text-sm font-medium text-slate-200">' + escHtml(r.title) + '</p><p class="text-xs text-emerald-400 mt-1">' + escHtml(r.source || '') + '</p><p class="text-xs text-slate-400 mt-1">' + escHtml(r.description || '') + '</p></div><button onclick=\'aiSelectResource(' + JSON.stringify(escAttr(JSON.stringify(r))) + ')\' class="ml-3 p-2 rounded-lg bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30 transition" title="Add to Scheme"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg></button></div></div>';
            });
            html += '</div></div>';
        }

        var cats = [
            { key: 'web', label: 'Web Resources', emoji: '\uD83C\uDF10', color: 'blue' },
            { key: 'videos', label: 'Videos & Tutorials', emoji: '\uD83C\uDFAC', color: 'red' },
            { key: 'papers', label: 'Past Papers & Exams', emoji: '\uD83D\uDCDD', color: 'amber' },
            { key: 'notes', label: 'Revision Notes & Guides', emoji: '\uD83D\uDCD6', color: 'purple' }
        ];

        cats.forEach(function(cat) {
            var items = data.web_resources[cat.key] || [];
            if (items.length === 0) return;
            html += '<div class="mb-8"><div class="flex items-center gap-2 mb-4"><span class="text-lg">' + cat.emoji + '</span><h3 class="text-sm font-semibold text-slate-200">' + cat.label + '</h3><span class="px-2 py-0.5 text-xs rounded-full bg-' + cat.color + '-500/20 text-' + cat.color + '-400">' + items.length + '</span></div><div class="space-y-3">';
            items.forEach(function(r) {
                var displayTitle = r.title + (r.domain ? ' (' + r.domain + ')' : '');
                html += '<div class="bg-slate-800/50 rounded-xl border border-slate-700 p-4 hover:border-' + cat.color + '-500/50 transition"><div class="flex items-start justify-between"><div class="flex-1"><a href="' + escAttr(r.url) + '" target="_blank" class="text-sm font-medium text-' + cat.color + '-400 hover:text-' + cat.color + '-300 transition">' + escHtml(r.title) + '</a>';
                if (r.domain) html += '<p class="text-xs text-slate-500 mt-1">' + escHtml(r.domain) + '</p>';
                if (r.snippet) html += '<p class="text-xs text-slate-400 mt-1">' + escHtml(r.snippet) + '</p>';
                html += '</div><button onclick=\'aiSelectResource(' + JSON.stringify(escAttr(JSON.stringify({title: displayTitle, url: r.url, type: r.type, category: r.category}))) + ')\' class="ml-3 p-2 rounded-lg bg-' + cat.color + '-500/20 text-' + cat.color + '-400 hover:bg-' + cat.color + '-500/30 transition" title="Add to Scheme"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg></button></div></div>';
            });
            html += '</div></div>';
        });

        container.innerHTML = html;
        container.classList.remove('hidden');
    }

    window.aiSelectResource = function(jsonStr) {
        var resource = JSON.parse(jsonStr);
        var exists = aiState.selectedResources.find(function(r) { return r.title === resource.title; });
        if (!exists) {
            aiState.selectedResources.push(resource);
        }
        aiUpdateFooter();
    };

    window.aiClearSelection = function() {
        aiState.selectedResources = [];
        aiUpdateFooter();
    };

    window.aiUpdateFooter = function() {
        var footer = document.getElementById('ai-footer');
        var count = document.getElementById('ai-selected-count');
        if (aiState.selectedResources.length > 0) {
            footer.classList.remove('hidden');
            count.textContent = aiState.selectedResources.length + ' resource(s) selected';
        } else {
            footer.classList.add('hidden');
        }
    };

    window.aiAddToScheme = function() {
        var text = aiState.selectedResources.map(function(r) {
            return r.title + (r.url ? ' - ' + r.url : '');
        }).join('\n');

        document.dispatchEvent(new CustomEvent('resources-selected', { detail: { resources: text } }));
        aiState.selectedResources = [];
        aiUpdateFooter();
        closeAiFinder();
    };

    function escHtml(str) {
        var div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function escAttr(str) {
        return str.replace(/&/g, '&amp;').replace(/'/g, '&#39;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    document.addEventListener('DOMContentLoaded', function() {
        window.onAiLevelChange();
    });
})();
</script>
