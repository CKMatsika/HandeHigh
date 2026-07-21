@extends('layouts.app')

@section('title', $committee->name . ' - Committee Details')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <a href="{{ route('admin.sda.committees.index') }}" class="text-decoration-none text-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>
            </a>
            {{ $committee->name }}
        </h1>
        <div>
            <a href="#" class="btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addMemberModal">
                <i class="fas fa-user-plus fa-sm text-white-50"></i> Add Member
            </a>
            <a href="#" class="btn btn-sm btn-info shadow-sm edit-committee" data-id="{{ $committee->id }}">
                <i class="fas fa-edit fa-sm text-white-50"></i> Edit Committee
            </a>
        </div>
    </div>

    <!-- Committee Details -->
    <div class="row">
        <div class="col-lg-8">
            <!-- Committee Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Committee Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="font-weight-bold">Description</h5>
                            <p>{{ $committee->description ?? 'No description provided.' }}</p>
                            
                            <h5 class="font-weight-bold mt-4">Mandate</h5>
                            <p>{{ $committee->mandate ?? 'No mandate provided.' }}</p>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-4 border-left-primary">
                                <div class="card-body">
                                    <h5 class="font-weight-bold">Committee Status</h5>
                                    <div class="mb-3">
                                        <span class="font-weight-bold">Active:</span>
                                        @if($committee->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </div>
                                    
                                    <div class="mb-3">
                                        <span class="font-weight-bold">Financial Approval:</span>
                                        @if($committee->requires_financial_approval)
                                            <span class="badge badge-success">Yes</span>
                                            <p class="small mt-1">This committee can approve financial transactions.</p>
                                        @else
                                            <span class="badge badge-secondary">No</span>
                                        @endif
                                    </div>
                                    
                                    <div class="mb-3">
                                        <span class="font-weight-bold">Procurement Approval:</span>
                                        @if($committee->requires_procurement_approval)
                                            <span class="badge badge-success">Yes</span>
                                            <p class="small mt-1">This committee can approve procurement requests.</p>
                                        @else
                                            <span class="badge badge-secondary">No</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Committee Members -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Committee Members</h6>
                    <span class="badge badge-primary">{{ $committee->activeMembers->count() }} Members</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Appointment Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($committee->activeMembers as $member)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm mr-3">
                                                <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
                                                    {{ substr($member->user->name, 0, 1) }}
                                                </div>
                                            </div>
                                            <div>
                                                <div class="font-weight-bold">{{ $member->user->name }}</div>
                                                <div class="text-muted small">{{ $member->user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $member->role->is_executive ? 'badge-primary' : 'badge-secondary' }}">
                                            {{ $member->role->name }}
                                        </span>
                                        @if($member->hasFinancialApproval())
                                            <div class="small text-success mt-1">Financial Approver</div>
                                        @endif
                                        @if($member->hasProcurementApproval())
                                            <div class="small text-info mt-1">Procurement Approver</div>
                                        @endif
                                    </td>
                                    <td>{{ $member->appointment_date->format('M d, Y') }}</td>
                                    <td>
                                        @if($member->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-info edit-member" data-id="{{ $member->id }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-member" data-id="{{ $member->id }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="text-muted">No members found in this committee.</div>
                                        <a href="#" class="btn btn-sm btn-primary mt-2" data-toggle="modal" data-target="#addMemberModal">
                                            <i class="fas fa-user-plus"></i> Add Member
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Executive Members -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Executive Members</h6>
                </div>
                <div class="card-body">
                    @php
                        $executiveMembers = $committee->activeMembers->filter(function($member) {
                            return $member->role->is_executive;
                        });
                    @endphp

                    @if($executiveMembers->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($executiveMembers as $member)
                            <div class="list-group-item px-0">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm mr-3">
                                        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
                                            {{ substr($member->user->name, 0, 1) }}
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="font-weight-bold">{{ $member->user->name }}</div>
                                        <div class="text-muted small">{{ $member->role->name }}</div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-3">
                            <div class="text-muted mb-2">No executive members found.</div>
                            <a href="#" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addMemberModal">
                                <i class="fas fa-user-plus"></i> Add Executive
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Activities</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @php
                            $activities = \App\Models\SdaCommitteeMember::where('sda_committee_id', $committee->id)
                                ->with(['user', 'role'])
                                ->orderBy('created_at', 'desc')
                                ->limit(5)
                                ->get();
                        @endphp

                        @if($activities->count() > 0)
                            @foreach($activities as $activity)
                            <div class="timeline-item">
                                <div class="timeline-marker">
                                    <i class="fas fa-circle {{ $activity->is_active ? 'text-success' : 'text-secondary' }}"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-1">{{ $activity->user->name }}</h6>
                                        <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                    </div>
                                    <p class="mb-1">
                                        @if($activity->is_active)
                                            Added as <strong>{{ $activity->role->name }}</strong>
                                        @else
                                            Removed as <strong>{{ $activity->role->name }}</strong>
                                        @endif
                                    </p>
                                    <small class="text-muted">{{ $activity->appointment_date->format('M d, Y') }}</small>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center py-3">
                                <div class="text-muted">No recent activities found.</div>
                            </div>
                        @endif
                    </div>
                    <div class="text-center mt-3">
                        <a href="#" class="btn btn-sm btn-link">View All Activities</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1" role="dialog" aria-labelledby="addMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMemberModalLabel">Add Member to {{ $committee->name }}</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form action="{{ route('admin.sda.committees.members.store', $committee) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="user_id">Select User</label>
                        <select class="form-control select2" id="user_id" name="user_id" required>
                            <option value="">-- Select User --</option>
                            @foreach(\App\Models\User::where('school_id', auth()->user()->school_id)->get() as $user)
                                @if(!$committee->members->contains('user_id', $user->id) || $committee->members->where('user_id', $user->id)->where('is_active', false)->count() > 0)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sda_role_id">Role</label>
                        <select class="form-control" id="sda_role_id" name="sda_role_id" required>
                            <option value="">-- Select Role --</option>
                            @foreach(\App\Models\SdaRole::active()->ordered()->get() as $role)
                                <option value="{{ $role->id }}" {{ $role->slug === 'committee-member' ? 'selected' : '' }}>
                                    {{ $role->name }}
                                    @if($role->is_executive)
                                        (Executive)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="appointment_date">Appointment Date</label>
                                <input type="date" class="form-control" id="appointment_date" name="appointment_date" value="{{ now()->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="end_date">End Date (Optional)</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Member Modal -->
<div class="modal fade" id="editMemberModal" tabindex="-1" role="dialog" aria-labelledby="editMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMemberModalLabel">Edit Committee Member</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form id="editMemberForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-group">
                        <label>Member</label>
                        <p class="form-control-static" id="edit_member_name"></p>
                    </div>
                    <div class="form-group">
                        <label for="edit_sda_role_id">Role</label>
                        <select class="form-control" id="edit_sda_role_id" name="sda_role_id" required>
                            @foreach(\App\Models\SdaRole::active()->ordered()->get() as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_appointment_date">Appointment Date</label>
                                <input type="date" class="form-control" id="edit_appointment_date" name="appointment_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_end_date">End Date (Optional)</label>
                                <input type="date" class="form-control" id="edit_end_date" name="end_date">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_notes">Notes</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="edit_is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="edit_is_active">Active Member</label>
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

<!-- Delete Member Confirmation Modal -->
<div class="modal fade" id="deleteMemberModal" tabindex="-1" role="dialog" aria-labelledby="deleteMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteMemberModalLabel">Remove Member</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to remove this member from the committee?
                <div class="mt-3">
                    <strong>Member:</strong> <span id="delete_member_name"></span><br>
                    <strong>Role:</strong> <span id="delete_member_role"></span>
                </div>
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle"></i> 
                    This action will mark the member as inactive but keep the historical record.
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <form id="deleteMemberForm" method="POST" style="display: inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Remove Member</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .avatar-sm {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
    .timeline {
        position: relative;
        padding-left: 1.5rem;
    }
    .timeline-item {
        position: relative;
        padding-bottom: 1.5rem;
        padding-left: 1.5rem;
        border-left: 1px solid #e3e6f0;
    }
    .timeline-marker {
        position: absolute;
        left: -0.5rem;
        top: 0;
    }
    .timeline-content {
        padding-left: 0.5rem;
    }
</style>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize Select2 for user selection
        $('.select2').select2({
            placeholder: 'Search for a user...',
            width: '100%'
        });

        // Handle edit member button click
        $('.edit-member').click(function() {
            var memberId = $(this).data('id');
            
            // Fetch member data
            $.get('/admin/sda/members/' + memberId + '/edit', function(data) {
                $('#edit_member_name').text(data.user.name);
                $('#edit_sda_role_id').val(data.sda_role_id).trigger('change');
                $('#edit_appointment_date').val(data.appointment_date);
                $('#edit_end_date').val(data.end_date);
                $('#edit_notes').val(data.notes);
                $('#edit_is_active').prop('checked', data.is_active);
                
                // Update form action
                $('#editMemberForm').attr('action', '/admin/sda/members/' + data.id);
                
                // Show modal
                $('#editMemberModal').modal('show');
            });
        });

        // Handle delete member button click
        $('.delete-member').click(function() {
            var memberId = $(this).data('id');
            
            // Fetch member data for confirmation
            $.get('/admin/sda/members/' + memberId, function(data) {
                $('#delete_member_name').text(data.user.name);
                $('#delete_member_role').text(data.role.name);
                
                // Update form action
                $('#deleteMemberForm').attr('action', '/admin/sda/members/' + data.id);
                
                // Show modal
                $('#deleteMemberModal').modal('show');
            });
        });

        // Handle edit committee button click
        $('.edit-committee').click(function() {
            var committeeId = $(this).data('id');
            
            // Fetch committee data
            $.get('/admin/sda/committees/' + committeeId + '/edit', function(data) {
                $('#edit_name').val(data.name);
                $('#edit_description').val(data.description);
                $('#edit_mandate').val(data.mandate);
                $('#edit_chairman_title').val(data.chairman_title);
                $('#edit_requires_financial_approval').prop('checked', data.requires_financial_approval);
                $('#edit_requires_procurement_approval').prop('checked', data.requires_procurement_approval);
                $('#edit_is_active').prop('checked', data.is_active);
                
                // Update form action
                $('#editCommitteeForm').attr('action', '/admin/sda/committees/' + data.id);
                
                // Show modal
                $('#editCommitteeModal').modal('show');
            });
        });
    });
</script>
@endpush
