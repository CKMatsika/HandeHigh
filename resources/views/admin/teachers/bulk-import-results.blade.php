@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div>
        <h1 class="text-xl font-semibold text-slate-50">Teacher Import Results</h1>
        <p class="mt-1 text-sm text-slate-400">Existing teacher and user records were not changed.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        @foreach ([['New teachers created', count($results['created']), 'emerald'], ['Existing/duplicate rows skipped', count($results['skipped']), 'amber'], ['Invalid or failed rows', count($results['invalid']), 'rose']] as [$label, $count, $tone])
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
                <p class="text-xs text-slate-400">{{ $label }}</p><p class="mt-1 text-2xl font-semibold text-{{ $tone }}-400">{{ $count }}</p>
            </div>
        @endforeach
    </div>

    @foreach (['created' => 'Created', 'skipped' => 'Skipped', 'invalid' => 'Invalid / failed'] as $key => $title)
        @if (count($results[$key]))
            <section class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
                <h2 class="mb-3 font-medium text-slate-100">{{ $title }}</h2>
                <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="text-left text-xs text-slate-400"><th class="p-2">Excel row</th><th class="p-2">Teacher</th><th class="p-2">Result</th></tr></thead><tbody class="divide-y divide-slate-800">
                    @foreach ($results[$key] as $item)
                        <tr><td class="p-2 text-slate-300">{{ $item['row'] }}</td><td class="p-2 text-slate-100">{{ $item['name'] ?? '—' }}</td><td class="p-2 text-slate-300">{{ $item['reason'] ?? 'Created teacher record #'.$item['teacher_id'] }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </section>
        @endif
    @endforeach

    <a href="{{ route('admin.teachers.index') }}" class="inline-flex rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white">Back to Teachers</a>
</div>
@endsection
