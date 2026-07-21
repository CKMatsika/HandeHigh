@extends('layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-50">Send Email</h1>
            <p class="text-xs text-slate-400 mt-1">Send email messages to students, staff, and guardians.</p>
        </div>
        <a href="{{ route('admin.communication.index') }}" class="inline-flex items-center rounded-full bg-slate-700 px-4 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-600 transition">
            Back to Communication
        </a>
    </div>

    <div class="max-w-4xl">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6">
            <form action="/admin/communication/email/send" method="POST">
                @csrf
                <div class="space-y-6">
                    <!-- Recipients -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-3">Recipients</label>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                            <!-- Students -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-xs font-medium text-slate-400">Students</h4>
                                    <div class="flex gap-2">
                                        <button type="button" onclick="selectAll('students')" class="text-xs text-blue-400 hover:text-blue-300">All</button>
                                        <button type="button" onclick="deselectAll('students')" class="text-xs text-slate-500 hover:text-slate-400">None</button>
                                    </div>
                                </div>
                                <div class="space-y-1 max-h-32 overflow-y-auto border border-slate-700 rounded-lg p-2">
                                    @foreach($students as $student)
                                        <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer hover:text-slate-100">
                                            <input type="checkbox" name="recipients[]" value="student_{{ $student->id }}" data-type="students" class="rounded border-slate-600 bg-slate-800 text-blue-500 focus:ring-blue-500">
                                            {{ $student->first_name }} {{ $student->last_name }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            
                            <!-- Staff -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-xs font-medium text-slate-400">Staff</h4>
                                    <div class="flex gap-2">
                                        <button type="button" onclick="selectAll('staff')" class="text-xs text-blue-400 hover:text-blue-300">All</button>
                                        <button type="button" onclick="deselectAll('staff')" class="text-xs text-slate-500 hover:text-slate-400">None</button>
                                    </div>
                                </div>
                                <div class="space-y-1 max-h-32 overflow-y-auto border border-slate-700 rounded-lg p-2">
                                    @foreach($staff as $staffMember)
                                        <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer hover:text-slate-100">
                                            <input type="checkbox" name="recipients[]" value="staff_{{ $staffMember->id }}" data-type="staff" class="rounded border-slate-600 bg-slate-800 text-blue-500 focus:ring-blue-500">
                                            {{ $staffMember->first_name }} {{ $staffMember->last_name }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            
                            <!-- Guardians -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-xs font-medium text-slate-400">Guardians</h4>
                                    <div class="flex gap-2">
                                        <button type="button" onclick="selectAll('guardians')" class="text-xs text-blue-400 hover:text-blue-300">All</button>
                                        <button type="button" onclick="deselectAll('guardians')" class="text-xs text-slate-500 hover:text-slate-400">None</button>
                                    </div>
                                </div>
                                <div class="space-y-1 max-h-32 overflow-y-auto border border-slate-700 rounded-lg p-2">
                                    @foreach($guardians as $guardian)
                                        <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer hover:text-slate-100">
                                            <input type="checkbox" name="recipients[]" value="guardian_{{ $guardian->id }}" data-type="guardians" class="rounded border-slate-600 bg-slate-800 text-blue-500 focus:ring-blue-500">
                                            {{ $guardian->first_name }} {{ $guardian->last_name }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Details -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div>
                            <label for="subject" class="block text-sm font-medium text-slate-300 mb-2">Subject</label>
                            <input type="text" id="subject" name="subject" 
                                   class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Enter email subject..." required>
                        </div>
                        
                        <div>
                            <label for="priority" class="block text-sm font-medium text-slate-300 mb-2">Priority</label>
                            <select id="priority" name="priority" class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>

                    <!-- Message -->
                    <div>
                        <label for="message" class="block text-sm font-medium text-slate-300 mb-2">Message</label>
                        <textarea id="message" name="message" rows="8" 
                                  class="w-full rounded-lg border border-slate-700 bg-slate-800 text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Type your email message here..." required></textarea>
                        <div class="mt-1 text-xs text-slate-500">
                            <span id="charCount">0</span> characters
                        </div>
                    </div>

                    <!-- Attachments -->
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Attachments</label>
                        <div class="border border-slate-700 border-dashed rounded-lg p-4 text-center">
                            <input type="file" id="attachments" name="attachments[]" multiple class="hidden" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                            <label for="attachments" class="cursor-pointer">
                                <div class="text-slate-400">
                                    <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    <p class="text-sm">Click to upload files</p>
                                    <p class="text-xs mt-1">PDF, DOC, XLS, PNG, JPG (Max 10MB)</p>
                                </div>
                            </label>
                            <div id="fileList" class="mt-2 text-xs text-slate-400"></div>
                        </div>
                    </div>

                    <!-- Send Options -->
                    <div class="flex items-center gap-4">
                        <button type="submit" class="inline-flex items-center rounded-full bg-blue-500 px-6 py-2 text-sm font-medium text-white hover:bg-blue-600 transition">
                            Send Email
                        </button>
                        <button type="button" onclick="previewEmail()" class="inline-flex items-center rounded-full bg-slate-700 px-6 py-2 text-sm font-medium text-slate-300 hover:bg-slate-600 transition">
                            Preview
                        </button>
                        <label class="flex items-center gap-2 text-xs text-slate-400">
                            <input type="checkbox" name="send_copy" class="rounded border-slate-600 bg-slate-800 text-blue-500 focus:ring-blue-500">
                            Send copy to myself
                        </label>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black opacity-50" onclick="closePreview()"></div>
            
            <div class="relative bg-slate-900 rounded-2xl border border-slate-700 max-w-2xl w-full p-6 max-h-[80vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-slate-50 mb-4">Email Preview</h3>
                <div class="bg-slate-800 rounded-lg p-4 mb-4">
                    <div class="mb-3">
                        <div class="text-xs text-slate-500 mb-1">Subject:</div>
                        <div class="text-sm text-slate-300" id="previewSubject"></div>
                    </div>
                    <div class="mb-3">
                        <div class="text-xs text-slate-500 mb-1">Priority:</div>
                        <div class="text-sm text-slate-300" id="previewPriority"></div>
                    </div>
                    <div class="mb-3">
                        <div class="text-xs text-slate-500 mb-1">Message:</div>
                        <div class="text-sm text-slate-300 whitespace-pre-wrap" id="previewMessage"></div>
                    </div>
                </div>
                <div class="text-xs text-slate-500 mb-4">
                    Recipients: <span id="recipientCount">0</span>
                </div>
                <div class="flex gap-3">
                    <button onclick="closePreview()" class="flex-1 bg-slate-700 text-slate-300 rounded-lg px-4 py-2 text-sm font-medium hover:bg-slate-600 transition">
                        Close
                    </button>
                    <button onclick="sendEmail()" class="flex-1 bg-blue-500 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-blue-600 transition">
                        Send Email
                    </button>
                </div>
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
    });

    // File upload handling
    document.getElementById('attachments').addEventListener('change', function() {
        const files = this.files;
        const fileList = document.getElementById('fileList');
        fileList.innerHTML = '';
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            fileList.innerHTML += `<div>${file.name} (${fileSize} MB)</div>`;
        }
    });

    // Preview functionality
    function previewEmail() {
        const subject = document.getElementById('subject').value;
        const priority = document.getElementById('priority').value;
        const message = document.getElementById('message').value;
        const recipients = document.querySelectorAll('input[name="recipients[]"]:checked');
        
        if (!subject.trim() || !message.trim()) {
            alert('Please fill in both subject and message.');
            return;
        }
        
        if (recipients.length === 0) {
            alert('Please select at least one recipient.');
            return;
        }
        
        document.getElementById('previewSubject').textContent = subject;
        document.getElementById('previewPriority').textContent = priority.charAt(0).toUpperCase() + priority.slice(1);
        document.getElementById('previewMessage').textContent = message;
        document.getElementById('recipientCount').textContent = recipients.length;
        document.getElementById('previewModal').classList.remove('hidden');
    }

    function closePreview() {
        document.getElementById('previewModal').classList.add('hidden');
    }

    function sendEmail() {
        closePreview();
        document.querySelector('form[type="POST"]').submit();
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
