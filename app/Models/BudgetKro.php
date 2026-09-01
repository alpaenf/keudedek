<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetKro extends Model
{
    protected $fillable = [
        'fiscal_year',
        'code',
        'full_code',
        'parent_activity_code',
        'name',
        'data_status',
    ];
}
