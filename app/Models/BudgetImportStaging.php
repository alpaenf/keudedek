<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetImportStaging extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_history_id',
        'row_number',
        'rba_sequence_no',
        'department_code',
        'department_id',
        'fiscal_year',
        'funding_source_code',
        'program_code',
        'program_name',
        'activity_code',
        'activity_name',
        'kro_code',
        'kro_name',
        'ro_code',
        'ro_name',
        'component_code',
        'component_name',
        'subcomponent_code',
        'subcomponent_name',
        'subcomponent_full_code',
        'account_code',
        'account_name',
        'description',
        'volume',
        'unit',
        'unit_price',
        'initial_budget',
        'status',
        'error_message',
        'validation_errors',
        'is_duplicate',
        'matched_bucket_id',
    ];

    protected $casts = [
        'volume' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'initial_budget' => 'decimal:2',
        'is_duplicate' => 'boolean',
        'validation_errors' => 'array',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function matchedBucket(): BelongsTo
    {
        return $this->belongsTo(BudgetBucket::class, 'matched_bucket_id');
    }

    public function importHistory(): BelongsTo
    {
        return $this->belongsTo(ImportHistory::class);
    }
}
