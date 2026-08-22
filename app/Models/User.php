<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\SdaCommitteeMember;
use App\Models\StudentPosition;
use App\Models\StudentClub;
use App\Models\StudentSport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'school_id',
        'phone',
        'address',
        'metadata',
        'is_active',
        'is_sda_member',
        'sda_positions_summary',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'metadata' => 'array',
            'is_active' => 'boolean',
            'is_sda_member' => 'boolean',
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the SDA committee memberships for this user.
     */
    public function sdaCommitteeMemberships()
    {
        return $this->hasMany(SdaCommitteeMember::class);
    }

    /**
     * Get the active SDA committee memberships for this user.
     */
    public function activeSdaCommitteeMemberships()
    {
        return $this->sdaCommitteeMemberships()->active()->current();
    }

    /**
     * Get the SDA committees this user belongs to.
     */
    public function sdaCommittees()
    {
        return $this->belongsToMany(SdaCommittee::class, 'sda_committee_members')
            ->withPivot(['sda_role_id', 'appointment_date', 'end_date', 'is_active', 'notes'])
            ->withTimestamps();
    }

    /**
     * Get the active SDA committees for this user.
     */
    public function activeSdaCommittees()
    {
        return $this->sdaCommittees()->wherePivot('is_active', true);
    }

    /**
     * Check if user is an SDA member.
     */
    public function isSdaMember()
    {
        return $this->is_sda_member || $this->activeSdaCommitteeMemberships()->exists();
    }

    /**
     * Get user's SDA positions summary.
     */
    public function getSdaPositionsSummary()
    {
        $memberships = $this->activeSdaCommitteeMemberships()
            ->with(['committee', 'role'])
            ->get();

        if ($memberships->isEmpty()) {
            return null;
        }

        return $memberships->map(function ($membership) {
            return [
                'committee' => $membership->committee->name,
                'role' => $membership->role->name,
                'is_executive' => $membership->role->is_executive,
                'has_financial_approval' => $membership->hasFinancialApproval(),
                'has_procurement_approval' => $membership->hasProcurementApproval(),
            ];
        });
    }

    /**
     * Update SDA positions summary.
     */
    public function updateSdaPositionsSummary()
    {
        $positions = $this->getSdaPositionsSummary();
        
        $this->update([
            'is_sda_member' => $positions && $positions->isNotEmpty(),
            'sda_positions_summary' => $positions ? json_encode($positions) : null,
        ]);
    }

    /**
     * Check if user has financial approval authority in any committee.
     */
    public function hasFinancialApprovalAuthority()
    {
        return $this->activeSdaCommitteeMemberships()
            ->whereHas('committee', function ($query) {
                $query->where('requires_financial_approval', true);
            })
            ->whereHas('role', function ($query) {
                $query->where('is_executive', true);
            })
            ->exists();
    }

    /**
     * Check if user has procurement approval authority in any committee.
     */
    public function hasProcurementApprovalAuthority()
    {
        return $this->activeSdaCommitteeMemberships()
            ->whereHas('committee', function ($query) {
                $query->where('requires_procurement_approval', true);
            })
            ->whereHas('role', function ($query) {
                $query->where('is_executive', true);
            })
            ->exists();
    }

    /**
     * Get all SDA committee memberships for this user.
     */
    public function sdaCommitteeMembers()
    {
        return $this->hasMany(SdaCommitteeMember::class, 'user_id');
    }

    /**
     * Get student positions for this user.
     */
    public function studentPositions()
    {
        return $this->hasMany(StudentPosition::class, 'student_id');
    }

    /**
     * Get current student positions for this user.
     */
    public function currentPositions()
    {
        return $this->studentPositions()->current();
    }

    /**
     * Get student clubs for this user.
     */
    public function studentClubs()
    {
        return $this->hasMany(StudentClub::class, 'student_id');
    }

    /**
     * Get current student clubs for this user.
     */
    public function currentClubs()
    {
        return $this->studentClubs()->current();
    }

    /**
     * Get student sports for this user.
     */
    public function studentSports()
    {
        return $this->hasMany(StudentSport::class, 'student_id');
    }

    /**
     * Get current student sports for this user.
     */
    public function currentSports()
    {
        return $this->studentSports()->current();
    }

    /**
     * Get student's SDA position/role.
     */
    public function getSdaPositionAttribute()
    {
        $membership = $this->sdaCommitteeMembers()->with('role')->first();
        return $membership ? $membership->role->name : null;
    }

    /**
     * Check if user has any leadership positions.
     */
    public function hasLeadershipRoles()
    {
        return $this->currentPositions()->exists();
    }
}
