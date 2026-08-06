@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-lg font-semibold text-slate-50">AI Career Counsellor</h1>
        <p class="text-xs text-slate-400 mt-1">Explore careers that match your strengths</p>
    </div>
    <a href="{{ route('student.dashboard') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">Back to Dashboard</a>
</div>

<div class="rounded-2xl border border-slate-800 bg-slate-900/80 overflow-hidden" style="height: calc(100vh - 200px); min-height: 500px;">
    <!-- Chat Header -->
    <div class="px-4 py-3 border-b border-slate-800 flex items-center gap-3">
        <div class="h-8 w-8 rounded-full bg-indigo-500/20 flex items-center justify-center">
            <span class="text-sm">🤖</span>
        </div>
        <div>
            <h2 class="text-sm font-medium text-slate-50">Career Counsellor AI</h2>
            <p class="text-[10px] text-slate-400">Here to guide your future</p>
        </div>
    </div>

    <!-- Chat Messages -->
    <div id="chat-messages" class="p-4 space-y-4 overflow-y-auto" style="height: calc(100% - 130px);">
        <div class="flex items-start gap-3" id="welcome-message">
            <div class="h-7 w-7 rounded-full bg-indigo-500/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                <span class="text-xs">🤖</span>
            </div>
            <div class="flex-1">
                <div class="rounded-lg bg-slate-800/80 border border-slate-700/50 p-3 message-content">
                    <p class="text-xs text-slate-300 loading-text">Loading...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div id="quick-actions" class="px-4 py-2 border-t border-slate-800">
        <div class="flex flex-wrap gap-2" id="actions-container"></div>
    </div>

    <!-- Chat Input -->
    <div class="px-4 py-3 border-t border-slate-800 bg-slate-950/50">
        <form id="chat-form" class="flex gap-2">
            <input type="text" id="message-input" placeholder="Type your message..." autocomplete="off"
                class="flex-1 rounded-lg border border-slate-700 bg-slate-800/50 px-3 py-2 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition">
            <button type="submit" class="rounded-lg bg-indigo-500 px-4 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition disabled:opacity-50" id="send-btn">
                Send
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
const chatMessages = document.getElementById('chat-messages');
const chatForm = document.getElementById('chat-form');
const messageInput = document.getElementById('message-input');
const sendBtn = document.getElementById('send-btn');
const actionsContainer = document.getElementById('actions-container');

let isLoading = false;

function addMessage(role, text, type, actions) {
    const isUser = role === 'user';
    const div = document.createElement('div');
    div.className = `flex items-start gap-3 ${isUser ? 'flex-row-reverse' : ''}`;

    const avatar = document.createElement('div');
    avatar.className = `h-7 w-7 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5 ${isUser ? 'bg-emerald-500/20' : 'bg-indigo-500/20'}`;
    avatar.innerHTML = `<span class="text-xs">${isUser ? '👤' : '🤖'}</span>`;

    const content = document.createElement('div');
    content.className = 'flex-1 max-w-[85%]';

    const bubble = document.createElement('div');
    bubble.className = `rounded-lg p-3 ${isUser ? 'bg-indigo-500/20 border border-indigo-500/30' : 'bg-slate-800/80 border border-slate-700/50'}`;

    const textEl = document.createElement('div');
    textEl.className = 'text-xs text-slate-300 leading-relaxed whitespace-pre-wrap';
    textEl.innerHTML = formatMessage(text);

    bubble.appendChild(textEl);
    content.appendChild(bubble);
    div.appendChild(avatar);
    div.appendChild(content);

    chatMessages.appendChild(div);

    if (actions && actions.length > 0) {
        const actionsRow = document.createElement('div');
        actionsRow.className = 'flex flex-wrap gap-2 mt-2';
        actions.forEach(action => {
            const btn = document.createElement('button');
            btn.className = 'inline-flex items-center rounded-full border border-slate-700 bg-slate-800/60 px-3 py-1 text-[10px] font-medium text-slate-300 hover:bg-slate-700 hover:text-slate-200 transition';
            btn.textContent = action.label;
            btn.onclick = () => sendMessage(action.value);
            actionsRow.appendChild(btn);
        });
        content.appendChild(actionsRow);
    }

    scrollToBottom();
}

function formatMessage(text) {
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong class="text-slate-50">$1</strong>');
    text = text.replace(/\n/g, '<br>');
    return text;
}

function scrollToBottom() {
    setTimeout(() => {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }, 50);
}

function setLoading(loading) {
    isLoading = loading;
    sendBtn.disabled = loading;
    messageInput.disabled = loading;
    if (loading) {
        sendBtn.textContent = '...';
    } else {
        sendBtn.textContent = 'Send';
    }
}

function setActions(actions) {
    actionsContainer.innerHTML = '';
    if (!actions || actions.length === 0) {
        actionsContainer.parentElement.classList.add('hidden');
        return;
    }
    actionsContainer.parentElement.classList.remove('hidden');
    actions.forEach(action => {
        const btn = document.createElement('button');
        btn.className = 'inline-flex items-center rounded-full border border-slate-700 bg-slate-800/60 px-3 py-1 text-[10px] font-medium text-slate-300 hover:bg-slate-700 hover:text-slate-200 transition text-xs';
        btn.textContent = action.label;
        btn.onclick = () => sendMessage(action.value);
        actionsContainer.appendChild(btn);
    });
}

async function sendMessage(message) {
    if (isLoading || !message.trim()) return;
    messageInput.value = '';

    addMessage('user', message);
    setLoading(true);

    try {
        const response = await fetch('{{ route('student.counsellor.chat') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ message: message }),
        });

        const data = await response.json();
        addMessage('counsellor', data.message, data.type, data.actions);
        setActions(data.actions);
    } catch (error) {
        addMessage('counsellor', "I'm sorry, I couldn't process that. Please try again!", 'error');
    } finally {
        setLoading(false);
    }
}

chatForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const message = messageInput.value.trim();
    if (message) {
        sendMessage(message);
    }
});

messageInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        chatForm.dispatchEvent(new Event('submit'));
    }
});

async function loadWelcome() {
    setLoading(true);
    try {
        const response = await fetch('{{ route('student.counsellor.welcome') }}', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
        });
        const data = await response.json();
        const welcomeEl = document.querySelector('.message-content');
        if (welcomeEl) {
            const textEl = welcomeEl.querySelector('.loading-text');
            if (textEl) {
                textEl.innerHTML = formatMessage(data.message);
                textEl.classList.remove('loading-text');
            }
        }
        setActions(data.actions);
    } catch (error) {
        const welcomeEl = document.querySelector('.message-content');
        if (welcomeEl) {
            welcomeEl.innerHTML = '<p class="text-xs text-slate-400">Welcome! How can I help you explore your career options?</p>';
        }
    } finally {
        setLoading(false);
    }
}

loadWelcome();
</script>
@endpush

@push('styles')
<style>
#chat-messages::-webkit-scrollbar {
    width: 4px;
}
#chat-messages::-webkit-scrollbar-track {
    background: transparent;
}
#chat-messages::-webkit-scrollbar-thumb {
    background: rgba(100, 116, 139, 0.3);
    border-radius: 2px;
}
.loading-text::after {
    content: '';
    display: inline-block;
    width: 12px;
    height: 12px;
    border: 2px solid rgba(99, 102, 241, 0.3);
    border-top-color: rgb(99, 102, 241);
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
    margin-left: 8px;
    vertical-align: middle;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
@endpush
@endsection
