<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'revision_number',
        'budget_bucket_id',
        'previous_amount',
        'revised_amount',
        'difference',
        'reason',
        'approved_by',
    ];

    protected $casts = [
        'previous_amount' => 'decimal:2',
        'revised_amount' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    public function budgetBucket(): BelongsTo
    {
        return $this->belongsTo(BudgetBucket::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
