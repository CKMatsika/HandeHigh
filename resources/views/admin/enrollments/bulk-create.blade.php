@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="bg-slate-900 rounded-lg shadow overflow-hidden">
        <div class="px-4 py-5 border-b border-slate-800 sm:px-6">
            <h3 class="text-lg font-medium text-slate-100">Bulk Student Enrollment</h3>
            <p class="mt-1 text-sm text-slate-400">Upload an Excel/CSV file to enroll multiple students at once.</p>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <div class="bg-slate-800/50 border border-dashed border-slate-700 rounded-lg p-6 text-center">
                <form action="{{ route('admin.enrollments.bulk-store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="space-y-6">
                        <div class="flex items-center justify-center">
                            <div class="w-full max-w-md">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-slate-400">
                                        <label for="file-upload" class="relative cursor-pointer bg-slate-800 rounded-md font-medium text-indigo-400 hover:text-indigo-300 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                            <span>Upload a file</span>
                                            <input id="file-upload" name="file" type="file" class="sr-only" accept=".xlsx,.xls,.csv">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-slate-500">Excel or CSV up to 10MB</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-800/50 p-4 rounded-lg text-left">
                            <h4 class="text-sm font-medium text-slate-200 mb-2">File Format Requirements:</h4>
                            <ul class="text-xs text-slate-400 space-y-1 list-disc list-inside">
                                <li>Required columns: <span class="font-mono bg-slate-900 px-1 rounded">student_name</span>, <span class="font-mono bg-slate-900 px-1 rounded">email</span>, <span class="font-mono bg-slate-900 px-1 rounded">class_name</span>, <span class="font-mono bg-slate-900 px-1 rounded">academic_year</span>, <span class="font-mono bg-slate-900 px-1 rounded">term</span></li>
                                <li>Optional columns: <span class="font-mono bg-slate-900 px-1 rounded">phone</span>, <span class="font-mono bg-slate-900 px-1 rounded">address</span>, <span class="font-mono bg-slate-900 px-1 rounded">date_of_birth</span>, <span class="font-mono bg-slate-900 px-1 rounded">gender</span>, <span class="font-mono bg-slate-900 px-1 rounded">grade</span>, <span class="font-mono bg-slate-900 px-1 rounded">is_boarding</span> (yes/no), <span class="font-mono bg-slate-900 px-1 rounded">has_transport</span> (yes/no), <span class="font-mono bg-slate-900 px-1 rounded">status</span> (active/inactive/graduated/transferred)</li>
                                <li>Date format: YYYY-MM-DD (e.g., 2023-09-01)</li>
                                <li>First row should contain column headers</li>
                            </ul>
                        </div>

                        @if ($errors->any())
                            <div class="bg-red-900/20 border border-red-800 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-red-400 mb-2">Please fix the following errors:</h4>
                                <ul class="text-xs text-red-300 space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="bg-red-900/20 border border-red-800 rounded-lg p-4">
                                <p class="text-sm text-red-400">{{ session('error') }}</p>
                            </div>
                        @endif

                        <div class="flex items-center justify-end">
                            <a href="{{ route('admin.enrollments.template') }}" class="text-sm text-indigo-400 hover:text-indigo-300 mr-4">
                                <i class="fas fa-download mr-1"></i> Download Template
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-upload mr-2"></i> Upload & Process
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #file-upload {
        display: none;
    }
    .file-upload-label {
        cursor: pointer;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('file-upload');
        const dropZone = document.querySelector('.bg-slate-800\\/50');
        
        // Handle drag and drop
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            dropZone.classList.add('border-indigo-400', 'bg-slate-800');
        }

        function unhighlight() {
            dropZone.classList.remove('border-indigo-400', 'bg-slate-800');
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        function handleFiles(files) {
            if (files.length > 0) {
                fileInput.files = files;
                // You can add file name display here if needed
            }
        }
    });
</script>
@endpush
