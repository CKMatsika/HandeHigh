@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Communication</h1>
            <p class="text-xs text-slate-400 mt-1">Chat, SMS, and Email messaging system.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.communication.sms') }}" class="inline-flex items-center rounded-full bg-green-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-green-600 transition">
                Send SMS
            </a>
            <a href="{{ route('admin.communication.email') }}" class="inline-flex items-center rounded-full bg-blue-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-blue-600 transition">
                Send Email
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Conversations List -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80">
                <div class="p-4 border-b border-slate-800">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-medium text-slate-50">Conversations</h2>
                        @if($unreadCount > 0)
                            <span class="inline-flex items-center rounded-full bg-red-500 px-2 py-1 text-xs font-medium text-white">
                                {{ $unreadCount }} unread
                            </span>
                        @endif
                    </div>
                </div>
                
                <div class="max-h-96 overflow-y-auto">
                    @if($conversations->count())
                        @foreach($conversations as $conversation)
                            <a href="{{ route('admin.communication.chat', $conversation) }}" 
                               class="block p-4 hover:bg-slate-800/50 transition border-b border-slate-800/50 last:border-b-0">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white text-sm font-medium">
                                            {{ $conversation->display_name[0] }}
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-medium text-slate-50 truncate">
                                                {{ $conversation->display_name }}
                                            </p>
                                            @if($conversation->unread_count > 0)
                                                <span class="inline-flex items-center rounded-full bg-red-500 w-2 h-2"></span>
                                            @endif
                                        </div>
                                        @if($conversation->messages->first())
                                            <p class="text-xs text-slate-400 truncate mt-1">
                                                {{ $conversation->messages->first()->sender->name }}: {{ $conversation->messages->first()->content }}
                                            </p>
                                            <p class="text-xs text-slate-500 mt-1">
                                                {{ $conversation->messages->first()->created_at->diffForHumans() }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    @else
                        <div class="p-8 text-center">
                            <div class="w-16 h-16 mx-auto bg-slate-800 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                            </div>
                            <p class="text-sm text-slate-400">No conversations yet</p>
                            <p class="text-xs text-slate-500 mt-1">Start a new conversation to begin chatting</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quick Actions & Stats -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Quick Actions -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <h2 class="text-sm font-medium text-slate-50 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button onclick="console.log('Button clicked'); openNewConversationModal();" 
                            class="p-4 rounded-xl border border-slate-700 bg-slate-800/50 hover:bg-slate-800 transition text-center">
                        <div class="w-12 h-12 mx-auto bg-indigo-500 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-slate-50">New Chat</p>
                        <p class="text-xs text-slate-400 mt-1">Start conversation</p>
                    </button>
                    
                    <a href="{{ route('admin.communication.sms') }}" 
                       class="block p-4 rounded-xl border border-slate-700 bg-slate-800/50 hover:bg-slate-800 transition text-center">
                        <div class="w-12 h-12 mx-auto bg-green-500 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-slate-50">Send SMS</p>
                        <p class="text-xs text-slate-400 mt-1">Text messages</p>
                    </a>
                    
                    <a href="{{ route('admin.communication.email') }}" 
                       class="block p-4 rounded-xl border border-slate-700 bg-slate-800/50 hover:bg-slate-800 transition text-center">
                        <div class="w-12 h-12 mx-auto bg-blue-500 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-slate-50">Send Email</p>
                        <p class="text-xs text-slate-400 mt-1">Email messages</p>
                    </a>
                </div>
            </div>

            <!-- Communication Stats -->
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <h2 class="text-sm font-medium text-slate-50 mb-4">Today's Activity</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-indigo-400">{{ $conversations->count() }}</div>
                        <p class="text-xs text-slate-400 mt-1">Total Conversations</p>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-400">{{ $unreadCount }}</div>
                        <p class="text-xs text-slate-400 mt-1">Unread Messages</p>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-400">0</div>
                        <p class="text-xs text-slate-400 mt-1">Messages Sent Today</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Conversation Modal -->
    <div id="newConversationModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50" onclick="if(event.target === this) closeNewConversationModal()">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="relative bg-slate-900 rounded-2xl border border-slate-700 max-w-md w-full p-6 z-10">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-slate-50">Start New Conversation</h3>
                    <button type="button" onclick="closeNewConversationModal()" class="text-slate-400 hover:text-slate-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form action="/admin/conversation/create" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">Conversation Type</label>
                            <select name="type" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="direct">Direct Message</option>
                                <option value="group">Group Chat</option>
                            </select>
                        </div>
                        
                        <div id="groupNameField" class="hidden">
                            <label class="block text-xs font-medium text-slate-300 mb-2">Group Name</label>
                            <input type="text" name="name" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Enter group name">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">Participants</label>
                            <div class="relative">
                                <input type="text" id="userSearch" 
                                       class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                                       placeholder="Search for users...">
                                <div id="searchResults" class="absolute z-10 w-full mt-1 bg-slate-800 border border-slate-700 rounded-lg shadow-lg max-h-40 overflow-y-auto hidden"></div>
                            </div>
                            <div id="selectedUsers" class="mt-2 flex flex-wrap gap-2"></div>
                            <input type="hidden" name="participants" id="participantsInput">
                        </div>
                    </div>
                    
                    <div class="flex gap-3 mt-6">
                        <button type="submit" class="flex-1 bg-indigo-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-indigo-600 transition">
                            Start Conversation
                        </button>
                        <button type="button" onclick="closeNewConversationModal()" class="flex-1 bg-slate-700 text-slate-300 rounded-lg px-4 py-2 text-sm font-medium hover:bg-slate-600 transition">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let selectedUsers = [];
    let userSearchListenerAdded = false;
    let typeChangeListenerAdded = false;

    function openNewConversationModal() {
        console.log('Opening new conversation modal...');
        const modal = document.getElementById('newConversationModal');
        if (modal) {
            modal.classList.remove('hidden');
            console.log('Modal found and shown');
            
            // Add event listeners only when modal is opened
            if (!typeChangeListenerAdded) {
                const typeSelect = document.querySelector('select[name="type"]');
                if (typeSelect) {
                    typeSelect.addEventListener('change', handleTypeChange);
                    typeChangeListenerAdded = true;
                }
            }
            
            if (!userSearchListenerAdded) {
                const userSearchInput = document.getElementById('userSearch');
                if (userSearchInput) {
                    userSearchInput.addEventListener('input', handleUserSearch);
                    userSearchListenerAdded = true;
                }
            }
        } else {
            console.error('Modal not found!');
        }
    }

    function closeNewConversationModal() {
        document.getElementById('newConversationModal').classList.add('hidden');
        selectedUsers = [];
        updateSelectedUsers();
        const userSearch = document.getElementById('userSearch');
        if (userSearch) {
            userSearch.value = '';
        }
        const searchResults = document.getElementById('searchResults');
        if (searchResults) {
            searchResults.classList.add('hidden');
        }
    }

    function handleTypeChange() {
        const groupNameField = document.getElementById('groupNameField');
        const typeSelect = document.querySelector('select[name="type"]');
        if (typeSelect && groupNameField) {
            if (typeSelect.value === 'group') {
                groupNameField.classList.remove('hidden');
            } else {
                groupNameField.classList.add('hidden');
            }
        }
    }

    function handleUserSearch() {
        const userSearch = document.getElementById('userSearch');
        if (!userSearch) return;
        
        const query = userSearch.value.trim();
        
        if (query.length < 2) {
            const searchResults = document.getElementById('searchResults');
            if (searchResults) {
                searchResults.classList.add('hidden');
            }
            return;
        }

        fetch(`/admin/communication/search-users?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(users => {
                const resultsDiv = document.getElementById('searchResults');
                if (!resultsDiv) return;
                
                resultsDiv.innerHTML = '';
                
                users.forEach(user => {
                    if (!selectedUsers.find(u => u.id === user.id)) {
                        const userDiv = document.createElement('div');
                        userDiv.className = 'px-3 py-2 hover:bg-slate-700 cursor-pointer text-sm text-slate-100';
                        userDiv.innerHTML = `${user.first_name} ${user.last_name} (${user.email})`;
                        userDiv.onclick = () => selectUser(user);
                        resultsDiv.appendChild(userDiv);
                    }
                });
                
                resultsDiv.classList.remove('hidden');
            })
            .catch(error => console.error('Error searching users:', error));
    }

    function selectUser(user) {
        selectedUsers.push(user);
        updateSelectedUsers();
        const userSearch = document.getElementById('userSearch');
        if (userSearch) {
            userSearch.value = '';
        }
        const searchResults = document.getElementById('searchResults');
        if (searchResults) {
            searchResults.classList.add('hidden');
        }
    }

    function removeUser(userId) {
        selectedUsers = selectedUsers.filter(u => u.id !== userId);
        updateSelectedUsers();
    }

    function updateSelectedUsers() {
        const container = document.getElementById('selectedUsers');
        const input = document.getElementById('participantsInput');
        
        if (!container || !input) return;
        
        container.innerHTML = '';
        input.value = selectedUsers.map(u => u.id).join(',');
        
        selectedUsers.forEach(user => {
            const userTag = document.createElement('div');
            userTag.className = 'inline-flex items-center gap-1 bg-indigo-500/20 text-indigo-400 px-2 py-1 rounded text-xs';
            userTag.innerHTML = `
                ${user.first_name} ${user.last_name}
                <button type="button" onclick="removeUser(${user.id})" class="ml-1 hover:text-indigo-300">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            container.appendChild(userTag);
        });
    }

    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#userSearch') && !e.target.closest('#searchResults')) {
            const searchResults = document.getElementById('searchResults');
            if (searchResults) {
                searchResults.classList.add('hidden');
            }
        }
    });
</script>
@endpush
