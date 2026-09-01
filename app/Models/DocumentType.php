<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'is_required',
        'applicable_transaction_types',
        'max_file_size_mb',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'applicable_transaction_types' => 'array',
        'max_file_size_mb' => 'integer',
    ];

    public function submissionDocuments(): HasMany
    {
        return $this->hasMany(SubmissionDocument::class);
    }
}
