@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-lg font-semibold text-slate-50">{{ $set->title }}</h1>
        <p class="text-xs text-slate-400 mt-1">{{ $set->subject->name ?? '' }} · <span id="cardCounter">1</span> of {{ $set->items->count() }}</p>
    </div>
    <a href="{{ route('student.flash-cards.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
        Back to Sets
    </a>
</div>

<div class="max-w-2xl mx-auto">
    <div id="studyComplete" class="hidden rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-6 py-8 text-center">
        <h2 class="text-lg font-semibold text-emerald-300 mb-2">Session Complete!</h2>
        <p class="text-sm text-slate-300 mb-1">You studied <span id="totalStudied">0</span> cards.</p>
        <p class="text-sm text-slate-300 mb-4">Confident on <span id="totalConfident">0</span> cards.</p>
        <a href="{{ route('student.flash-cards.index') }}" class="inline-flex items-center rounded-full bg-indigo-500 px-5 py-2 text-xs font-medium text-white hover:bg-indigo-600 transition">
            Back to Sets
        </a>
    </div>

    <div id="studyArea">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex gap-1">
                <span class="h-2 w-8 rounded-full bg-slate-700 transition-all" id="progressBar"></span>
            </div>
            <span class="text-[11px] text-slate-400" id="progressText">0% complete</span>
        </div>

        <div class="relative cursor-pointer" id="cardContainer" onclick="flipCard()">
            <div class="flash-card h-64 md:h-80" id="flashCard">
                <div class="flash-card-inner" id="cardInner">
                    <div class="flash-card-front rounded-2xl border border-slate-700 bg-gradient-to-br from-slate-800 to-slate-900 p-6 flex items-center justify-center">
                        <p class="text-lg md:text-xl text-slate-100 text-center font-medium" id="cardFront"></p>
                    </div>
                    <div class="flash-card-back rounded-2xl border border-indigo-500/30 bg-gradient-to-br from-slate-800 to-indigo-950/30 p-6 flex items-center justify-center">
                        <div class="text-center">
                            <p class="text-lg md:text-xl text-slate-100 font-medium" id="cardBack"></p>
                            <p class="text-xs text-slate-400 mt-3 italic" id="cardHint"></p>
                        </div>
                    </div>
                </div>
            </div>
            <p class="text-center text-[11px] text-slate-500 mt-2">Tap card to flip</p>
        </div>

        <div class="hidden mt-4" id="confidenceButtons">
            <div class="grid grid-cols-3 gap-3">
                <button onclick="submitConfidence('know')" class="rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-4 py-3 text-sm font-medium text-emerald-300 hover:bg-emerald-500/20 transition">
                    I Know This
                </button>
                <button onclick="submitConfidence('unsure')" class="rounded-xl bg-amber-500/10 border border-amber-500/30 px-4 py-3 text-sm font-medium text-amber-300 hover:bg-amber-500/20 transition">
                    Unsure
                </button>
                <button onclick="submitConfidence('dont_know')" class="rounded-xl bg-rose-500/10 border border-rose-500/30 px-4 py-3 text-sm font-medium text-rose-300 hover:bg-rose-500/20 transition">
                    Don't Know
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<style>
.flash-card {
    perspective: 1000px;
}
.flash-card-inner {
    position: relative;
    width: 100%;
    height: 100%;
    transition: transform 0.5s;
    transform-style: preserve-3d;
}
.flash-card-inner.flipped {
    transform: rotateY(180deg);
}
.flash-card-front, .flash-card-back {
    position: absolute;
    width: 100%;
    height: 100%;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}
.flash-card-back {
    transform: rotateY(180deg);
}
</style>

<script>
const cards = @json($set->items);
const totalCards = cards.length;
let currentIndex = 0;
let sessionId = null;
let isFlipped = false;
let studied = 0;
let confident = 0;

function showCard(index) {
    if (index >= totalCards) {
        endSession();
        return;
    }
    const card = cards[index];
    document.getElementById('cardFront').textContent = card.front_text;
    document.getElementById('cardBack').textContent = card.back_text;
    document.getElementById('cardHint').textContent = card.hint ? 'Hint: ' + card.hint : '';
    document.getElementById('cardCounter').textContent = index + 1;
    document.getElementById('cardInner').classList.remove('flipped');
    document.getElementById('confidenceButtons').classList.add('hidden');
    isFlipped = false;

    const pct = Math.round((index / totalCards) * 100);
    document.getElementById('progressBar').style.width = pct + '%';
    document.getElementById('progressBar').classList.remove('bg-slate-700', 'bg-emerald-500');
    document.getElementById('progressBar').classList.add(pct === 100 ? 'bg-emerald-500' : 'bg-indigo-500');
    document.getElementById('progressText').textContent = pct + '% complete';
}

function flipCard() {
    if (isFlipped) return;
    document.getElementById('cardInner').classList.add('flipped');
    document.getElementById('confidenceButtons').classList.remove('hidden');
    isFlipped = true;
}

async function submitConfidence(level) {
    if (!sessionId) return;

    try {
        await fetch('{{ route("student.flash-cards.submit-result") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            },
            body: JSON.stringify({
                session_id: sessionId,
                item_id: cards[currentIndex].id,
                confidence: level,
            }),
        });

        studied++;
        if (level === 'know') confident++;
        currentIndex++;
        setTimeout(() => showCard(currentIndex), 300);
    } catch (e) {
        currentIndex++;
        showCard(currentIndex);
    }
}

async function startSession() {
    try {
        const res = await fetch('{{ route("student.flash-cards.start-session", $set) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            },
        });
        const data = await res.json();
        sessionId = data.session_id;
    } catch (e) {
        sessionId = 'fallback-' + Date.now();
    }
}

async function endSession() {
    document.getElementById('studyArea').classList.add('hidden');
    document.getElementById('studyComplete').classList.remove('hidden');
    document.getElementById('totalStudied').textContent = studied;
    document.getElementById('totalConfident').textContent = confident;

    if (sessionId && !sessionId.startsWith('fallback-')) {
        try {
            await fetch('{{ route("student.flash-cards.complete-session", ["session" => "_SESSION_ID_"]) }}'.replace('_SESSION_ID_', sessionId), { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}' } });
        } catch (e) {}
    }
}

startSession();
showCard(0);
</script>
@endpush
@endsection
