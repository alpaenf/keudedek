<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceIndicator extends Model
{
    protected $fillable = [
        'code',
        'objective_code',
        'name',
        'unit',
        'target_2026',
        'framework',
        'data_status',
    ];
}
