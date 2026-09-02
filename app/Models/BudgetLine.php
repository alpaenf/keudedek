<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_version_id',
        'department_id',
        'funding_source_id',
        'budget_bucket_id',
        'rba_sequence_no',
        'budget_program_id',
        'budget_activity_id',
        'budget_kro_id',
        'budget_ro_id',
        'budget_component_id',
        'budget_subcomponent_id',
        'budget_account_id',
        'budget_subaccount_id',
        'description',
        'volume',
        'unit',
        'unit_price',
        'budget_amount',
        'import_history_id',
        'source_row_index',
        'status',
        'source_metadata',
    ];

    protected $casts = [
        'volume' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'budget_amount' => 'decimal:2',
        'source_metadata' => 'array',
        'source_row_index' => 'integer',
    ];

    /**
     * 1. Versioning & Organization Context
     */
    public function budgetVersion(): BelongsTo
    {
        return $this->belongsTo(BudgetVersion::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function fundingSource(): BelongsTo
    {
        return $this->belongsTo(FundingSource::class);
    }

    /**
     * 2. Control Bucket Relationship (Aggregation Target)
     */
    public function budgetBucket(): BelongsTo
    {
        return $this->belongsTo(BudgetBucket::class);
    }

    /**
     * 3. Hierarchy Nomenklatur Relations
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(BudgetProgram::class, 'budget_program_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(BudgetActivity::class, 'budget_activity_id');
    }

    public function kro(): BelongsTo
    {
        return $this->belongsTo(BudgetKro::class, 'budget_kro_id');
    }

    public function ro(): BelongsTo
    {
        return $this->belongsTo(BudgetRo::class, 'budget_ro_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(BudgetComponent::class, 'budget_component_id');
    }

    public function subcomponent(): BelongsTo
    {
        return $this->belongsTo(BudgetSubcomponent::class, 'budget_subcomponent_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(BudgetAccount::class, 'budget_account_id');
    }

    public function subaccount(): BelongsTo
    {
        return $this->belongsTo(BudgetSubaccount::class, 'budget_subaccount_id');
    }

    /**
     * 4. Audit / Import Source
     */
    public function importHistory(): BelongsTo
    {
        return $this->belongsTo(ImportHistory::class, 'import_history_id');
    }

    /**
     * 5. Transactions referencing this Budget Line
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'budget_line_id');
    }
}
