<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlashCardItemResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_session_id',
        'flash_card_item_id',
        'confidence',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function studySession()
    {
        return $this->belongsTo(FlashCardStudySession::class, 'study_session_id');
    }

    public function flashCardItem()
    {
        return $this->belongsTo(FlashCardItem::class, 'flash_card_item_id');
    }
}
