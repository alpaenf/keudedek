<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_definition_id',
        'sequence',
        'role',
        'name',
        'can_approve',
        'can_return',
        'can_reject',
        'requires_signoff',
        'reserve_trigger',
        'final_trigger',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'can_approve' => 'boolean',
        'can_return' => 'boolean',
        'can_reject' => 'boolean',
        'requires_signoff' => 'boolean',
        'reserve_trigger' => 'boolean',
        'final_trigger' => 'boolean',
    ];

    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }
}
