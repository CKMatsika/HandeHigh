<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentReconciliationBatch extends Model
{
    protected $fillable = ['school_id', 'created_by', 'academic_year', 'term', 'complete_register', 'complete_invoice_register', 'status', 'source_count', 'source_rows_count', 'matched_rows_count', 'open_findings_count', 'summary'];
    protected $casts = ['complete_register' => 'boolean', 'complete_invoice_register' => 'boolean', 'summary' => 'array'];
    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function sources(): HasMany { return $this->hasMany(StudentReconciliationSource::class, 'batch_id'); }
    public function rows(): HasMany { return $this->hasMany(StudentReconciliationRow::class, 'batch_id'); }
    public function findings(): HasMany { return $this->hasMany(StudentReconciliationFinding::class, 'batch_id'); }
}
