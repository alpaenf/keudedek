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
        'department_code',
        'fiscal_year',
        'funding_source_code',
        'account_code',
        'account_name',
        'initial_budget',
        'status',
        'error_message',
    ];

    public function importHistory(): BelongsTo
    {
        return $this->belongsTo(ImportHistory::class);
    }
}
