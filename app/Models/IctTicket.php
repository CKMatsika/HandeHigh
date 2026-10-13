<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IctTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'ticket_number',
        'user_id',
        'assigned_to',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'resolution_notes',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }

    public function scopeResolved($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'login_account' => 'Login / Account Access',
            'hardware_lab' => 'Computer Lab / Hardware',
            'printer_scanner' => 'Printer / Scanner',
            'network_wifi' => 'Internet / Wi-Fi / LAN',
            'erp_support' => 'ERP / System Workflow',
            default => ucfirst(str_replace('_', ' ', $this->category)),
        };
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'open' => 'amber',
            'in_progress' => 'blue',
            'resolved' => 'emerald',
            'closed' => 'slate',
            default => 'slate',
        };
    }

    public function getPriorityBadgeColorAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'red',
            'high' => 'rose',
            'medium' => 'cyan',
            'low' => 'slate',
            default => 'slate',
        };
    }
}
