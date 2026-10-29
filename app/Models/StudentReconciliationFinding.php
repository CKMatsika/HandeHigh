<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentReconciliationFinding extends Model
{
    protected $fillable = ['batch_id', 'source_row_id', 'student_id', 'invoice_id', 'code', 'severity', 'field', 'source_value', 'erp_value', 'message', 'status', 'reviewed_by', 'reviewed_at', 'review_note'];
    protected $casts = ['reviewed_at' => 'datetime'];
    public function batch(): BelongsTo { return $this->belongsTo(StudentReconciliationBatch::class, 'batch_id'); }
    public function sourceRow(): BelongsTo { return $this->belongsTo(StudentReconciliationRow::class, 'source_row_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
