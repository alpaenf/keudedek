<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubmissionImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_number',
        'user_id',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'status',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'valid_rows' => 'integer',
        'invalid_rows' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stagings(): HasMany
    {
        return $this->hasMany(SubmissionImportStaging::class, 'batch_id')->orderBy('row_number');
    }
}
