<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetComponent extends Model
{
    protected $fillable = [
        'fiscal_year',
        'code',
        'full_code',
        'parent_ro_code',
        'name',
        'data_status',
    ];
}
