<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MigrationStagingRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'migration_batch_id',
        'school_id',
        'row_number',
        'accounting_date',
        'reference_number',
        'description',
        'amount',
        'direction',
        'classification',
        'account_code',
        'vote_code',
        'student_admission_number',
        'vendor_name',
        'bank_identifier',
        'raw_data',
        'parsed_data',
        'matched_entity_type',
        'matched_entity_id',
        'status',
        'validation_errors',
        'journal_batch_id',
        'idempotency_key',
    ];

    protected $casts = [
        'accounting_date' => 'date',
        'amount' => 'decimal:2',
        'raw_data' => 'array',
        'parsed_data' => 'array',
        'validation_errors' => 'array',
    ];

    public function batch()
    {
        return $this->belongsTo(MigrationBatch::class, 'migration_batch_id');
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function journalBatch()
    {
        return $this->belongsTo(JournalBatch::class);
    }
}
