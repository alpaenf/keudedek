<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionTemplateField extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_template_id',
        'field_code',
        'label',
        'data_type',
        'is_required',
        'is_editable',
        'import_column',
        'validation_rules',
        'default_value',
        'order_index',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_editable' => 'boolean',
        'order_index' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(SubmissionTemplate::class, 'submission_template_id');
    }
}
