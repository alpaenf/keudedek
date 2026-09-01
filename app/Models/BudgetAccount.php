<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetAccount extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'data_status',
    ];
}
