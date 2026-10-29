<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentReconciliationRow extends Model
{
    protected $fillable = ['batch_id', 'source_id', 'source_row_number', 'normalized_data', 'identity_key', 'match_status', 'match_confidence', 'matched_student_id', 'candidate_student_ids', 'erp_invoice_total', 'erp_invoice_ids'];
    protected $casts = ['normalized_data' => 'array', 'candidate_student_ids' => 'array', 'erp_invoice_ids' => 'array', 'erp_invoice_total' => 'decimal:2'];
    public function batch(): BelongsTo { return $this->belongsTo(StudentReconciliationBatch::class, 'batch_id'); }
    public function source(): BelongsTo { return $this->belongsTo(StudentReconciliationSource::class, 'source_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class, 'matched_student_id'); }
    public function findings(): HasMany { return $this->hasMany(StudentReconciliationFinding::class, 'source_row_id'); }
}
