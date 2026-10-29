<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentReconciliationSource extends Model
{
    protected $fillable = ['batch_id', 'source_type', 'original_filename', 'header_row', 'rows_read', 'recognized_columns'];
    protected $casts = ['recognized_columns' => 'array'];
    public function batch(): BelongsTo { return $this->belongsTo(StudentReconciliationBatch::class, 'batch_id'); }
    public function rows(): HasMany { return $this->hasMany(StudentReconciliationRow::class, 'source_id'); }
}
