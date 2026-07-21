@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Send SMS</h1>
            <p class="text-xs text-slate-400 mt-1">Send text messages to students, staff, and guardians.</p>
        </div>
        <a href="{{ route('admin.communication.index') }}" class="inline-flex items-center rounded-full bg-slate-700 px-4 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-600 transition">
            Back to Communication
        </a>
    </div>

    <div class="max-w-2xl">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <form action="/admin/communication/sms/send" method="POST">
                @csrf
                <div class="space-y-6">
                    <!-- Recipients -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-3">Recipients</label>
                        <div class="space-y-3">
                            <!-- Students -->
                            <div>
                                <h4 class="text-xs font-medium text-slate-400 mb-2">Students</h4>
                                <div class="grid grid-cols-2 gap-2 max-h-32 overflow-y-auto">
                                    @foreach($students as $student)
                                        <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer hover:text-slate-100">
                                            <input type="checkbox" name="recipients[]" value="{{ $student->id }}" class="rounded border-slate-600 bg-slate-800 text-green-500 focus:ring-green-500">
                                            {{ $student->first_name }} {{ $student->last_name }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            
                            <!-- Staff -->
                            <div>
                                <h4 class="text-xs font-medium text-slate-400 mb-2">Staff</h4>
                                <div class="grid grid-cols-2 gap-2 max-h-32 overflow-y-auto">
                                    @foreach($staff as $staffMember)
                                        <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer hover:text-slate-100">
                                            <input type="checkbox" name="recipients[]" value="{{ $staffMember->id }}" class="rounded border-slate-600 bg-slate-800 text-green-500 focus:ring-green-500">
                                            {{ $staffMember->first_name }} {{ $staffMember->last_name }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            
                            <!-- Guardians -->
                            <div>
                                <h4 class="text-xs font-medium text-slate-400 mb-2">Guardians</h4>
                                <div class="grid grid-cols-2 gap-2 max-h-32 overflow-y-auto">
                                    @foreach($guardians as $guardian)
                                        <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer hover:text-slate-100">
                                            <input type="checkbox" name="recipients[]" value="{{ $guardian->id }}" class="rounded border-slate-600 bg-slate-800 text-green-500 focus:ring-green-500">
                                            {{ $guardian->first_name }} {{ $guardian->last_name }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Message -->
                    <div>
                        <label for="message" class="block text-sm font-medium text-slate-300 mb-2">Message</label>
                        <textarea id="message" name="message" rows="4" maxlength="160" 
                                  class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                                  placeholder="Type your SMS message here (160 characters max)..." required></textarea>
                        <div class="mt-1 text-xs text-slate-500">
                            <span id="charCount">0</span>/160 characters
                        </div>
                    </div>

                    <!-- Send Options -->
                    <div class="flex items-center gap-4">
                        <button type="submit" class="inline-flex items-center rounded-full bg-green-500 px-6 py-2 text-sm font-medium text-white hover:bg-green-600 transition">
                            Send SMS
                        </button>
                        <button type="button" onclick="previewSMS()" class="inline-flex items-center rounded-full bg-slate-700 px-6 py-2 text-sm font-medium text-slate-300 hover:bg-slate-600 transition">
                            Preview
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black opacity-50" onclick="closePreview()"></div>
            
            <div class="relative bg-slate-900 rounded-2xl border border-slate-700 max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-slate-50 mb-4">SMS Preview</h3>
                <div class="bg-slate-800 rounded-lg p-4 mb-4">
                    <div class="text-sm text-slate-300" id="previewContent"></div>
                </div>
                <div class="text-xs text-slate-500 mb-4">
                    Recipients: <span id="recipientCount">0</span>
                </div>
                <button onclick="closePreview()" class="w-full bg-slate-700 text-slate-300 rounded-lg px-4 py-2 text-sm font-medium hover:bg-slate-600 transition">
                    Close
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Character counter
    document.getElementById('message').addEventListener('input', function() {
        const charCount = this.value.length;
        document.getElementById('charCount').textContent = charCount;
        
        if (charCount > 160) {
            this.value = this.value.substring(0, 160);
            document.getElementById('charCount').textContent = 160;
        }
    });

    // Preview functionality
    function previewSMS() {
        const message = document.getElementById('message').value;
        const recipients = document.querySelectorAll('input[name="recipients[]"]:checked');
        
        if (!message.trim()) {
            alert('Please enter a message.');
            return;
        }
        
        if (recipients.length === 0) {
            alert('Please select at least one recipient.');
            return;
        }
        
        document.getElementById('previewContent').textContent = message;
        document.getElementById('recipientCount').textContent = recipients.length;
        document.getElementById('previewModal').classList.remove('hidden');
    }

    function closePreview() {
        document.getElementById('previewModal').classList.add('hidden');
    }

    // Select all functionality
    function selectAll(type) {
        const checkboxes = document.querySelectorAll(`input[name="recipients[]"][data-type="${type}"]`);
        checkboxes.forEach(checkbox => checkbox.checked = true);
    }

    function deselectAll(type) {
        const checkboxes = document.querySelectorAll(`input[name="recipients[]"][data-type="${type}"]`);
        checkboxes.forEach(checkbox => checkbox.checked = false);
    }
</script>
@endpush
