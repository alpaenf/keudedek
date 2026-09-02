<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetComponent extends Model
{
    protected $fillable = [
        'fiscal_year',
        'code',
        'full_code',
        'parent_ro_code',
        'name',
        'data_status',
        'source_type',
        'status',
    ];

    public function subcomponents(): HasMany
    {
        return $this->hasMany(BudgetSubcomponent::class, 'parent_component_code', 'code')
            ->where('fiscal_year', $this->fiscal_year);
    }
}
