<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'filename',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stagings(): HasMany
    {
        return $this->hasMany(BudgetImportStaging::class);
    }
}
