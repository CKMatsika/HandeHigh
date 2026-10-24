<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScholarshipApproval extends Model
{
    protected static function booted(): void
    {
        static::updating(function ($request) {
            if ($request->getRawOriginal('status') !== 'pending' && $request->isDirty(['school_id', 'action', 'subject_type', 'subject_id', 'payload', 'requested_by', 'approved_by', 'approval_reference'])) {
                throw new \LogicException('Checked approval terms are immutable.');
            }
        });
        static::deleting(fn ($request) => throw new \LogicException('Approval history cannot be deleted.'));
    }

    protected $guarded = ['id'];

    protected $casts = ['payload' => 'array', 'executed_at' => 'datetime'];
}
