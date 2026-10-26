@extends('layouts.app')

@section('title', 'Finance Account Mappings')

@section('content')
<div class="p-6 max-w-7xl mx-auto space-y-6">
    <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
        <div class="text-sm text-gray-500 mb-1"><a href="{{ route('admin.accounts.index') }}">Finance</a> / Account Mappings</div>
        <h1 class="text-2xl font-bold text-gray-900">Receivable and Fee-Vote Mappings</h1>
        <p class="mt-2 text-sm text-gray-600">School: {{ $school->name }}. Map only finance-approved votes to existing active, postable USD accounts. This screen does not approve historical fee rates or post invoices.</p>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
            <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm space-y-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Configure approved mapping</h2>
            <p class="text-sm text-gray-600">A finance approval reference is required and recorded with the administrator and before/after account values.</p>
        </div>
        <form method="POST" action="{{ route('admin.account-mappings.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <label class="text-sm font-medium text-gray-700">Mapping type
                <select name="mapping_type" id="mappingType" required class="mt-1 block w-full rounded border-gray-300">
                    <option value="">Select type</option>
                    <option value="control_account" @selected(old('mapping_type') === 'control_account')>Receivable control</option>
                    <option value="fee_vote" @selected(old('mapping_type') === 'fee_vote')>Fee vote</option>
                </select>
            </label>
            <label class="text-sm font-medium text-gray-700">Mapping key
                <select name="source_key" id="sourceKey" required class="mt-1 block w-full rounded border-gray-300">
                    <option value="">Select key</option>
                    <option data-type="control_account" value="ACCOUNTS_RECEIVABLE" @selected(old('source_key') === 'ACCOUNTS_RECEIVABLE')>ACCOUNTS_RECEIVABLE</option>
                    @foreach(\App\Http\Controllers\Admin\AccountMappingController::FEE_VOTES as $code => $label)
                        <option data-type="fee_vote" value="{{ $code }}" @selected(old('source_key') === $code)>{{ $code }} — {{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium text-gray-700 md:col-span-2">Existing chart-of-accounts account
                <select name="account_id" required class="mt-1 block w-full rounded border-gray-300">
                    <option value="">Select eligible account</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) old('account_id') === (string) $account->id)>
                            {{ $account->code }} — {{ $account->name }} ({{ $account->type }}, {{ $account->currency }})
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium text-gray-700 md:col-span-2">Finance approval reference
                <input type="text" name="finance_approval_reference" value="{{ old('finance_approval_reference') }}" maxlength="150" required class="mt-1 block w-full rounded border-gray-300" placeholder="Approved minute, resolution or finance sign-off reference">
            </label>
            <label class="md:col-span-2 flex items-start gap-2 text-sm text-gray-700">
                <input type="checkbox" name="finance_approval_confirmed" value="1" required @checked(old('finance_approval_confirmed')) class="mt-1 rounded border-gray-300">
                <span>I confirm the cited finance approval covers this exact mapping key and account. Provisional fee rates remain unapproved until separately signed off.</span>
            </label>
            <div class="md:col-span-2"><button type="submit" class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded-lg font-medium">Save mapping and audit</button></div>
        </form>
    </section>

    <section class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-5 border-b"><h2 class="font-semibold text-gray-900">Current Term 1 mapping keys</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700">
                <thead class="bg-gray-50 text-xs uppercase"><tr><th class="p-3">Type</th><th class="p-3">Key</th><th class="p-3">Account</th><th class="p-3">Status</th></tr></thead>
                <tbody class="divide-y">
                    @forelse($mappings as $mapping)
                        <tr><td class="p-3">{{ $mapping->mapping_type }}</td><td class="p-3 font-mono">{{ $mapping->source_key }}</td><td class="p-3">{{ $mapping->account?->code }} — {{ $mapping->account?->name }}</td><td class="p-3">{{ $mapping->is_active ? 'Active' : 'Inactive' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="p-6 text-center text-gray-500">No receivable or Term 1 fee-vote mappings are configured for this school.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
<script>
(() => {
    const type = document.getElementById('mappingType');
    const key = document.getElementById('sourceKey');
    const filter = () => {
        [...key.options].forEach((option) => {
            if (!option.dataset.type) return;
            option.hidden = option.dataset.type !== type.value;
            if (option.hidden && option.selected) key.value = '';
        });
    };
    type.addEventListener('change', filter);
    filter();
})();
</script>
@endsection
