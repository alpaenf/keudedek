<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetSubaccount extends Model
{
    protected $fillable = [
        'code',
        'parent_account_code',
        'name',
        'data_status',
    ];
}
