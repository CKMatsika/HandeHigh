@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-lg font-semibold text-slate-50">Create Flash Card Set</h1>
        <p class="text-xs text-slate-400 mt-1">Add terms and definitions for student study</p>
    </div>
    <a href="{{ route('teacher.flash-cards.index') }}" class="inline-flex items-center rounded-full border border-slate-700 bg-slate-900/80 px-4 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 transition">
        Back
    </a>
</div>

<div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
    <form method="POST" action="{{ route('teacher.flash-cards.store') }}" id="flashCardForm">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Title</label>
                <input type="text" name="title" required class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none" placeholder="e.g. Physics Formulas">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Subject (optional)</label>
                <select name="subject_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none">
                    <option value="">All Subjects</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Class (optional)</label>
                <select name="school_class_id" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none">
                    <option value="">All Classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1">Description (optional)</label>
                <input type="text" name="description" class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none" placeholder="Brief description">
            </div>
        </div>

        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-slate-100">Flash Cards</h2>
            <button type="button" onclick="addCard()" class="inline-flex items-center rounded-full bg-indigo-500 px-3 py-1 text-[11px] font-medium text-white hover:bg-indigo-600 transition">
                + Add Card
            </button>
        </div>

        <div id="cards-container" class="space-y-3">
            <div class="card-item rounded-xl border border-slate-700 bg-slate-950/60 p-3">
                <div class="flex gap-2 items-start">
                    <div class="flex-1">
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">Front (term/question)</label>
                        <input type="text" name="items[0][front_text]" required class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none" placeholder="Term or question">
                    </div>
                    <div class="flex-1">
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">Back (definition/answer)</label>
                        <input type="text" name="items[0][back_text]" required class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none" placeholder="Definition or answer">
                    </div>
                    <div class="w-28">
                        <label class="block text-[11px] font-medium text-slate-400 mb-1">Hint (optional)</label>
                        <input type="text" name="items[0][hint]" class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none" placeholder="Hint">
                    </div>
                    <button type="button" onclick="this.closest('.card-item').remove()" class="mt-5 text-rose-400 hover:text-rose-300 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex gap-2 mt-6">
            <button type="submit" name="action" value="draft" class="rounded-full bg-slate-700 px-5 py-2 text-xs font-medium text-slate-100 hover:bg-slate-600 transition">
                Save as Draft
            </button>
            <button type="submit" name="action" value="publish" class="rounded-full bg-emerald-500 px-5 py-2 text-xs font-medium text-white hover:bg-emerald-600 transition">
                Save & Publish
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
let cardIndex = 1;

function addCard() {
    const container = document.getElementById('cards-container');
    const div = document.createElement('div');
    div.className = 'card-item rounded-xl border border-slate-700 bg-slate-950/60 p-3';
    div.innerHTML = `
        <div class="flex gap-2 items-start">
            <div class="flex-1">
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Front (term/question)</label>
                <input type="text" name="items[${cardIndex}][front_text]" required class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none" placeholder="Term or question">
            </div>
            <div class="flex-1">
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Back (definition/answer)</label>
                <input type="text" name="items[${cardIndex}][back_text]" required class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none" placeholder="Definition or answer">
            </div>
            <div class="w-28">
                <label class="block text-[11px] font-medium text-slate-400 mb-1">Hint (optional)</label>
                <input type="text" name="items[${cardIndex}][hint]" class="w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-slate-100 focus:border-indigo-500 focus:outline-none" placeholder="Hint">
            </div>
            <button type="button" onclick="this.closest('.card-item').remove()" class="mt-5 text-rose-400 hover:text-rose-300 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    `;
    container.appendChild(div);
    cardIndex++;
}
</script>
@endpush
@endsection
