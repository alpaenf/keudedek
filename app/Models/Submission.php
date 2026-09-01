<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_number',
        'evidence_number',
        'transaction_date',
        'reference_no',
        'title',
        'department_id',
        'study_program_id',
        'fiscal_year_id',
        'transaction_type_id',
        'submission_template_id',
        'budget_bucket_id',
        'amount',
        'beneficiary_name',
        'status',
        'current_workflow_step_id',
        'created_by',
        'notes',
        'attachment_path',
        'electronic_signoff_hash',
        'background_narrative',
        'objective_narrative',
        'target_output',
        'performance_indicator_code',
        'performance_indicator_name',
        'subcomponent_full_code',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SubmissionTemplate::class, 'submission_template_id');
    }

    public function budgetBucket(): BelongsTo
    {
        return $this->belongsTo(BudgetBucket::class);
    }

    public function currentWorkflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_workflow_step_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SubmissionItem::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SubmissionDocument::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class)->latest();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(SubmissionStatusHistory::class)->latest();
    }
}
