<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RuleConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_code',
        'rule_name',
        'category',
        'indicator_type',
        'parameters',
        'parameter_version',
        'is_active',
        'description',
    ];

    protected $casts = [
        'parameters' => 'array',
        'parameter_version' => 'integer',
        'is_active' => 'boolean',
    ];

    public function earlyWarnings(): HasMany
    {
        return $this->hasMany(EarlyWarning::class);
    }
}
