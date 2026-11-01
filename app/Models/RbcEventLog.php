<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RbcEventLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'submission_id',
        'operation_type',
        'payload_hash',
        'response_data',
    ];

    protected $casts = [
        'response_data' => 'array',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
