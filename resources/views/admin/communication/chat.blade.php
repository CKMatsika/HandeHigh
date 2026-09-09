@extends('layouts.app')

@section('content')
<div class="flex flex-col h-[calc(100vh-8rem)]" id="chatApp" data-conversation-id="{{ $conversation->id }}" data-last-message-id="{{ $messages->first()?->id ?? 0 }}" data-current-user-id="{{ auth()->id() }}">

    <!-- Chat Header -->
    <div class="flex items-center justify-between gap-3 px-4 py-3 rounded-2xl border border-slate-800 bg-slate-900/80 mb-3 shadow-sm">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('admin.communication.index') }}" class="p-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center text-white text-sm font-medium flex-shrink-0">
                @if($conversation->type === 'direct' && $otherParticipant)
                    {{ strtoupper(substr($otherParticipant->name ?? 'U', 0, 1)) }}
                @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <h2 class="text-sm font-semibold text-slate-50 truncate flex items-center gap-2">
                    {{ $conversation->display_name }}
                    @if($conversation->type === 'group')
                        <span class="px-2 py-0.5 text-[10px] rounded bg-indigo-500/20 text-indigo-300 font-medium">GROUP</span>
                    @endif
                </h2>
                <p class="text-xs text-slate-400" id="chatStatus">
                    @if($conversation->type === 'group')
                        {{ $conversation->participants->count() }} members
                    @else
                        {{ $otherParticipant->name ?? '' }} ({{ $otherParticipant->email ?? '' }})
                    @endif
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <div id="typingIndicator" class="hidden text-xs text-indigo-400 italic mr-2">typing...</div>
            @if($conversation->type === 'group')
                <button onclick="openGroupInfoModal()" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Group Info
                </button>
            @endif
        </div>
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

                <div class="flex {{ $isMe ? 'justify-end' : 'justify-start' }} group" data-message-id="{{ $message->id }}">
                    <div class="max-w-[75%] {{ $isMe ? 'order-2' : '' }} relative">
                        @if(!$isMe && $conversation->type === 'group')
                            <p class="text-[10px] text-indigo-400 font-medium mb-1 ml-1">{{ $message->sender->name ?? 'Unknown' }}</p>
                        @endif

                        <div class="{{ $isMe ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-100' }} rounded-2xl {{ $isMe ? 'rounded-br-md' : 'rounded-bl-md' }} px-4 py-2.5 shadow-lg relative">
                            @if($message->type === 'image' && $message->file_path)
                                <div class="mb-2">
                                    <img src="{{ Storage::url($message->file_path) }}" alt="Image" class="rounded-lg max-w-full max-h-64 cursor-pointer hover:opacity-90 transition" onclick="window.open('{{ Storage::url($message->file_path) }}', '_blank')">
                                </div>
                            @endif

                            @if($message->type === 'voice' && $message->file_path)
                                <div class="flex items-center gap-3 p-2 rounded-lg {{ $isMe ? 'bg-indigo-700/50' : 'bg-slate-700/50' }} mb-1">
                                    <audio controls class="h-8 max-w-xs">
                                        <source src="{{ Storage::url($message->file_path) }}" type="audio/webm">
                                        Your browser does not support the audio element.
                                    </audio>
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

                        <!-- Reactions Container -->
                        <div class="flex flex-wrap gap-1 mt-1 {{ $isMe ? 'justify-end' : 'justify-start' }}" id="reactions-{{ $message->id }}">
                            @php
                                $groupedReactions = $message->reactions->groupBy('reaction');
                            @endphp
                            @foreach($groupedReactions as $emoji => $rxList)
                                <button onclick="toggleReaction({{ $message->id }}, '{{ $emoji }}')" class="px-2 py-0.5 rounded-full text-xs {{ $rxList->contains('user_id', auth()->id()) ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300' }} border border-slate-700 hover:scale-105 transition flex items-center gap-1 shadow-sm">
                                    <span>{{ $emoji }}</span>
                                    <span class="text-[10px] font-bold">{{ $rxList->count() }}</span>
                                </button>
                            @endforeach
                        </div>

                        <!-- Hover Reaction Quick Trigger -->
                        <div class="opacity-0 group-hover:opacity-100 transition absolute top-0 {{ $isMe ? '-left-28' : '-right-28' }} flex items-center gap-1 bg-slate-800 border border-slate-700 rounded-full px-2 py-1 shadow-md z-10">
                            @foreach(['👍', '❤️', '😂', '👏', '🙏'] as $em)
                                <button onclick="toggleReaction({{ $message->id }}, '{{ $em }}')" class="hover:scale-125 transition text-xs p-0.5">{{ $em }}</button>
                            @endforeach
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
                <p class="text-sm text-slate-400">Send a message, file, or voice note to begin chatting</p>
            </div>
        @endif
    </div>

    <!-- Voice Recording Live Bar (hidden by default) -->
    <div id="voiceRecordingBar" class="hidden rounded-2xl border border-red-500 bg-red-950/40 p-3 mb-3 flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-3">
            <span class="w-3 h-3 rounded-full bg-red-500 animate-ping"></span>
            <span class="text-sm font-semibold text-red-300">Recording Voice Note: <span id="recordTimer">0:00</span></span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="cancelVoiceRecording()" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-300 transition">Cancel</button>
            <button type="button" onclick="stopAndSendVoiceRecording()" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-red-600 hover:bg-red-700 text-white transition flex items-center gap-1.5 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                Send Voice Note
            </button>
        </div>
    </div>

    <!-- File Drop Zone (hidden by default) -->
    <div id="dropZone" class="hidden rounded-2xl border-2 border-dashed border-indigo-500 bg-indigo-500/10 p-8 text-center mb-3 transition">
        <svg class="w-10 h-10 mx-auto text-indigo-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
        </svg>
        <p class="text-sm text-slate-300">Drop files here to share</p>
        <p class="text-xs text-slate-500 mt-1">Max 15MB - PDF, DOC, images, audio</p>
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
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-3 shadow-sm">
        <form id="messageForm" class="flex items-end gap-2">
            <div class="flex gap-1">
                <button type="button" onclick="document.getElementById('fileInput').click()" class="p-2 rounded-lg text-slate-400 hover:text-indigo-400 hover:bg-slate-800 transition" title="Attach file">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                </button>
                <button type="button" id="micBtn" onclick="toggleVoiceRecording()" class="p-2 rounded-lg text-slate-400 hover:text-red-400 hover:bg-slate-800 transition" title="Record voice note">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
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

<!-- Group Info Modal -->
@if($conversation->type === 'group')
<div id="groupInfoModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 text-slate-100 rounded-2xl max-w-lg w-full p-6 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
            <h3 class="font-bold text-lg text-slate-50 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                {{ $conversation->name }}
            </h3>
            <button onclick="document.getElementById('groupInfoModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-200 text-xl">&times;</button>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-xs uppercase font-semibold text-slate-400 mb-1">Group Members ({{ $conversation->participants->count() }})</label>
                <div class="max-h-48 overflow-y-auto divide-y divide-slate-800 rounded-xl bg-slate-800/50 p-2">
                    @foreach($conversation->participants as $p)
                        <div class="py-2 px-2 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-medium text-slate-200">{{ $p->name }}</span>
                                <span class="text-[10px] text-slate-400 ml-1">({{ $p->email }})</span>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $p->pivot->role === 'admin' || $p->pivot->role === 'owner' ? 'bg-amber-500/20 text-amber-300' : 'bg-slate-700 text-slate-300' }}">
                                {{ strtoupper($p->pivot->role) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="pt-3 border-t border-slate-800 flex justify-between gap-2">
                <button type="button" onclick="leaveGroup()" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-red-600/20 text-red-300 border border-red-500/30 hover:bg-red-600/30 transition">
                    Leave Group
                </button>
                <button type="button" onclick="document.getElementById('groupInfoModal').classList.add('hidden')" class="px-4 py-1.5 rounded-lg text-xs font-medium bg-slate-800 text-slate-300 hover:bg-slate-700 transition">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
(function() {
    const chatApp = document.getElementById('chatApp');
    const conversationId = chatApp.dataset.conversationId;
    const currentUserId = parseInt(chatApp.dataset.currentUserId);
    let lastMessageId = parseInt(chatApp.dataset.lastMessageId);
    const pollUrl = '{{ route("admin.communication.poll", $conversation) }}';
    const sendUrl = '{{ route("admin.communication.message.send", $conversation) }}';
    const voiceUrl = '{{ route("admin.communication.voice-note", $conversation) }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const messagesContainer = document.getElementById('messagesContainer');
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const fileInput = document.getElementById('fileInput');
    const filePreview = document.getElementById('filePreview');
    const filePreviewName = document.getElementById('filePreviewName');
    const filePreviewSize = document.getElementById('filePreviewSize');
    let selectedFile = null;

    // Scroll to bottom
    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    scrollToBottom();

    window.autoResize = function(el) {
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 128) + 'px';
        updateSendBtn();
    };

    function updateSendBtn() {
        sendBtn.disabled = !messageInput.value.trim() && !selectedFile;
    }

    window.handleKeyDown = function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (!sendBtn.disabled) {
                messageForm.dispatchEvent(new Event('submit'));
            }
        }
    };

    window.handleFileSelect = function(input) {
        if (input.files && input.files[0]) {
            selectedFile = input.files[0];
            filePreviewName.textContent = selectedFile.name;
            filePreviewSize.textContent = (selectedFile.size / 1024).toFixed(1) + ' KB';
            filePreview.classList.remove('hidden');
            updateSendBtn();
        }
    };

    window.clearFileSelection = function() {
        selectedFile = null;
        fileInput.value = '';
        filePreview.classList.add('hidden');
        updateSendBtn();
    };

    // Voice Recorder
    let mediaRecorder = null;
    let audioChunks = [];
    let recordTimerInterval = null;
    let recordSeconds = 0;

    window.toggleVoiceRecording = async function() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Voice recording is not supported in this browser environment.');
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];

            mediaRecorder.ondataavailable = function(e) {
                if (e.data.size > 0) audioChunks.push(e.data);
            };

            mediaRecorder.start();
            recordSeconds = 0;
            document.getElementById('voiceRecordingBar').classList.remove('hidden');
            recordTimerInterval = setInterval(function() {
                recordSeconds++;
                const mins = Math.floor(recordSeconds / 60);
                const secs = recordSeconds % 60;
                document.getElementById('recordTimer').textContent = mins + ':' + (secs < 10 ? '0' : '') + secs;
            }, 1000);
        } catch (err) {
            console.error('Microphone access error:', err);
            alert('Microphone permission denied or unavailable.');
        }
    };

    window.cancelVoiceRecording = function() {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
        clearInterval(recordTimerInterval);
        document.getElementById('voiceRecordingBar').classList.add('hidden');
        audioChunks = [];
    };

    window.stopAndSendVoiceRecording = function() {
        if (!mediaRecorder) return;
        mediaRecorder.onstop = function() {
            clearInterval(recordTimerInterval);
            document.getElementById('voiceRecordingBar').classList.add('hidden');
            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
            const formData = new FormData();
            formData.append('audio', audioBlob, 'voice_recording.webm');
            formData.append('duration', recordSeconds);

            fetch(voiceUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.message) {
                    appendMessage(data.message);
                    lastMessageId = Math.max(lastMessageId, data.message.id);
                }
            })
            .catch(err => console.error('Voice send error:', err));
        };
        mediaRecorder.stop();
    };

    // Toggle Reactions
    window.toggleReaction = function(messageId, emoji) {
        fetch('/admin/communication/messages/' + messageId + '/react', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ reaction: emoji })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderReactions(messageId, data.reactions);
            }
        })
        .catch(err => console.error('Reaction error:', err));
    };

    function renderReactions(messageId, reactions) {
        const container = document.getElementById('reactions-' + messageId);
        if (!container) return;
        container.innerHTML = '';
        reactions.forEach(rx => {
            const btn = document.createElement('button');
            btn.onclick = () => window.toggleReaction(messageId, rx.emoji);
            btn.className = 'px-2 py-0.5 rounded-full text-xs ' + (rx.has_reacted ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-300') + ' border border-slate-700 hover:scale-105 transition flex items-center gap-1 shadow-sm';
            btn.innerHTML = '<span>' + rx.emoji + '</span> <span class="text-[10px] font-bold">' + rx.count + '</span>';
            container.appendChild(btn);
        });
    }

    // Modal controls
    window.openGroupInfoModal = function() {
        const m = document.getElementById('groupInfoModal');
        if (m) m.classList.remove('hidden');
    };

    window.leaveGroup = function() {
        if (confirm('Are you sure you want to leave this group?')) {
            fetch('/admin/communication/conversations/' + conversationId + '/members/' + currentUserId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                window.location.href = '{{ route("admin.communication.index") }}';
            });
        }
    };

    // Append Message DOM
    function appendMessage(msg) {
        if (document.querySelector('[data-message-id="' + msg.id + '"]')) return;

        const isMe = msg.sender_id === currentUserId;
        const div = document.createElement('div');
        div.className = 'flex ' + (isMe ? 'justify-end' : 'justify-start') + ' group';
        div.dataset.messageId = msg.id;

        let contentHtml = '';
        if (msg.type === 'image' && msg.file_path) {
            contentHtml += '<div class="mb-2"><img src="/storage/' + msg.file_path + '" class="rounded-lg max-w-full max-h-64 cursor-pointer" onclick="window.open(\'/storage/' + msg.file_path + '\', \'_blank\')"></div>';
        } else if (msg.type === 'voice' && msg.file_path) {
            contentHtml += '<div class="flex items-center gap-3 p-2 rounded-lg ' + (isMe ? 'bg-indigo-700/50' : 'bg-slate-700/50') + ' mb-1"><audio controls class="h-8 max-w-xs"><source src="/storage/' + msg.file_path + '" type="audio/webm"></audio></div>';
        } else if (msg.type === 'file' && msg.file_path) {
            contentHtml += '<div class="flex items-center gap-3 p-2 rounded-lg ' + (isMe ? 'bg-indigo-700/50' : 'bg-slate-700/50') + ' mb-1"><span class="text-xs truncate">' + (msg.content || 'File') + '</span> <a href="/storage/' + msg.file_path + '" target="_blank" class="p-1 text-white">📎</a></div>';
        }

        if (msg.content && msg.type !== 'voice') {
            const escaped = msg.content.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
            contentHtml += '<p class="text-sm leading-relaxed whitespace-pre-wrap">' + escaped + '</p>';
        }

        div.innerHTML = `
            <div class="max-w-[75%] ${isMe ? 'order-2' : ''} relative">
                ${!isMe ? '<p class="text-[10px] text-indigo-400 font-medium mb-1 ml-1">' + (msg.sender ? msg.sender.name : 'User') + '</p>' : ''}
                <div class="${isMe ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-100'} rounded-2xl ${isMe ? 'rounded-br-md' : 'rounded-bl-md'} px-4 py-2.5 shadow-lg relative">
                    ${contentHtml}
                    <div class="flex items-center justify-end gap-1.5 mt-1">
                        <span class="text-[10px] ${isMe ? 'text-indigo-200' : 'text-slate-500'}">${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-1 mt-1 ${isMe ? 'justify-end' : 'justify-start'}" id="reactions-${msg.id}"></div>
                <div class="opacity-0 group-hover:opacity-100 transition absolute top-0 ${isMe ? '-left-28' : '-right-28'} flex items-center gap-1 bg-slate-800 border border-slate-700 rounded-full px-2 py-1 shadow-md z-10">
                    <button onclick="toggleReaction(${msg.id}, '👍')" class="hover:scale-125 transition text-xs p-0.5">👍</button>
                    <button onclick="toggleReaction(${msg.id}, '❤️')" class="hover:scale-125 transition text-xs p-0.5">❤️</button>
                    <button onclick="toggleReaction(${msg.id}, '😂')" class="hover:scale-125 transition text-xs p-0.5">😂</button>
                    <button onclick="toggleReaction(${msg.id}, '👏')" class="hover:scale-125 transition text-xs p-0.5">👏</button>
                    <button onclick="toggleReaction(${msg.id}, '🙏')" class="hover:scale-125 transition text-xs p-0.5">🙏</button>
                </div>
            </div>
        `;

        messagesContainer.appendChild(div);
        scrollToBottom();
    }

    // Submit text / file message
    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const content = messageInput.value.trim();
        if (!content && !selectedFile) return;

        const formData = new FormData();
        formData.append('content', content);
        if (selectedFile) {
            formData.append('file', selectedFile);
            formData.append('type', selectedFile.type.startsWith('image/') ? 'image' : 'file');
        } else {
            formData.append('type', 'text');
        }

        messageInput.value = '';
        autoResize(messageInput);
        clearFileSelection();

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
            if (data.success && data.message) {
                appendMessage(data.message);
                lastMessageId = Math.max(lastMessageId, data.message.id);
            }
        })
        .catch(err => console.error('Send error:', err));
    });

    // Real-Time Echo / WebSocket Integration
    let echoChannel = null;
    let typingTimer = null;
    const typingIndicator = document.getElementById('typingIndicator');

    if (window.Echo) {
        try {
            echoChannel = window.Echo.private('conversation.' + conversationId);
            
            echoChannel.listen('.MessageSent', function(e) {
                if (e && e.id) {
                    appendMessage(e);
                    lastMessageId = Math.max(lastMessageId, e.id);
                }
            });

            echoChannel.listen('MessageSent', function(e) {
                const msg = e.message || e;
                if (msg && msg.id) {
                    appendMessage(msg);
                    lastMessageId = Math.max(lastMessageId, msg.id);
                }
            });

            echoChannel.listen('MessageReactionUpdated', function(e) {
                if (e && e.message_id && e.reactions) {
                    renderReactions(e.message_id, e.reactions);
                }
            });

            echoChannel.listenForWhisper('typing', function(e) {
                if (e.userId !== currentUserId) {
                    if (typingIndicator) {
                        typingIndicator.textContent = (e.userName || 'Someone') + ' is typing...';
                        typingIndicator.classList.remove('hidden');
                        clearTimeout(typingTimer);
                        typingTimer = setTimeout(function() {
                            typingIndicator.classList.add('hidden');
                        }, 2500);
                    }
                }
            });

            let lastTypingTime = 0;
            messageInput.addEventListener('keydown', function() {
                const now = Date.now();
                if (now - lastTypingTime > 1500 && echoChannel) {
                    lastTypingTime = now;
                    echoChannel.whisper('typing', {
                        userId: currentUserId,
                        userName: '{{ auth()->user()?->name ?? "User" }}'
                    });
                }
            });
        } catch (echoErr) {
            console.warn('Echo initialization:', echoErr);
        }
    }

    // Polling fallback every 3 seconds
    setInterval(function() {
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
                    appendMessage(msg);
                    lastMessageId = Math.max(lastMessageId, msg.id);
                });
            }
        })
        .catch(err => console.error('Poll error:', err));
    }, 3000);

})();
</script>
@endpush
@endsection
