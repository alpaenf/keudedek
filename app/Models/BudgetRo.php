<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetRo extends Model
{
    protected $fillable = [
        'fiscal_year',
        'code',
        'full_code',
        'parent_kro_code',
        'name',
        'data_status',
        'source_type',
        'status',
    ];

    public function components(): HasMany
    {
        return $this->hasMany(BudgetComponent::class, 'parent_ro_code', 'code')
            ->where('fiscal_year', $this->fiscal_year);
    }
}
