@extends('layouts.app')

@section('title', 'Create New Committee')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <a href="{{ route('admin.sda.committees.index') }}" class="text-decoration-none text-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>
            </a>
            Create New Committee
        </h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('admin.sda.committees.store') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label for="name">Committee Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" 
                           value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" 
                              id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check mb-3">
                    <input type="hidden" name="requires_financial_approval" value="0">
                    <input class="form-check-input" type="checkbox" 
                           name="requires_financial_approval" id="requires_financial_approval" value="1"
                           {{ old('requires_financial_approval') ? 'checked' : '' }}>
                    <label class="form-check-label" for="requires_financial_approval">
                        Requires Financial Approval
                    </label>
                    <small class="form-text text-muted">
                        Check if this committee needs to approve financial decisions
                    </small>
                </div>

                <div class="form-check mb-3">
                    <input type="hidden" name="requires_procurement_approval" value="0">
                    <input class="form-check-input" type="checkbox" 
                           name="requires_procurement_approval" id="requires_procurement_approval" value="1"
                           {{ old('requires_procurement_approval') ? 'checked' : '' }}>
                    <label class="form-check-label" for="requires_procurement_approval">
                        Requires Procurement Approval
                    </label>
                    <small class="form-text text-muted">
                        Check if this committee needs to approve procurement decisions
                    </small>
                </div>

                <div class="form-check mb-3">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" 
                           name="is_active" id="is_active" value="1"
                           {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        Active
                    </label>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.sda.committees.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Create Committee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
