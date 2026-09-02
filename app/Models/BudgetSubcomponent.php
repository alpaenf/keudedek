<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetSubcomponent extends Model
{
    protected $fillable = [
        'fiscal_year',
        'code',
        'full_code',
        'parent_component_code',
        'name',
        'header_color',
        'data_status',
        'source_type',
        'status',
    ];

    /**
     * Budget allocations (pos pagu) that use this subcomponent.
     */
    public function budgetBuckets(): HasMany
    {
        return $this->hasMany(BudgetBucket::class, 'subcomponent_full_code', 'full_code');
    }
}
