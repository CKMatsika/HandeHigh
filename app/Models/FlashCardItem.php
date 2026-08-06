<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlashCardItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'flash_card_set_id',
        'front_text',
        'back_text',
        'hint',
        'sort_order',
    ];

    public function flashCardSet()
    {
        return $this->belongsTo(FlashCardSet::class);
    }

    public function results()
    {
        return $this->hasMany(FlashCardItemResult::class, 'flash_card_item_id');
    }
}
