<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EarlyWarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_code',
        'severity',
        'department_id',
        'budget_bucket_id',
        'current_value',
        'threshold_value',
        'message',
        'status',
        'lifecycle_state',
        'rule_config_id',
        'acknowledged_by',
    ];

    protected $casts = [
        'current_value' => 'decimal:2',
        'threshold_value' => 'decimal:2',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function budgetBucket(): BelongsTo
    {
        return $this->belongsTo(BudgetBucket::class);
    }

    public function ruleConfig(): BelongsTo
    {
        return $this->belongsTo(RuleConfig::class);
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }
}
