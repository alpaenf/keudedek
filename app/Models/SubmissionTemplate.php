<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubmissionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'transaction_type_id',
        'version',
        'effective_date',
        'is_active',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(SubmissionTemplateField::class)->orderBy('order_index');
    }
}
