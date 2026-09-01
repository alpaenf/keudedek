<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetRo extends Model
{
    protected $fillable = [
        'fiscal_year',
        'code',
        'full_code',
        'parent_kro_code',
        'name',
        'data_status',
    ];
}
