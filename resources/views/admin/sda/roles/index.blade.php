@extends('layouts.app')

@section('title', 'SDA Roles Management')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <a href="{{ route('admin.sda.dashboard') }}" class="text-decoration-none text-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>
            </a>
            SDA Roles Management
        </h1>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addRoleModal">
            <i class="fas fa-plus"></i> Add New Role
        </button>
    </div>

    <!-- Roles List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Available Roles</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Export Options:</div>
                    <a class="dropdown-item" href="#"><i class="fas fa-file-pdf fa-sm fa-fw mr-2 text-gray-400"></i> Export as PDF</a>
                    <a class="dropdown-item" href="#"><i class="fas fa-file-excel fa-sm fa-fw mr-2 text-gray-400"></i> Export as Excel</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#"><i class="fas fa-print fa-sm fa-fw mr-2 text-gray-400"></i> Print</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="rolesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Role Name</th>
                            <th>Slug</th>
                            <th>Type</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $index => $role)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $role->name }}</strong>
                                @if($role->description)
                                    <div class="text-muted small">{{ $role->description }}</div>
                                @endif
                            </td>
                            <td><code>{{ $role->slug }}</code></td>
                            <td>
                                @if($role->is_executive)
                                    <span class="badge badge-primary">Executive</span>
                                @else
                                    <span class="badge badge-secondary">Regular</span>
                                @endif
                            </td>
                            <td>{{ $role->sort_order }}</td>
                            <td>
                                @if($role->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-info edit-role" data-id="{{ $role->id }}" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger delete-role" data-id="{{ $role->id }}" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1" role="dialog" aria-labelledby="addRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addRoleModalLabel">Add New Role</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.sda.roles.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Role Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_executive" name="is_executive" value="1">
                            <label class="custom-control-label" for="is_executive">This is an executive role</label>
                            <small class="form-text text-muted">Executive roles have additional permissions and responsibilities.</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="sort_order">Sort Order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="0" min="0">
                        <small class="form-text text-muted">Determines the display order of roles (lower numbers appear first).</small>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" checked>
                            <label class="custom-control-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1" role="dialog" aria-labelledby="editRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRoleModalLabel">Edit Role</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editRoleForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_name">Role Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="edit_is_executive" name="is_executive" value="1">
                            <label class="custom-control-label" for="edit_is_executive">This is an executive role</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_sort_order">Sort Order</label>
                        <input type="number" class="form-control" id="edit_sort_order" name="sort_order" min="0">
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="edit_is_active" name="is_active" value="1">
                            <label class="custom-control-label" for="edit_is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Role Confirmation Modal -->
<div class="modal fade" id="deleteRoleModal" tabindex="-1" role="dialog" aria-labelledby="deleteRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteRoleModalLabel">Confirm Deletion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this role?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Warning:</strong> This action cannot be undone. Any members assigned to this role will have their role set to the default role.
                </div>
                <div id="deleteRoleInfo" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form id="deleteRoleForm" method="POST" style="display: inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Role</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .badge {
        font-size: 0.8rem;
        font-weight: 500;
        padding: 0.35em 0.65em;
    }
    .table td {
        vertical-align: middle;
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#rolesTable').DataTable({
            "order": [[4, "asc"], [1, "asc"]], // Sort by sort_order, then by name
            "columnDefs": [
                { "orderable": false, "targets": [0, 6] } // Disable sorting on # and Actions columns
            ]
        });

        // Handle edit role button click
        $('.edit-role').click(function() {
            var roleId = $(this).data('id');
            
            // Fetch role data
            $.get('/admin/sda/roles/' + roleId + '/edit', function(data) {
                $('#edit_name').val(data.name);
                $('#edit_description').val(data.description);
                $('#edit_is_executive').prop('checked', data.is_executive);
                $('#edit_sort_order').val(data.sort_order);
                $('#edit_is_active').prop('checked', data.is_active);
                
                // Update form action
                $('#editRoleForm').attr('action', '/admin/sda/roles/' + data.id);
                
                // Show modal
                $('#editRoleModal').modal('show');
            });
        });

        // Handle delete role button click
        $('.delete-role').click(function() {
            var roleId = $(this).data('id');
            
            // Fetch role data for confirmation
            $.get('/admin/sda/roles/' + roleId, function(data) {
                var roleInfo = `
                    <p><strong>Role:</strong> ${data.name}</p>
                    <p><strong>Type:</strong> ${data.is_executive ? 'Executive' : 'Regular'}</p>
                    <p><strong>Members with this role:</strong> ${data.members_count || 0}</p>
                `;
                
                $('#deleteRoleInfo').html(roleInfo);
                
                // Update form action
                $('#deleteRoleForm').attr('action', '/admin/sda/roles/' + data.id);
                
                // Show modal
                $('#deleteRoleModal').modal('show');
            });
        });

        // Auto-generate slug from name
        $('#name').on('keyup', function() {
            var slug = $(this).val()
                .toString()
                .toLowerCase()
                .replace(/[^\w\s-]/g, '') // Remove special chars
                .replace(/\s+/g, '-')     // Replace spaces with -
                .replace(/--+/g, '-')     // Replace multiple - with single -
                .trim();
            
            $('#slug').val(slug);
        });
    });
</script>
@endpush
