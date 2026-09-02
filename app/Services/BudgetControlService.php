<?php

namespace App\Services;

use App\Models\BudgetBucket;
use App\Models\BudgetLine;
use App\Models\FiscalYear;
use App\Models\Submission;
use App\Models\SubmissionStatusHistory;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BudgetControlService
{
    /**
     * Canonical Transaction Status Definitions
     */
    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_DIAJUKAN = 'PROCESSING'; // Canonical active commitment state

    public const STATUS_RETURNED = 'RETURNED';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_SELESAI = 'FINAL'; // Canonical internal realization state

    /**
     * Status groupings for mathematical evaluation
     */
    public const COMMITMENT_STATUSES = ['PROCESSING', 'SUBMITTED', 'UNDER_REVIEW', 'REVIEW', 'APPROVED', 'RESERVED'];

    public const REALIZATION_STATUSES = ['FINAL', 'COMPLETED'];

    public const RELEASED_STATUSES = ['DRAFT', 'RETURNED', 'REVISION_REQUIRED', 'REJECTED', 'CANCELLED'];

    /**
     * RBC-008: Budget Version Integrity Check
     */
    public static function checkVersionIntegrity(BudgetBucket $bucket): void
    {
        $version = $bucket->budgetVersion;
        if (! $version || $version->status !== 'ACTIVE') {
            throw new InvalidArgumentException("RBC-008: Transaksi tidak dapat diproses karena Versi Anggaran ({$version?->revision_no}) tidak aktif / berstatus arsip.");
        }

        $fiscalYear = $bucket->fiscalYear;
        if ($fiscalYear && ($fiscalYear->status !== 'ACTIVE' || $fiscalYear->is_locked)) {
            throw new InvalidArgumentException("RBC-008: Transaksi tidak dapat diproses karena Tahun Anggaran ({$fiscalYear->year}) berstatus terkunci.");
        }
    }

    /**
     * RBC-007: Scope Guard Check
     */
    public static function checkScopeGuard(User $user, BudgetLine $line, ?int $departmentId = null): void
    {
        $targetDeptId = (int) ($departmentId ?? $line->department_id);

        if ($user->hasRole(['PTK', 'KAJUR', 'KAPRODI'])) {
            if ((int) $user->department_id !== (int) $line->department_id || (int) $user->department_id !== $targetDeptId) {
                throw new InvalidArgumentException('RBC-007: Akses Ditolak: Baris anggaran yang dipilih berada di luar unit/jurusan kerja Anda.');
            }
        }
    }

    /**
     * RBC-006: Duplicate Reference Warning / Validation
     */
    public static function checkDuplicateReference(?string $evidenceNumber, int $departmentId, ?int $excludeSubmissionId = null): ?string
    {
        if (empty(trim((string) $evidenceNumber))) {
            return null;
        }

        $query = Submission::where('evidence_number', trim($evidenceNumber))
            ->where('department_id', $departmentId);

        if ($excludeSubmissionId) {
            $query->where('id', '!=', $excludeSubmissionId);
        }

        if ($query->exists()) {
            return "RBC-006: Peringatan: Nomor bukti/kuitansi '{$evidenceNumber}' sudah pernah dicatat di unit ini.";
        }

        return null;
    }

    /**
     * Core RBC Balance Check & Atomic Creation / Submit
     *
     * RBC-001: Requested Amount > Available -> BLOCK
     * RBC-002: Draft does not reserve/lock balance
     * RBC-003: When DRAFT -> DIAJUKAN, amount enters active commitment
     */
    public static function recordTransaction(array $data, User $user): Submission
    {
        return DB::transaction(function () use ($data, $user) {
            $line = BudgetLine::with(['budgetBucket', 'subcomponent', 'account'])
                ->where('id', $data['budget_line_id'])
                ->firstOrFail();

            // RBC-007: Scope Guard
            $departmentId = $user->hasRole(['PTK', 'KAJUR', 'KAPRODI'])
                ? (int) $user->department_id
                : (int) ($data['department_id'] ?? $user->department_id ?? $line->department_id);

            self::checkScopeGuard($user, $line, $departmentId);

            // Resolve Control Bucket
            $bucket = $line->budgetBucket ?: BudgetCalculationService::resolveControlBucket($line);
            if (! $bucket && ! empty($data['budget_bucket_id'])) {
                $bucket = BudgetBucket::find($data['budget_bucket_id']);
            }

            if (! $bucket) {
                throw new InvalidArgumentException('Gagal: Control Bucket untuk baris anggaran ini tidak ditemukan.');
            }

            // Lock Control Bucket row for update to prevent concurrent overbudget (Race Condition Protection)
            $bucket = BudgetBucket::where('id', $bucket->id)->lockForUpdate()->firstOrFail();

            // RBC-008: Budget Version Integrity
            self::checkVersionIntegrity($bucket);

            // RBC-006: Duplicate reference
            self::checkDuplicateReference($data['evidence_number'] ?? null, $departmentId);

            $amount = (float) $data['amount'];
            $submitAction = strtoupper($data['submit_action'] ?? self::STATUS_DIAJUKAN);
            $targetStatus = in_array($submitAction, ['PROCESSING', 'SUBMITTED', 'AJUKAN'])
                ? self::STATUS_DIAJUKAN
                : ($submitAction === self::STATUS_DRAFT ? self::STATUS_DRAFT : self::STATUS_DIAJUKAN);

            // RBC-001 & RBC-003: Overbudget check on submit
            if ($targetStatus === self::STATUS_DIAJUKAN) {
                if ($amount > (float) $bucket->available_balance) {
                    $defisit = $amount - (float) $bucket->available_balance;
                    throw new InvalidArgumentException(
                        'RBC-001: Overbudget Protection: Nominal pengajuan (Rp '.number_format($amount, 0, ',', '.').') melebihi saldo tersedia (Rp '.number_format($bucket->available_balance, 0, ',', '.').'). Defisit: Rp '.number_format($defisit, 0, ',', '.').'. Transaksi diblokir.'
                    );
                }

                // RBC-003: Enter Active Commitment
                $bucket->reserved_budget += $amount;
                $bucket->available_balance = max(0, (float) $bucket->allocated_budget - (float) $bucket->reserved_budget - (float) $bucket->realized_budget);
                $bucket->save();
            }
            // RBC-002: DRAFT does not touch reserved_budget or available_balance

            $fiscalYear = FiscalYear::where('status', 'ACTIVE')->first() ?? $bucket->fiscalYear;
            $submissionNumber = 'TRX/'.date('Y/m').'/'.str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            $defaultTrxTypeId = TransactionType::where('is_active', true)->first()?->id ?? TransactionType::first()?->id;

            $submission = Submission::create([
                'submission_number' => $submissionNumber,
                'evidence_number' => $data['evidence_number'],
                'transaction_date' => $data['transaction_date'],
                'reference_no' => $data['reference_no'] ?? null,
                'title' => $data['title'],
                'department_id' => $departmentId,
                'study_program_id' => $data['study_program_id'] ?? $user->study_program_id,
                'fiscal_year_id' => $fiscalYear->id,
                'transaction_type_id' => $data['transaction_type_id'] ?? $defaultTrxTypeId,
                'budget_bucket_id' => $bucket->id,
                'budget_line_id' => $line->id,
                'amount' => $amount,
                'beneficiary_name' => $data['beneficiary_name'] ?? null,
                'status' => $targetStatus,
                'created_by' => $user->id,
                'notes' => $data['notes'] ?? null,
                'subcomponent_full_code' => $line->subcomponent?->full_code ?? $bucket->subcomponent_full_code,
            ]);

            SubmissionStatusHistory::create([
                'submission_id' => $submission->id,
                'from_status' => null,
                'to_status' => $targetStatus,
                'actor_id' => $user->id,
                'role' => $user->role,
                'notes' => $targetStatus === self::STATUS_DRAFT ? 'Draft transaksi dicatat.' : 'Transaksi dicatat dan masuk ke Active Commitment (Diajukan).',
            ]);

            AuditLogService::log(
                'RECORD_TRANSACTION',
                Submission::class,
                $submission->id,
                null,
                [
                    'amount' => $amount,
                    'status' => $targetStatus,
                    'bucket_id' => $bucket->id,
                    'available_balance' => $bucket->available_balance,
                ]
            );

            return $submission;
        });
    }

    /**
     * Transition existing submission state atomically.
     *
     * RBC-003: DRAFT / RETURNED -> DIAJUKAN (Re-reserve commitment with RBC-001 check)
     * RBC-004: DIAJUKAN -> RETURNED / REJECTED / CANCELLED (Release commitment)
     * RBC-005: DIAJUKAN -> SELESAI (Commitment decreases, realization increases, available invariant preserved)
     */
    public static function transitionStatus(
        Submission $submission,
        string $targetStatus,
        User $actor,
        ?string $notes = null,
        ?float $newAmount = null
    ): Submission {
        return DB::transaction(function () use ($submission, $targetStatus, $actor, $notes, $newAmount) {
            $sub = Submission::where('id', $submission->id)->lockForUpdate()->firstOrFail();
            $bucket = BudgetBucket::where('id', $sub->budget_bucket_id)->lockForUpdate()->firstOrFail();

            self::checkVersionIntegrity($bucket);

            $oldStatus = $sub->status;
            $oldAmount = (float) $sub->amount;
            $effectiveAmount = $newAmount !== null ? (float) $newAmount : $oldAmount;

            $wasInCommitment = in_array($oldStatus, self::COMMITMENT_STATUSES);
            $wasInRealization = in_array($oldStatus, self::REALIZATION_STATUSES);

            $willBeInCommitment = in_array($targetStatus, self::COMMITMENT_STATUSES);
            $willBeInRealization = in_array($targetStatus, self::REALIZATION_STATUSES);
            $willBeReleased = in_array($targetStatus, self::RELEASED_STATUSES);

            // =========================================================================
            // Case 1: Transition TO DIAJUKAN (from DRAFT or RETURNED or Amount changed)
            // =========================================================================
            if ($willBeInCommitment) {
                if (! $wasInCommitment) {
                    // RBC-001: Available check for new commitment
                    if ($effectiveAmount > (float) $bucket->available_balance) {
                        $defisit = $effectiveAmount - (float) $bucket->available_balance;
                        throw new InvalidArgumentException(
                            'RBC-001: Overbudget: Nominal (Rp '.number_format($effectiveAmount, 0, ',', '.').') melebihi saldo tersedia (Rp '.number_format($bucket->available_balance, 0, ',', '.').'). Defisit: Rp '.number_format($defisit, 0, ',', '.').'.'
                        );
                    }
                    $bucket->reserved_budget += $effectiveAmount;
                } else {
                    // Already in commitment, but amount changed
                    $delta = $effectiveAmount - $oldAmount;
                    if ($delta > 0 && $delta > (float) $bucket->available_balance) {
                        throw new InvalidArgumentException('RBC-001: Peningkatan nominal melebihi sisa saldo tersedia.');
                    }
                    $bucket->reserved_budget += $delta;
                }
            }

            // =========================================================================
            // Case 2: RBC-004: DIAJUKAN -> DIKEMBALIKAN / DITOLAK / DIBATALKAN
            // Release commitment
            // =========================================================================
            if ($wasInCommitment && $willBeReleased) {
                $bucket->reserved_budget = max(0, (float) $bucket->reserved_budget - $oldAmount);
            }

            // =========================================================================
            // Case 3: RBC-005: DIAJUKAN -> SELESAI (Finalize internal realization)
            // Commitment decreases, internal realization increases. Available is NOT double-deducted!
            // =========================================================================
            if ($wasInCommitment && $willBeInRealization) {
                $bucket->reserved_budget = max(0, (float) $bucket->reserved_budget - $oldAmount);
                $bucket->realized_budget += $effectiveAmount;
            } elseif (! $wasInCommitment && $willBeInRealization) {
                // Direct to final (e.g. historical / direct realization)
                if ($effectiveAmount > (float) $bucket->available_balance) {
                    throw new InvalidArgumentException('RBC-001: Realisasi melebihi saldo tersedia.');
                }
                $bucket->realized_budget += $effectiveAmount;
            }

            // Invariant: Available = Allocated - Reserved - Realized
            $bucket->available_balance = max(0, (float) $bucket->allocated_budget - (float) $bucket->reserved_budget - (float) $bucket->realized_budget);
            $bucket->save();

            // Update submission
            $sub->status = $targetStatus;
            $sub->amount = $effectiveAmount;
            if ($notes) {
                $sub->notes = $notes;
            }
            $sub->save();

            SubmissionStatusHistory::create([
                'submission_id' => $sub->id,
                'from_status' => $oldStatus,
                'to_status' => $targetStatus,
                'actor_id' => $actor->id,
                'role' => $actor->role,
                'notes' => $notes ?: "Transisi status dari {$oldStatus} ke {$targetStatus}.",
            ]);

            AuditLogService::log(
                'TRANSITION_STATUS',
                Submission::class,
                $sub->id,
                ['status' => $oldStatus, 'amount' => $oldAmount],
                ['status' => $targetStatus, 'amount' => $effectiveAmount, 'available_balance' => $bucket->available_balance]
            );

            return $sub;
        });
    }
}
