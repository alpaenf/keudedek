<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'fiscal_year_id',
        'funding_source_id',
        'revision_no',
        'version_label',
        'status',
        'effective_at',
        'source_reference',
        'import_history_id',
        'source_filename',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'effective_at' => 'date',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function fundingSource(): BelongsTo
    {
        return $this->belongsTo(FundingSource::class);
    }

    public function importHistory(): BelongsTo
    {
        return $this->belongsTo(ImportHistory::class, 'import_history_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function budgetBuckets(): HasMany
    {
        return $this->hasMany(BudgetBucket::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }
}
