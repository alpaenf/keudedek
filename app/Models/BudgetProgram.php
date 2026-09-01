<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetProgram extends Model
{
    protected $fillable = [
        'fiscal_year',
        'code',
        'full_code',
        'name',
        'data_status',
    ];
}
