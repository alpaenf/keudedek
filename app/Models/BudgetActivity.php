<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetActivity extends Model
{
    protected $fillable = [
        'fiscal_year',
        'code',
        'full_code',
        'parent_program_code',
        'name',
        'data_status',
    ];
}
