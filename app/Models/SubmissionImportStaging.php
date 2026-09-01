<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionImportStaging extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'row_number',
        'evidence_number',
        'transaction_date',
        'reference_no',
        'fiscal_year',
        'department_code',
        'study_program_id',
        'study_program_code',
        'transaction_type_code',
        'title',
        'account_code',
        'subcomponent_code',
        'budget_control_key',
        'matched_bucket_id',
        'matched_hierarchy',
        'amount',
        'beneficiary',
        'notes',
        'validation_status',
        'duplicate_status',
        'error_messages',
        'parsed_items',
    ];

    protected $casts = [
        'row_number' => 'integer',
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'error_messages' => 'array',
        'parsed_items' => 'array',
        'matched_hierarchy' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SubmissionImportBatch::class, 'batch_id');
    }
}
