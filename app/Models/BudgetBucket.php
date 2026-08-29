<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetBucket extends Model
{
    use HasFactory;

    protected $fillable = [
        'fiscal_year_id',
        'department_id',
        'funding_source_id',
        'account_code',
        'account_name',
        'initial_budget',
        'allocated_budget',
        'reserved_budget',
        'realized_budget',
        'available_balance',
    ];

    protected $casts = [
        'initial_budget' => 'decimal:2',
        'allocated_budget' => 'decimal:2',
        'reserved_budget' => 'decimal:2',
        'realized_budget' => 'decimal:2',
        'available_balance' => 'decimal:2',
    ];

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function fundingSource(): BelongsTo
    {
        return $this->belongsTo(FundingSource::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(BudgetRevision::class);
    }

    public function earlyWarnings(): HasMany
    {
        return $this->hasMany(EarlyWarning::class);
    }

    public function recalculateAvailableBalance(): void
    {
        $this->available_balance = $this->allocated_budget - $this->reserved_budget - $this->realized_budget;
        $this->save();
    }
}
