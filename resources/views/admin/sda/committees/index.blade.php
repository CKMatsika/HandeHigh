@extends('layouts.app')

@section('title', 'SDA Committees')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">SDA Committees</h1>
        <a href="{{ route('admin.sda.dashboard') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Dashboard
        </a>
    </div>

    <!-- Committees List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">All Committees</h6>
            <a href="#" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addCommitteeModal">
                <i class="fas fa-plus fa-sm"></i> Add Committee
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="committeesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Members</th>
                            <th>Financial Approval</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($committees as $committee)
                        <tr>
                            <td>
                                <strong>{{ $committee->name }}</strong>
                                @if($committee->chairman_title)
                                <div class="text-muted small">{{ $committee->chairman_title }}: 
                                    @php
                                        $chairman = $committee->chairman();
                                    @endphp
                                    {{ $chairman ? $chairman->user->name : 'Vacant' }}
                                </div>
                                @endif
                            </td>
                            <td>{{ Str::limit($committee->description, 100) }}</td>
                            <td>{{ $committee->activeMembers->count() }}</td>
                            <td>
                                @if($committee->requires_financial_approval)
                                <span class="badge badge-success">Yes</span>
                                @else
                                <span class="badge badge-secondary">No</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.sda.committees.show', $committee) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <button class="btn btn-sm btn-info edit-committee" data-id="{{ $committee->id }}">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Committee Modal -->
<div class="modal fade" id="addCommitteeModal" tabindex="-1" role="dialog" aria-labelledby="addCommitteeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCommitteeModalLabel">Add New Committee</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form action="{{ route('admin.sda.committees.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Committee Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="mandate">Mandate</label>
                        <textarea class="form-control" id="mandate" name="mandate" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="chairman_title">Chairman Title (e.g., 'Chairman', 'President')</label>
                        <input type="text" class="form-control" id="chairman_title" name="chairman_title">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="requires_financial_approval" name="requires_financial_approval" value="1">
                                <label class="form-check-label" for="requires_financial_approval">Requires Financial Approval</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="requires_procurement_approval" name="requires_procurement_approval" value="1">
                                <label class="form-check-label" for="requires_procurement_approval">Requires Procurement Approval</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Committee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Committee Modal -->
<div class="modal fade" id="editCommitteeModal" tabindex="-1" role="dialog" aria-labelledby="editCommitteeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCommitteeModalLabel">Edit Committee</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form id="editCommitteeForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_name">Committee Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_mandate">Mandate</label>
                        <textarea class="form-control" id="edit_mandate" name="mandate" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_chairman_title">Chairman Title</label>
                        <input type="text" class="form-control" id="edit_chairman_title" name="chairman_title">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="edit_requires_financial_approval" name="requires_financial_approval" value="1">
                                <label class="form-check-label" for="edit_requires_financial_approval">Requires Financial Approval</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="edit_requires_procurement_approval" name="requires_procurement_approval" value="1">
                                <label class="form-check-label" for="edit_requires_procurement_approval">Requires Procurement Approval</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mt-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="edit_is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="edit_is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteCommitteeModal" tabindex="-1" role="dialog" aria-labelledby="deleteCommitteeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCommitteeModalLabel">Delete Committee</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this committee? This action cannot be undone.
                <p class="mt-2"><strong>Note:</strong> This will not delete the members, but they will no longer be associated with this committee.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <form id="deleteCommitteeForm" method="POST" style="display: inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Committee</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#committeesTable').DataTable({
            "order": [[0, "asc"]]
        });

        // Handle edit button click
        $('.edit-committee').click(function() {
            var committeeId = $(this).data('id');
            
            // Fetch committee data
            $.get('/admin/sda/committees/' + committeeId, function(data) {
                $('#edit_name').val(data.name);
                $('#edit_description').val(data.description);
                $('#edit_mandate').val(data.mandate);
                $('#edit_chairman_title').val(data.chairman_title);
                
                // Set checkboxes
                $('#edit_requires_financial_approval').prop('checked', data.requires_financial_approval);
                $('#edit_requires_procurement_approval').prop('checked', data.requires_procurement_approval);
                $('#edit_is_active').prop('checked', data.is_active);
                
                // Update form action
                $('#editCommitteeForm').attr('action', '/admin/sda/committees/' + data.id);
                
                // Show modal
                $('#editCommitteeModal').modal('show');
            });
        });

        // Handle delete button click
        $('.delete-committee').click(function(e) {
            e.preventDefault();
            var committeeId = $(this).data('id');
            $('#deleteCommitteeForm').attr('action', '/admin/sda/committees/' + committeeId);
            $('#deleteCommitteeModal').modal('show');
        });
    });
</script>
@endpush
