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
        'target_object',
        'severity',
        'department_id',
        'budget_bucket_id',
        'submission_id',
        'budget_version_id',
        'current_value',
        'threshold_value',
        'message',
        'reason',
        'status',
        'lifecycle_state',
        'rule_config_id',
        'acknowledged_by',
        'first_triggered_at',
        'context_metadata',
    ];

    protected $casts = [
        'current_value' => 'decimal:2',
        'threshold_value' => 'decimal:2',
        'first_triggered_at' => 'datetime',
        'context_metadata' => 'array',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function budgetBucket(): BelongsTo
    {
        return $this->belongsTo(BudgetBucket::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function budgetVersion(): BelongsTo
    {
        return $this->belongsTo(BudgetVersion::class);
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
