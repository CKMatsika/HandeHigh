<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScholarshipSponsorReceipt extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'decimal:2', 'received_on' => 'date', 'reversed_on' => 'date'];
    public function application() { return $this->belongsTo(ScholarshipApplication::class, 'scholarship_application_id'); }
    public function journalBatch() { return $this->belongsTo(JournalBatch::class); }
}
