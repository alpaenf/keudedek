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
        'reference_no',
        'fiscal_year',
        'department_code',
        'transaction_type_code',
        'title',
        'account_code',
        'amount',
        'beneficiary',
        'notes',
        'validation_status',
        'error_messages',
        'parsed_items',
    ];

    protected $casts = [
        'row_number' => 'integer',
        'amount' => 'decimal:2',
        'error_messages' => 'array',
        'parsed_items' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SubmissionImportBatch::class, 'batch_id');
    }
}
