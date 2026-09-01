<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetSubcomponent extends Model
{
    protected $fillable = [
        'fiscal_year',
        'code',
        'full_code',
        'parent_component_code',
        'name',
        'header_color',
        'data_status',
    ];
}
