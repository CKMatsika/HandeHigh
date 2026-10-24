@php($award = $award ?? null)
<div class="grid grid-cols-1 md:grid-cols-3 gap-3">
    <label>Funding source<select name="funding_source" class="block w-full bg-slate-800 rounded-lg"><option value="internal" @selected(old('funding_source',$award?->funding_source ?? ($award?->accounting_treatment==='sponsor_receivable'?'external':'internal'))==='internal')>Internal school scholarship</option><option value="external" @selected(old('funding_source',$award?->funding_source ?? ($award?->accounting_treatment==='sponsor_receivable'?'external':'internal'))==='external')>External sponsorship</option></select></label>
    <label>Academic year<input name="academic_year" required pattern="[0-9]{4}" value="{{ old('academic_year',$award?->academic_year ?? date('Y')) }}" class="block w-full bg-slate-800 rounded-lg"></label>
    <label>Registered sponsor<select name="funding_sponsor_id" class="block w-full bg-slate-800 rounded-lg"><option value="">School funding / legacy sponsor description</option>@foreach($sponsors as $sponsor)<option value="{{ $sponsor->id }}" @selected((string)old('funding_sponsor_id',$award?->funding_sponsor_id)===(string)$sponsor->id)>{{ $sponsor->name }}</option>@endforeach</select></label>
    <label class="md:col-span-3">Sponsor recognition evidence<textarea name="recognition_evidence" maxlength="4000" placeholder="Approved enforceable obligation and recognition evidence; leave pending until verified" class="block w-full bg-slate-800 rounded-lg">{{ old('recognition_evidence',$award?->recognition_evidence) }}</textarea></label>
    @foreach(['reference' => 'Award reference', 'name' => 'Scholarship name', 'sponsor' => 'Sponsor / funding organisation', 'supporting_reference' => 'Supporting document reference'] as $field => $label)
        <label>{{ $label }}<input name="{{ $field }}" value="{{ old($field, $award?->$field) }}" maxlength="{{ $field === 'reference' ? 100 : 255 }}" @required(in_array($field, ['reference','name'])) class="block w-full bg-slate-800 border-slate-600 rounded-lg"></label>
    @endforeach
    <label>Coverage type<select name="coverage_type" class="block w-full bg-slate-800 rounded-lg">
        @foreach(['full' => 'Full (selected votes)', 'partial' => 'Partial'] as $key => $label)<option value="{{ $key }}" @selected(old('coverage_type', $award?->coverage_type ?? 'partial') === $key)>{{ $label }}</option>@endforeach
    </select></label>
    <label>Coverage basis<select name="coverage_basis" class="block w-full bg-slate-800 rounded-lg">
        @foreach(['percentage' => 'Percentage of selected votes', 'amount' => 'Total award amount cap'] as $key => $label)<option value="{{ $key }}" @selected(old('coverage_basis', $award?->coverage_basis) === $key)>{{ $label }}</option>@endforeach
    </select></label>
    <label>Approved value<input type="number" name="coverage_value" required min="0.01" step="0.01" value="{{ old('coverage_value', $award?->coverage_value) }}" class="block w-full bg-slate-800 rounded-lg"></label>
    <label>Effective start<input type="date" name="starts_on" required value="{{ old('starts_on', $award?->starts_on?->toDateString()) }}" class="block w-full bg-slate-800 rounded-lg"></label>
    <label>Effective end<input type="date" name="ends_on" required value="{{ old('ends_on', $award?->ends_on?->toDateString()) }}" class="block w-full bg-slate-800 rounded-lg"></label>
    <label>Covered fee votes (Ctrl to select)<select name="covered_votes[]" multiple required class="block w-full bg-slate-800 rounded-lg">
        @foreach($votes as $vote)<option value="{{ $vote }}" @selected(in_array($vote, old('covered_votes', $award?->covered_votes ?? [])))>{{ $vote }}</option>@endforeach
    </select></label>
    <label>Accounting treatment<select name="accounting_treatment" class="block w-full bg-slate-800 rounded-lg">
        @foreach(['unconfigured' => 'Await management policy', 'school_remission' => 'School-funded remission', 'sponsor_receivable' => 'Transfer to sponsor receivable'] as $key => $label)<option value="{{ $key }}" @selected(old('accounting_treatment', $award?->accounting_treatment) === $key)>{{ $label }}</option>@endforeach
    </select></label>
</div>
<p class="text-xs text-slate-400">Full means 100% of selected votes only. An amount cap applies across all invoices under this award, in the school ledger currency. Approval never creates cash.</p>
