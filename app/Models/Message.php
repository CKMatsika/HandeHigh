<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'conversation_id',
        'sender_id',
        'content',
        'type',
        'file_path',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function readReceipts()
    {
        return $this->hasMany(MessageRead::class);
    }

    public function markAsRead($userId)
    {
        if (!$this->is_read && $this->sender_id !== $userId) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
            
            $this->readReceipts()->firstOrCreate([
                'user_id' => $userId,
            ]);
        }
    }

    public function scopeUnread($query, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        return $query->where('sender_id', '!=', $userId)
            ->whereDoesntHave('readReceipts', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            });
    }
}
