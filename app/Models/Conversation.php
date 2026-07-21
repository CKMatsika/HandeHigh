<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'type',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->latest();
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['role', 'joined_at', 'last_read_at'])
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDirect($query)
    {
        return $query->where('type', 'direct');
    }

    public function scopeGroup($query)
    {
        return $query->where('type', 'group');
    }

    public function getDisplayNameAttribute()
    {
        if ($this->type === 'direct') {
            $otherParticipant = $this->participants()->where('user_id', '!=', auth()->id())->first();
            return $otherParticipant ? $otherParticipant->name : 'Unknown';
        }
        
        return $this->name ?: 'Group Chat';
    }

    public function getUnreadCountAttribute()
    {
        return $this->messages()
            ->where('sender_id', '!=', auth()->id())
            ->whereDoesntHave('readReceipts', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->count();
    }
}
