@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Communication</h1>
            <p class="text-xs text-slate-400 mt-1">Chat, SMS, and Email messaging system.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="openNewConversationModal()" class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-indigo-500 to-violet-600 px-4 py-1.5 text-xs font-medium text-white hover:from-indigo-600 hover:to-violet-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                New Chat
            </button>
            <a href="{{ route('admin.communication.sms') }}" class="inline-flex items-center rounded-full bg-green-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-green-600 transition">
                SMS
            </a>
            <a href="{{ route('admin.communication.email') }}" class="inline-flex items-center rounded-full bg-blue-500 px-4 py-1.5 text-xs font-medium text-white hover:bg-blue-600 transition">
                Email
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Conversations List -->
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80">
                <div class="p-4 border-b border-slate-800">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-medium text-slate-50">Chats</h2>
                        @if($unreadCount > 0)
                            <span id="unreadBadge" class="inline-flex items-center rounded-full bg-red-500 px-2 py-0.5 text-xs font-medium text-white">
                                {{ $unreadCount }}
                            </span>
                        @else
                            <span id="unreadBadge" class="hidden inline-flex items-center rounded-full bg-red-500 px-2 py-0.5 text-xs font-medium text-white">0</span>
                        @endif
                    </div>
                </div>

                <div class="max-h-[600px] overflow-y-auto" id="conversationList">
                    @if($conversations->count())
                        @foreach($conversations as $conversation)
                            <a href="{{ route('admin.communication.chat', $conversation) }}"
                               class="block p-4 hover:bg-slate-800/50 transition border-b border-slate-800/50 last:border-b-0 conversation-item"
                               data-conversation-id="{{ $conversation->id }}">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 relative">
                                        <div class="w-11 h-11 rounded-full {{ $conversation->type === 'group' ? 'bg-violet-500' : 'bg-indigo-500' }} flex items-center justify-center text-white text-sm font-medium">
                                            @if($conversation->type === 'group')
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            @else
                                                {{ strtoupper(substr($conversation->display_name, 0, 1)) }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <p class="text-sm font-medium text-slate-50 truncate">{{ $conversation->display_name }}</p>
                                            @if($conversation->latest_message)
                                                <span class="text-[10px] text-slate-500 flex-shrink-0 ml-2">{{ $conversation->latest_message->created_at->diffForHumans() }}</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center justify-between mt-1">
                                            <p class="text-xs text-slate-400 truncate">
                                                @if($conversation->latest_message)
                                                    @if($conversation->latest_message->type === 'image')
                                                        📷 Image
                                                    @elseif($conversation->latest_message->type === 'file')
                                                        📎 File
                                                    @else
                                                        {{ $conversation->latest_message->content }}
                                                    @endif
                                                @else
                                                    No messages yet
                                                @endif
                                            </p>
                                            @if($conversation->unread_count > 0)
                                                <span class="flex-shrink-0 ml-2 inline-flex items-center justify-center rounded-full bg-red-500 min-w-[20px] h-5 px-1.5 text-[10px] font-bold text-white unread-badge" data-conv-badge="{{ $conversation->id }}">
                                                    {{ $conversation->unread_count }}
                                                </span>
                                            @endif
                                        </div>
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
                            <p class="text-xs text-slate-500 mt-1">Click "New Chat" to start</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quick Actions & Stats -->
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <h2 class="text-sm font-medium text-slate-50 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button onclick="openNewConversationModal()"
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

            <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
                <h2 class="text-sm font-medium text-slate-50 mb-4">Activity</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-indigo-400">{{ $conversations->count() }}</div>
                        <p class="text-xs text-slate-400 mt-1">Conversations</p>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-400" id="totalUnread">{{ $unreadCount }}</div>
                        <p class="text-xs text-slate-400 mt-1">Unread Messages</p>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-400">Active</div>
                        <p class="text-xs text-slate-400 mt-1">Status</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Conversation Modal -->
    <div id="newConversationModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm" onclick="if(event.target === this) closeNewConversationModal()">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="relative bg-slate-900 rounded-2xl border border-slate-700 max-w-md w-full p-6 z-10">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-slate-50">Start New Conversation</h3>
                    <button type="button" onclick="closeNewConversationModal()" class="p-1 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form action="{{ route('admin.conversation.create') }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">Conversation Type</label>
                            <select name="type" onchange="toggleGroupName(this.value)" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="direct">Direct Message</option>
                                <option value="group">Group Chat</option>
                            </select>
                        </div>

                        <div id="groupNameField" class="hidden">
                            <label class="block text-xs font-medium text-slate-300 mb-2">Group Name</label>
                            <input type="text" name="name" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Enter group name">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-2">Search for people</label>
                            <div class="relative">
                                <input type="text" id="userSearch"
                                       class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                       placeholder="Type a name or email...">
                                <div id="searchResults" class="absolute z-20 w-full mt-1 bg-slate-800 border border-slate-700 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden"></div>
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

    function openNewConversationModal() {
        document.getElementById('newConversationModal').classList.remove('hidden');
        document.getElementById('userSearch').focus();
    }

    function closeNewConversationModal() {
        document.getElementById('newConversationModal').classList.add('hidden');
        selectedUsers = [];
        updateSelectedUsers();
        document.getElementById('userSearch').value = '';
        document.getElementById('searchResults').classList.add('hidden');
    }

    function toggleGroupName(value) {
        document.getElementById('groupNameField').classList.toggle('hidden', value !== 'group');
    }

    document.getElementById('userSearch').addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length < 2) {
            document.getElementById('searchResults').classList.add('hidden');
            return;
        }

        fetch('{{ route("admin.communication.search-users") }}?q=' + encodeURIComponent(query))
            .then(r => r.json())
            .then(users => {
                const div = document.getElementById('searchResults');
                div.innerHTML = '';
                users.forEach(user => {
                    if (!selectedUsers.find(u => u.id === user.id)) {
                        const item = document.createElement('div');
                        item.className = 'px-3 py-2 hover:bg-slate-700 cursor-pointer text-sm text-slate-100 border-b border-slate-700/50 last:border-b-0';
                        item.innerHTML = '<div class="font-medium">' + user.name + '</div><div class="text-xs text-slate-400">' + user.email + '</div>';
                        item.onclick = () => selectUser(user);
                        div.appendChild(item);
                    }
                });
                div.classList.remove('hidden');
            });
    });

    function selectUser(user) {
        selectedUsers.push(user);
        updateSelectedUsers();
        document.getElementById('userSearch').value = '';
        document.getElementById('searchResults').classList.add('hidden');
    }

    function removeUser(userId) {
        selectedUsers = selectedUsers.filter(u => u.id !== userId);
        updateSelectedUsers();
    }

    function updateSelectedUsers() {
        const container = document.getElementById('selectedUsers');
        const input = document.getElementById('participantsInput');
        container.innerHTML = '';
        input.value = selectedUsers.map(u => u.id).join(',');
        selectedUsers.forEach(user => {
            container.innerHTML += '<span class="inline-flex items-center gap-1 bg-indigo-500/20 text-indigo-400 px-2 py-1 rounded-lg text-xs font-medium">' + user.name + '<button type="button" onclick="removeUser(' + user.id + ')" class="ml-0.5 hover:text-indigo-200">&times;</button></span>';
        });
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#userSearch') && !e.target.closest('#searchResults')) {
            document.getElementById('searchResults').classList.add('hidden');
        }
    });

    // Poll for unread counts
    setInterval(function() {
        fetch('{{ route("admin.communication.unread-count") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
        })
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('unreadBadge');
            const total = document.getElementById('totalUnread');
            if (data.count > 0) {
                badge.textContent = data.count;
                badge.classList.remove('hidden');
                if (total) total.textContent = data.count;
                document.title = '(' + data.count + ') Communication - School ERP';
            } else {
                badge.classList.add('hidden');
                if (total) total.textContent = '0';
                document.title = 'Communication - School ERP';
            }
            // Update per-conversation badges
            document.querySelectorAll('.conversation-item').forEach(function(item) {
                const convId = item.dataset.conversationId;
                const existingBadge = item.querySelector('.unread-badge');
                if (data.per_conversation[convId]) {
                    if (existingBadge) {
                        existingBadge.textContent = data.per_conversation[convId];
                    } else {
                        const badgeHtml = '<span class="flex-shrink-0 ml-2 inline-flex items-center justify-center rounded-full bg-red-500 min-w-[20px] h-5 px-1.5 text-[10px] font-bold text-white unread-badge">' + data.per_conversation[convId] + '</span>';
                        item.querySelector('.items-center.justify-between').insertAdjacentHTML('beforeend', badgeHtml);
                    }
                } else if (existingBadge) {
                    existingBadge.remove();
                }
            });
        })
        .catch(() => {});
    }, 5000);
</script>
@endpush
