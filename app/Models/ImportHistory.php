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

    protected $appends = [
        'import_batch_id',
    ];

    public function getImportBatchIdAttribute(): string
    {
        $dateStr = $this->created_at ? $this->created_at->format('Ymd') : date('Ymd');

        return 'BATCH-PAGU-'.$dateStr.'-'.str_pad($this->id, 3, '0', STR_PAD_LEFT);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stagings(): HasMany
    {
        return $this->hasMany(BudgetImportStaging::class);
    }
}
