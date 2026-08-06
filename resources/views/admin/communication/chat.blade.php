@extends('layouts.app')

@section('content')
<div class="flex flex-col h-[calc(100vh-8rem)]" id="chatApp" data-conversation-id="{{ $conversation->id }}" data-last-message-id="{{ $messages->first()?->id ?? 0 }}" data-current-user-id="{{ auth()->id() }}">

    <!-- Chat Header -->
    <div class="flex items-center gap-3 px-4 py-3 rounded-2xl border border-slate-800 bg-slate-900/80 mb-3">
        <a href="{{ route('admin.communication.index') }}" class="p-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white text-sm font-medium flex-shrink-0">
            @if($conversation->type === 'direct' && $otherParticipant)
                {{ strtoupper(substr($otherParticipant->first_name ?? 'U', 0, 1)) }}
            @else
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <h2 class="text-sm font-semibold text-slate-50 truncate">{{ $conversation->display_name }}</h2>
            <p class="text-xs text-slate-400" id="chatStatus">
                @if($conversation->type === 'group')
                    {{ $conversation->participants->count() }} members
                @else
                    {{ $otherParticipant->first_name ?? '' }} {{ $otherParticipant->last_name ?? '' }}
                @endif
            </p>
        </div>
        <div id="typingIndicator" class="hidden text-xs text-slate-400 italic">typing...</div>
    </div>

    <!-- Messages Area -->
    <div class="flex-1 overflow-y-auto rounded-2xl border border-slate-800 bg-slate-900/80 p-4 space-y-3" id="messagesContainer">
        @if($messages->count() > 0)
            @php $lastDate = null; @endphp
            @foreach($messages->reverse() as $message)
                @php
                    $messageDate = $message->created_at->format('Y-m-d');
                    $isMe = $message->sender_id === auth()->id();
                @endphp

                @if($lastDate !== $messageDate)
                    <div class="flex items-center justify-center my-4">
                        <div class="px-3 py-1 rounded-full bg-slate-800 text-[10px] text-slate-400 font-medium">
                            {{ $message->created_at->format('d M Y') }}
                        </div>
                    </div>
                    @php $lastDate = $messageDate; @endphp
                @endif

                <div class="flex {{ $isMe ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $message->id }}">
                    <div class="max-w-[75%] {{ $isMe ? 'order-2' : '' }}">
                        @if(!$isMe && $conversation->type === 'group')
                            <p class="text-[10px] text-indigo-400 font-medium mb-1 ml-1">{{ $message->sender->first_name ?? 'Unknown' }}</p>
                        @endif
                        <div class="{{ $isMe ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-100' }} rounded-2xl {{ $isMe ? 'rounded-br-md' : 'rounded-bl-md' }} px-4 py-2.5 shadow-lg">
                            @if($message->type === 'image' && $message->file_path)
                                <div class="mb-2">
                                    <img src="{{ Storage::url($message->file_path) }}" alt="Image" class="rounded-lg max-w-full max-h-64 cursor-pointer hover:opacity-90 transition" onclick="window.open('{{ Storage::url($message->file_path) }}', '_blank')">
                                </div>
                            @endif

                            @if($message->type === 'file' && $message->file_path)
                                <div class="flex items-center gap-3 p-2 rounded-lg {{ $isMe ? 'bg-indigo-700/50' : 'bg-slate-700/50' }} mb-1">
                                    <div class="w-10 h-10 rounded-lg {{ $isMe ? 'bg-indigo-500/30' : 'bg-indigo-500/20' }} flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium truncate">{{ basename($message->file_path) }}</p>
                                    </div>
                                    <a href="{{ Storage::url($message->file_path) }}" target="_blank" class="p-1 rounded hover:bg-white/10 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </a>
                                </div>
                            @endif

                            @if($message->content && ($message->type === 'text' || $message->type === 'file' || $message->type === 'image'))
                                <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $message->content }}</p>
                            @endif

                            <div class="flex items-center justify-end gap-1.5 mt-1">
                                <span class="text-[10px] {{ $isMe ? 'text-indigo-200' : 'text-slate-500' }}">{{ $message->created_at->format('H:i') }}</span>
                                @if($isMe)
                                    @if($message->readReceipts->where('user_id', '!=', auth()->id())->count() > 0)
                                        <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @else
                                        <svg class="w-3.5 h-3.5 {{ $isMe ? 'text-indigo-300' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="flex flex-col items-center justify-center h-full text-center">
                <div class="w-16 h-16 rounded-2xl bg-indigo-500/20 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-slate-200 mb-1">Start the conversation</h3>
                <p class="text-sm text-slate-400">Send a message to begin chatting</p>
            </div>
        @endif
    </div>

    <!-- File Drop Zone (hidden by default) -->
    <div id="dropZone" class="hidden rounded-2xl border-2 border-dashed border-indigo-500 bg-indigo-500/10 p-8 text-center mb-3 transition">
        <svg class="w-10 h-10 mx-auto text-indigo-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        <p class="text-sm text-slate-300">Drop files here to share</p>
        <p class="text-xs text-slate-500 mt-1">Max 10MB - PDF, DOC, images</p>
    </div>

    <!-- File Preview (hidden by default) -->
    <div id="filePreview" class="hidden rounded-2xl border border-slate-800 bg-slate-900/80 px-4 py-3 mb-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-indigo-500/20 flex items-center justify-center flex-shrink-0" id="filePreviewIcon">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-200 truncate" id="filePreviewName"></p>
                <p class="text-xs text-slate-400" id="filePreviewSize"></p>
            </div>
            <button onclick="clearFileSelection()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Message Input -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-3">
        <form id="messageForm" class="flex items-end gap-3">
            <div class="flex gap-1">
                <button type="button" onclick="document.getElementById('fileInput').click()" class="p-2 rounded-lg text-slate-400 hover:text-indigo-400 hover:bg-slate-800 transition" title="Attach file">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                </button>
                <input type="file" id="fileInput" class="hidden" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.jpg,.jpeg,.png,.gif,.webp" onchange="handleFileSelect(this)">
            </div>
            <div class="flex-1 relative">
                <textarea id="messageInput" rows="1" placeholder="Type a message..." maxlength="2000"
                    class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none max-h-32"
                    onkeydown="handleKeyDown(event)" oninput="autoResize(this)"></textarea>
            </div>
            <button type="submit" id="sendBtn" class="p-2.5 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const chatApp = document.getElementById('chatApp');
    const conversationId = chatApp.dataset.conversationId;
    const currentUserId = parseInt(chatApp.dataset.currentUserId);
    let lastMessageId = parseInt(chatApp.dataset.lastMessageId);
    const pollUrl = '{{ route("admin.communication.poll", $conversation) }}';
    const sendUrl = '{{ route("admin.communication.message.send", $conversation) }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const messagesContainer = document.getElementById('messagesContainer');
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const dropZone = document.getElementById('dropZone');
    const filePreview = document.getElementById('filePreview');
    const fileInput = document.getElementById('fileInput');
    let selectedFile = null;
    let isSending = false;

    scrollToBottom();
    messageInput.focus();

    // Enable/disable send button
    messageInput.addEventListener('input', function() {
        sendBtn.disabled = !this.value.trim() && !selectedFile;
    });

    // Form submit
    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        sendMessage();
    });

    // Enter to send (Shift+Enter for new line)
    window.handleKeyDown = function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    };

    window.autoResize = function(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 128) + 'px';
    };

    function sendMessage() {
        if (isSending) return;
        const content = messageInput.value.trim();
        if (!content && !selectedFile) return;

        isSending = true;
        sendBtn.disabled = true;

        const formData = new FormData();
        if (content) formData.append('content', content);
        if (selectedFile) {
            formData.append('file', selectedFile);
            formData.append('type', 'file');
        }

        fetch(sendUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                appendMessage(data.message);
                lastMessageId = data.message.id;
                messageInput.value = '';
                messageInput.style.height = 'auto';
                clearFileSelection();
                scrollToBottom();
            }
        })
        .catch(err => {
            console.error('Send failed:', err);
        })
        .finally(() => {
            isSending = false;
            sendBtn.disabled = !messageInput.value.trim() && !selectedFile;
        });
    }

    function appendMessage(msg) {
        const existing = document.querySelector('[data-message-id="' + msg.id + '"]');
        if (existing) return;

        const isMe = msg.sender_id === currentUserId;
        const time = new Date(msg.created_at).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });

        let contentHtml = '';
        if (msg.type === 'image' && msg.file_path) {
            contentHtml += '<div class="mb-2"><img src="/storage/' + msg.file_path + '" alt="Image" class="rounded-lg max-w-full max-h-64 cursor-pointer hover:opacity-90 transition" onclick="window.open(\'/storage/' + msg.file_path + '\', \'_blank\')"></div>';
        }
        if (msg.type === 'file' && msg.file_path) {
            const fileName = msg.file_path.split('/').pop();
            contentHtml += '<div class="flex items-center gap-3 p-2 rounded-lg ' + (isMe ? 'bg-indigo-700/50' : 'bg-slate-700/50') + ' mb-1">';
            contentHtml += '<div class="w-10 h-10 rounded-lg bg-indigo-500/30 flex items-center justify-center flex-shrink-0"><svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg></div>';
            contentHtml += '<div class="flex-1 min-w-0"><p class="text-xs font-medium truncate">' + escHtml(fileName) + '</p></div>';
            contentHtml += '<a href="/storage/' + msg.file_path + '" target="_blank" class="p-1 rounded hover:bg-white/10 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg></a>';
            contentHtml += '</div>';
        }
        if (msg.content) {
            contentHtml += '<p class="text-sm leading-relaxed whitespace-pre-wrap">' + escHtml(msg.content) + '</p>';
        }

        const html = '<div class="flex ' + (isMe ? 'justify-end' : 'justify-start') + '" data-message-id="' + msg.id + '">'
            + '<div class="max-w-[75%]">'
            + '<div class="' + (isMe ? 'bg-indigo-600 text-white rounded-br-md' : 'bg-slate-800 text-slate-100 rounded-bl-md') + ' rounded-2xl px-4 py-2.5 shadow-lg">'
            + contentHtml
            + '<div class="flex items-center justify-end gap-1.5 mt-1">'
            + '<span class="text-[10px] ' + (isMe ? 'text-indigo-200' : 'text-slate-500') + '">' + time + '</span>'
            + (isMe ? '<svg class="w-3.5 h-3.5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>' : '')
            + '</div></div></div></div>';

        // Check if we need a date separator
        const msgDate = new Date(msg.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        const lastDateSep = messagesContainer.querySelector('.date-separator:last-of-type');
        if (!lastDateSep || lastDateSep.dataset.date !== msgDate) {
            const sep = document.createElement('div');
            sep.className = 'flex items-center justify-center my-4 date-separator';
            sep.dataset.date = msgDate;
            sep.innerHTML = '<div class="px-3 py-1 rounded-full bg-slate-800 text-[10px] text-slate-400 font-medium">' + msgDate + '</div>';
            messagesContainer.appendChild(sep);
        }

        messagesContainer.insertAdjacentHTML('beforeend', html);
        scrollToBottom();
    }

    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function escHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    // File handling
    window.handleFileSelect = function(input) {
        if (input.files && input.files[0]) {
            showFilePreview(input.files[0]);
        }
    };

    function showFilePreview(file) {
        selectedFile = file;
        document.getElementById('filePreviewName').textContent = file.name;
        document.getElementById('filePreviewSize').textContent = formatFileSize(file.size);
        filePreview.classList.remove('hidden');
        dropZone.classList.add('hidden');
        sendBtn.disabled = false;
    }

    window.clearFileSelection = function() {
        selectedFile = null;
        fileInput.value = '';
        filePreview.classList.add('hidden');
        sendBtn.disabled = !messageInput.value.trim();
    };

    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    // Drag and drop
    let dragCounter = 0;
    messagesContainer.addEventListener('dragenter', function(e) {
        e.preventDefault();
        dragCounter++;
        dropZone.classList.remove('hidden');
    });
    messagesContainer.addEventListener('dragleave', function(e) {
        e.preventDefault();
        dragCounter--;
        if (dragCounter === 0) dropZone.classList.add('hidden');
    });
    messagesContainer.addEventListener('dragover', function(e) { e.preventDefault(); });
    messagesContainer.addEventListener('drop', function(e) {
        e.preventDefault();
        dragCounter = 0;
        dropZone.classList.add('hidden');
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            showFilePreview(e.dataTransfer.files[0]);
        }
    });

    // AJAX Polling for new messages
    let pollInterval = null;

    function startPolling() {
        pollInterval = setInterval(pollForMessages, 4000);
    }

    function pollForMessages() {
        fetch(pollUrl + '?after=' + lastMessageId, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                data.messages.forEach(function(msg) {
                    if (msg.sender_id !== currentUserId) {
                        appendMessage(msg);
                        lastMessageId = Math.max(lastMessageId, msg.id);
                        showBrowserNotification(msg);
                    } else {
                        appendMessage(msg);
                        lastMessageId = Math.max(lastMessageId, msg.id);
                    }
                });
            }
        })
        .catch(err => console.error('Poll error:', err));
    }

    // Browser notifications
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }

    function showBrowserNotification(msg) {
        if ('Notification' in window && Notification.permission === 'granted') {
            const title = msg.sender ? (msg.sender.first_name + ' ' + msg.sender.last_name) : 'New Message';
            const body = msg.type === 'image' ? '📷 Image' : msg.type === 'file' ? '📎 File' : msg.content;
            new Notification(title, {
                body: body,
                icon: '/favicon.ico',
                tag: 'chat-' + msg.id
            });
        }
    }

    startPolling();

    // Cleanup on page leave
    window.addEventListener('beforeunload', function() {
        if (pollInterval) clearInterval(pollInterval);
    });
})();
</script>
@endpush
@endsection
