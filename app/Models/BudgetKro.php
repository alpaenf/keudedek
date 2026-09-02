<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetKro extends Model
{
    protected $fillable = [
        'fiscal_year',
        'code',
        'full_code',
        'parent_activity_code',
        'name',
        'data_status',
        'source_type',
        'status',
    ];

    public function ros(): HasMany
    {
        return $this->hasMany(BudgetRo::class, 'parent_kro_code', 'code')
            ->where('fiscal_year', $this->fiscal_year);
    }
}
