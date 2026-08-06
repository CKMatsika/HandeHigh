<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlashCardStudySession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'flash_card_set_id',
        'started_at',
        'completed_at',
        'cards_studied',
        'cards_confident',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function flashCardSet()
    {
        return $this->belongsTo(FlashCardSet::class);
    }

    public function results()
    {
        return $this->hasMany(FlashCardItemResult::class, 'study_session_id');
    }
}
