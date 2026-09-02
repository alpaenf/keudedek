<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetActivity extends Model
{
    protected $fillable = [
        'fiscal_year',
        'code',
        'full_code',
        'parent_program_code',
        'name',
        'data_status',
        'source_type',
        'status',
    ];

    public function kros(): HasMany
    {
        return $this->hasMany(BudgetKro::class, 'parent_activity_code', 'code')
            ->where('fiscal_year', $this->fiscal_year);
    }
}
