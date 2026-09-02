<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FundingSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_mvp_enabled',
        'is_active',
        'status',
        'external_system',
    ];

    protected $casts = [
        'is_mvp_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function budgetBuckets(): HasMany
    {
        return $this->hasMany(BudgetBucket::class);
    }

    public function budgetVersions(): HasMany
    {
        return $this->hasMany(BudgetVersion::class);
    }
}
