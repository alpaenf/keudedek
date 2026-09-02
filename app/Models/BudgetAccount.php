<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetAccount extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'data_status',
        'effective_year',
        'source_type',
        'status',
    ];

    protected $casts = [
        'effective_year' => 'integer',
    ];

    public function subaccounts(): HasMany
    {
        return $this->hasMany(BudgetSubaccount::class, 'parent_account_code', 'code');
    }

    /**
     * Budget allocations (pos pagu) that use this account code.
     */
    public function budgetBuckets(): HasMany
    {
        return $this->hasMany(BudgetBucket::class, 'account_code', 'code');
    }
}
