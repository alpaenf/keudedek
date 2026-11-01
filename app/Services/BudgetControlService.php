<?php

namespace App\Services;

use App\Models\BudgetBucket;
use App\Models\BudgetLine;
use App\Models\FiscalYear;
use App\Models\RbcEventLog;
use App\Models\Submission;
use App\Models\SubmissionStatusHistory;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BudgetControlService
{
    /**
     * Canonical Transaction Status Definitions (Tabel 20 Proposal)
     */
    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_DIAJUKAN = 'PROCESSING'; // Canonical active commitment state (DP)

    public const STATUS_RETURNED = 'RETURNED';

    public const STATUS_REJECTED = 'REJECTED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_SELESAI = 'FINAL'; // Canonical internal realization state (RI)

    /**
     * Status groupings for mathematical evaluation (Tabel 19 & 20 Proposal)
     */
    public const COMMITMENT_STATUSES = ['PROCESSING', 'SUBMITTED', 'UNDER_REVIEW', 'REVIEW', 'APPROVED', 'RESERVED'];

    public const REALIZATION_STATUSES = ['FINAL', 'COMPLETED'];

    public const RELEASED_STATUSES = ['DRAFT', 'RETURNED', 'REVISION_REQUIRED', 'REJECTED', 'CANCELLED'];

    /**
     * Allowed State Transitions (R-18)
     */
    protected const ALLOWED_TRANSITIONS = [
        'DRAFT' => ['PROCESSING', 'SUBMITTED', 'CANCELLED', 'FINAL'],
        'PROCESSING' => ['FINAL', 'COMPLETED', 'RETURNED', 'REVISION_REQUIRED', 'REJECTED', 'CANCELLED', 'PROCESSING'],
        'SUBMITTED' => ['FINAL', 'COMPLETED', 'RETURNED', 'REVISION_REQUIRED', 'REJECTED', 'CANCELLED', 'PROCESSING', 'APPROVED'],
        'UNDER_REVIEW' => ['FINAL', 'COMPLETED', 'RETURNED', 'REVISION_REQUIRED', 'REJECTED', 'CANCELLED', 'PROCESSING', 'APPROVED'],
        'APPROVED' => ['FINAL', 'COMPLETED', 'RETURNED', 'REJECTED', 'CANCELLED', 'PROCESSING'],
        'RESERVED' => ['FINAL', 'COMPLETED', 'RETURNED', 'REJECTED', 'CANCELLED', 'PROCESSING'],
        'RETURNED' => ['PROCESSING', 'SUBMITTED', 'CANCELLED', 'DRAFT'],
        'REVISION_REQUIRED' => ['PROCESSING', 'SUBMITTED', 'CANCELLED', 'DRAFT'],
        'FINAL' => ['FINAL', 'COMPLETED'], // Terminal/realized unless explicitly corrected via RBC-05
        'COMPLETED' => ['FINAL', 'COMPLETED'],
        'REJECTED' => ['REJECTED'], // Terminal
        'CANCELLED' => ['CANCELLED'], // Terminal
    ];

    /**
     * RBC-01: Budget Version Integrity Check
     */
    public static function checkVersionIntegrity(BudgetBucket $bucket): void
    {
        $version = $bucket->budgetVersion;
        if (! $version || $version->status !== 'ACTIVE') {
            throw new InvalidArgumentException("RBC-01: Transaksi tidak dapat diproses karena Versi Anggaran ({$version?->revision_no}) tidak aktif / berstatus arsip.");
        }

        $fiscalYear = $bucket->fiscalYear;
        if ($fiscalYear && ($fiscalYear->status !== 'ACTIVE' || $fiscalYear->is_locked)) {
            throw new InvalidArgumentException("RBC-01: Transaksi tidak dapat diproses karena Tahun Anggaran ({$fiscalYear->year}) berstatus terkunci.");
        }
    }

    /**
     * Scope Guard Check (Domain enforcement for users)
     */
    public static function checkScopeGuard(User $user, BudgetLine $line, ?int $departmentId = null): void
    {
        $targetDeptId = (int) ($departmentId ?? $line->department_id);

        if ($user->hasRole(['PTK', 'KAJUR', 'KAPRODI'])) {
            if ((int) $user->department_id !== (int) $line->department_id || (int) $user->department_id !== $targetDeptId) {
                throw new InvalidArgumentException('Akses Ditolak: Baris anggaran yang dipilih berada di luar unit/jurusan kerja Anda.');
            }
        }
    }

    /**
     * Duplicate Reference Warning / Validation
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
            return "Peringatan: Nomor bukti/kuitansi '{$evidenceNumber}' sudah pernah dicatat di unit ini.";
        }

        return null;
    }

    /**
     * RBC-07: Idempotency Verification and Execution Helper
     * Tabel 21, Tabel 22 (K-01, K-03), R-06, R-13, R-19.
     *
     * @param  string  $eventId  Unique event identifier
     * @param  string  $operationType  Operation type (e.g. RECORD, TRANSITION, CORRECT)
     * @param  array  $payload  Canonical payload of the request
     * @param  callable  $callback  Execution callback returning mixed
     * @return mixed
     */
    public static function handleEventIdempotency(?string $eventId, string $operationType, array $payload, callable $callback)
    {
        if (empty($eventId)) {
            return $callback();
        }

        $payloadHash = hash('sha256', json_encode($payload));
        $existingLog = RbcEventLog::where('event_id', $eventId)->first();

        if ($existingLog) {
            // If identical event and payload -> Return previous result without duplicate changes (K-01, R-06, R-13)
            if ($existingLog->payload_hash === $payloadHash) {
                if ($existingLog->submission_id) {
                    return Submission::find($existingLog->submission_id) ?? $existingLog->response_data;
                }

                return $existingLog->response_data;
            }

            // Same event ID with conflicting payload -> Reject as event identity conflict (K-03, R-19)
            throw new InvalidArgumentException('RBC-07: Konflik Identitas Kejadian: Identitas kejadian telah digunakan untuk data perubahan yang berbeda.');
        }

        $result = $callback();

        // Record successful event execution
        $submissionId = ($result instanceof Submission) ? $result->id : ($payload['submission_id'] ?? null);
        RbcEventLog::create([
            'event_id' => $eventId,
            'submission_id' => $submissionId,
            'operation_type' => $operationType,
            'payload_hash' => $payloadHash,
            'response_data' => ($result instanceof Submission) ? [
                'submission_id' => $result->id,
                'submission_number' => $result->submission_number,
                'status' => $result->status,
                'amount' => (float) $result->amount,
            ] : null,
        ]);

        return $result;
    }

    /**
     * RBC-02 / RBC-03 / RBC-08: Core Transaction Recording
     *
     * @throws InvalidArgumentException
     */
    public static function recordTransaction(array $data, User $user, ?string $eventId = null): Submission
    {
        $resolvedEventId = $eventId ?? ($data['event_id'] ?? $data['idempotency_key'] ?? null);

        $payloadForHash = [
            'budget_line_id' => $data['budget_line_id'] ?? null,
            'evidence_number' => $data['evidence_number'] ?? null,
            'amount' => (float) ($data['amount'] ?? 0),
            'submit_action' => $data['submit_action'] ?? null,
        ];

        return self::handleEventIdempotency($resolvedEventId, 'RECORD_TRANSACTION', $payloadForHash, function () use ($data, $user, $resolvedEventId) {
            return DB::transaction(function () use ($data, $user, $resolvedEventId) {
                $amount = (float) ($data['amount'] ?? 0);

                // RBC-09: Data Validation - Nominal check (R-04)
                if ($amount <= 0) {
                    throw new InvalidArgumentException('RBC-09: Nominal transaksi harus lebih besar dari nol.');
                }

                $line = BudgetLine::with(['budgetBucket', 'subcomponent', 'account'])
                    ->where('id', $data['budget_line_id'])
                    ->firstOrFail();

                // Scope Guard
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
                    throw new InvalidArgumentException('RBC-09: Data dan pemetaan tidak memadai: Control Bucket untuk baris anggaran tidak ditemukan.');
                }

                // Lock Control Bucket row for update (Race Condition Protection)
                $bucket = BudgetBucket::where('id', $bucket->id)->lockForUpdate()->firstOrFail();

                // RBC-01: Budget Version Integrity
                self::checkVersionIntegrity($bucket);

                // Duplicate reference check
                self::checkDuplicateReference($data['evidence_number'] ?? null, $departmentId);

                $submitAction = strtoupper((string) ($data['submit_action'] ?? self::STATUS_DIAJUKAN));
                $targetStatus = in_array($submitAction, ['PROCESSING', 'SUBMITTED', 'AJUKAN'])
                    ? self::STATUS_DIAJUKAN
                    : ($submitAction === self::STATUS_DRAFT ? self::STATUS_DRAFT : ($submitAction === 'FINAL' ? self::STATUS_SELESAI : self::STATUS_DIAJUKAN));

                $isDocumentedHistorical = (bool) ($data['is_documented_historical'] ?? false);
                $needsReview = false;

                // RBC-02 (DP) or RBC-03 (RI Direct) or RBC-08 (Overbudget Evaluation)
                if ($targetStatus === self::STATUS_DIAJUKAN) {
                    // RBC-08: Overbudget evaluation
                    if ($amount > (float) $bucket->available_balance) {
                        if ($isDocumentedHistorical) {
                            // Documented historical transaction that already occurred: Record with needs_review flag (K-05, R-03, R-14)
                            $needsReview = true;
                        } else {
                            // New submission: Block overbudget (K-03, R-01, R-02)
                            $defisit = $amount - (float) $bucket->available_balance;
                            throw new InvalidArgumentException(
                                'RBC-08 (RBC-001: Overbudget Protection): Nominal pengajuan (Rp '.number_format($amount, 0, ',', '.').') melebihi saldo tersedia (Rp '.number_format($bucket->available_balance, 0, ',', '.').'). Defisit: Rp '.number_format($defisit, 0, ',', '.').'. Transaksi diblokir.'
                            );
                        }
                    }

                    // RBC-02: Enter Active Commitment (C) exactly once
                    $bucket->reserved_budget += $amount;
                    $bucket->available_balance = (float) $bucket->allocated_budget - (float) $bucket->reserved_budget - (float) $bucket->realized_budget;
                    if ($needsReview) {
                        $bucket->needs_review = true;
                    }
                    $bucket->save();
                } elseif ($targetStatus === self::STATUS_SELESAI) {
                    // RBC-03: Direct realization based on valid document (R-16)
                    if ($amount > (float) $bucket->available_balance) {
                        if ($isDocumentedHistorical) {
                            $needsReview = true;
                        } else {
                            throw new InvalidArgumentException('RBC-08: Realisasi melebihi saldo tersedia.');
                        }
                    }

                    $bucket->realized_budget += $amount;
                    $bucket->available_balance = (float) $bucket->allocated_budget - (float) $bucket->reserved_budget - (float) $bucket->realized_budget;
                    if ($needsReview) {
                        $bucket->needs_review = true;
                    }
                    $bucket->save();
                }

                $fiscalYear = FiscalYear::where('status', 'ACTIVE')->first() ?? $bucket->fiscalYear;
                $submissionNumber = 'TRX/'.date('Y/m').'/'.str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT);
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
                    'original_budget_bucket_id' => $bucket->id,
                    'budget_line_id' => $line->id,
                    'amount' => $amount,
                    'is_documented_historical' => $isDocumentedHistorical,
                    'beneficiary_name' => $data['beneficiary_name'] ?? null,
                    'status' => $targetStatus,
                    'needs_review' => $needsReview,
                    'idempotency_key' => $resolvedEventId,
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
                    'notes' => $targetStatus === self::STATUS_DRAFT
                        ? 'Draft transaksi dicatat.'
                        : ($targetStatus === self::STATUS_SELESAI ? 'Realisasi internal langsung dicatat.' : 'Transaksi dicatat dan masuk ke Active Commitment (DP).'),
                ]);

                $initialAuditAction = match ($targetStatus) {
                    self::STATUS_DRAFT => 'CREATE_TRANSACTION',
                    self::STATUS_SELESAI => 'RECORD_DIRECT_REALIZATION',
                    default => 'SUBMIT_TRANSACTION',
                };

                AuditLogService::log(
                    $initialAuditAction,
                    Submission::class,
                    $submission->id,
                    null,
                    [
                        'amount' => $amount,
                        'status' => $targetStatus,
                        'evidence_number' => $submission->evidence_number,
                        'bucket_id' => $bucket->id,
                        'available_balance' => $bucket->available_balance,
                        'needs_review' => $needsReview,
                    ]
                );

                EarlyWarningService::evaluateAfterRbcChange($bucket, $submission);

                return $submission;
            });
        });
    }

    /**
     * RBC-04 / RBC-06: State Transition
     * Transitions existing submission state atomically.
     * Implements Persamaan (5) & (6):
     * - RBC-04: DP -> RI (moves contribution from C to R without double deduction).
     * - RBC-06: Cancellation/Return (releases contribution from C or R exactly once).
     * - RBC-07: Idempotent replay protection.
     * - R-18: Invalid transition protection.
     *
     * @throws InvalidArgumentException
     */
    public static function transitionStatus(
        Submission $submission,
        string $targetStatus,
        User $actor,
        ?string $notes = null,
        ?float $newAmount = null,
        ?string $eventId = null
    ): Submission {
        $payloadForHash = [
            'submission_id' => $submission->id,
            'target_status' => $targetStatus,
            'new_amount' => $newAmount !== null ? (float) $newAmount : (float) $submission->amount,
        ];

        return self::handleEventIdempotency($eventId, 'TRANSITION_STATUS', $payloadForHash, function () use ($submission, $targetStatus, $actor, $notes, $newAmount) {
            return DB::transaction(function () use ($submission, $targetStatus, $actor, $notes, $newAmount) {
                $sub = Submission::where('id', $submission->id)->lockForUpdate()->firstOrFail();
                $bucket = BudgetBucket::where('id', $sub->budget_bucket_id)->lockForUpdate()->firstOrFail();

                self::checkVersionIntegrity($bucket);

                $oldStatus = $sub->status;
                $oldAmount = (float) $sub->amount;
                $effectiveAmount = $newAmount !== null ? (float) $newAmount : $oldAmount;

                // Idempotent no-op if already in the target status with same amount (R-06)
                if ($oldStatus === $targetStatus && abs($effectiveAmount - $oldAmount) < 0.001) {
                    return $sub;
                }

                // R-18: Validate allowed transition state machine
                $allowed = self::ALLOWED_TRANSITIONS[$oldStatus] ?? [];
                if (! in_array($targetStatus, $allowed)) {
                    throw new InvalidArgumentException("RBC-06: Perubahan keadaan tidak sah: Transisi status dari '{$oldStatus}' ke '{$targetStatus}' tidak diizinkan.");
                }

                $wasInCommitment = in_array($oldStatus, self::COMMITMENT_STATUSES);
                $wasInRealization = in_array($oldStatus, self::REALIZATION_STATUSES);

                $willBeInCommitment = in_array($targetStatus, self::COMMITMENT_STATUSES);
                $willBeInRealization = in_array($targetStatus, self::REALIZATION_STATUSES);
                $willBeReleased = in_array($targetStatus, self::RELEASED_STATUSES);

                // Determine old contribution: c_i_lama, r_i_lama
                $cLama = $wasInCommitment ? $oldAmount : 0.0;
                $rLama = $wasInRealization ? $oldAmount : 0.0;

                // Determine new contribution: c_i_baru, r_i_baru
                $cBaru = $willBeInCommitment ? $effectiveAmount : 0.0;
                $rBaru = $willBeInRealization ? $effectiveAmount : 0.0;

                // Persamaan (5): Available capacity after removing old contribution
                $sMinusI = (float) $bucket->allocated_budget - ((float) $bucket->reserved_budget - $cLama) - ((float) $bucket->realized_budget - $rLama);

                // Overbudget validation when entering commitment from non-commitment
                if ($willBeInCommitment && ! $wasInCommitment) {
                    if ($effectiveAmount > $sMinusI && ! $sub->is_documented_historical) {
                        $defisit = $effectiveAmount - $sMinusI;
                        throw new InvalidArgumentException(
                            'RBC-08: Overbudget: Nominal (Rp '.number_format($effectiveAmount, 0, ',', '.').') melebihi saldo tersedia (Rp '.number_format($sMinusI, 0, ',', '.').'). Defisit: Rp '.number_format($defisit, 0, ',', '.').'.'
                        );
                    }
                }

                // Persamaan (6): Posisi setelah perubahan:
                // C' = C - c_i_lama + c_i_baru
                // R' = R - r_i_lama + r_i_baru
                // S' = P - C' - R'
                $bucket->reserved_budget = max(0, (float) $bucket->reserved_budget - $cLama + $cBaru);
                $bucket->realized_budget = max(0, (float) $bucket->realized_budget - $rLama + $rBaru);
                $bucket->available_balance = (float) $bucket->allocated_budget - (float) $bucket->reserved_budget - (float) $bucket->realized_budget;

                if ($bucket->available_balance < 0) {
                    $bucket->needs_review = true;
                }
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

                $auditAction = match ($targetStatus) {
                    'PROCESSING', 'SUBMITTED' => ($oldStatus === 'RETURNED' ? 'RESUBMIT_TRANSACTION' : 'SUBMIT_TRANSACTION'),
                    'RETURNED' => 'RETURN_TRANSACTION',
                    'REJECTED' => 'REJECT_TRANSACTION',
                    'FINAL', 'COMPLETED' => 'COMPLETE_TRANSACTION',
                    'CANCELLED' => 'CANCEL_TRANSACTION',
                    default => 'TRANSITION_STATUS',
                };

                AuditLogService::log(
                    $auditAction,
                    Submission::class,
                    $sub->id,
                    ['status' => $oldStatus, 'amount' => $oldAmount],
                    ['status' => $targetStatus, 'amount' => $effectiveAmount, 'available_balance' => $bucket->available_balance, 'notes' => $notes]
                );

                EarlyWarningService::evaluateAfterRbcChange($bucket, $sub);

                return $sub;
            });
        });
    }

    /**
     * RBC-05: Transaction Correction
     * Implements correction of nominal and/or status according to Persamaan (5) & (6):
     * S_{-i} = P - (C - c_i^{lama}) - (R - r_i^{lama})
     * C' = C - c_i^{lama} + c_i^{baru}
     * R' = R - r_i^{lama} + r_i^{baru}
     * S' = P' - C' - R'
     *
     * @throws InvalidArgumentException
     */
    public static function correctTransaction(
        Submission $submission,
        float $newAmount,
        ?string $newStatus = null,
        ?User $actor = null,
        ?string $notes = null,
        ?string $eventId = null
    ): Submission {
        $payloadForHash = [
            'submission_id' => $submission->id,
            'new_amount' => $newAmount,
            'new_status' => $newStatus ?? $submission->status,
        ];

        return self::handleEventIdempotency($eventId, 'CORRECT_TRANSACTION', $payloadForHash, function () use ($submission, $newAmount, $newStatus, $actor, $notes) {
            return DB::transaction(function () use ($submission, $newAmount, $newStatus, $actor, $notes) {
                // RBC-09: Nominal check (R-04)
                if ($newAmount <= 0) {
                    throw new InvalidArgumentException('RBC-09: Nominal transaksi hasil koreksi harus lebih besar dari nol.');
                }

                $sub = Submission::where('id', $submission->id)->lockForUpdate()->firstOrFail();
                $bucket = BudgetBucket::where('id', $sub->budget_bucket_id)->lockForUpdate()->firstOrFail();

                self::checkVersionIntegrity($bucket);

                $oldStatus = $sub->status;
                $targetStatus = $newStatus ?? $oldStatus;
                $oldAmount = (float) $sub->amount;

                $wasInCommitment = in_array($oldStatus, self::COMMITMENT_STATUSES);
                $wasInRealization = in_array($oldStatus, self::REALIZATION_STATUSES);

                $willBeInCommitment = in_array($targetStatus, self::COMMITMENT_STATUSES);
                $willBeInRealization = in_array($targetStatus, self::REALIZATION_STATUSES);

                // Old contribution
                $cLama = $wasInCommitment ? $oldAmount : 0.0;
                $rLama = $wasInRealization ? $oldAmount : 0.0;

                // New contribution
                $cBaru = $willBeInCommitment ? $newAmount : 0.0;
                $rBaru = $willBeInRealization ? $newAmount : 0.0;

                // Persamaan (5): Kapasitas setelah kontribusi lama dikeluarkan (R-08)
                $sMinusI = (float) $bucket->allocated_budget - ((float) $bucket->reserved_budget - $cLama) - ((float) $bucket->realized_budget - $rLama);

                // RBC-08 check against available capacity if commitment is increasing
                if ($willBeInCommitment && ! $sub->is_documented_historical) {
                    if ($cBaru > $sMinusI) {
                        $defisit = $cBaru - $sMinusI;
                        throw new InvalidArgumentException(
                            'RBC-08: Overbudget pada koreksi nominal: Nominal baru (Rp '.number_format($cBaru, 0, ',', '.').') melebihi kapasitas tersedia setelah kontribusi lama dikeluarkan (Rp '.number_format($sMinusI, 0, ',', '.').'). Defisit: Rp '.number_format($defisit, 0, ',', '.').'.'
                        );
                    }
                }

                // Persamaan (6): Posisi baru
                $bucket->reserved_budget = max(0, (float) $bucket->reserved_budget - $cLama + $cBaru);
                $bucket->realized_budget = max(0, (float) $bucket->realized_budget - $rLama + $rBaru);
                $bucket->available_balance = (float) $bucket->allocated_budget - (float) $bucket->reserved_budget - (float) $bucket->realized_budget;

                if ($bucket->available_balance < 0) {
                    $bucket->needs_review = true;
                }
                $bucket->save();

                // Update submission
                $sub->amount = $newAmount;
                $sub->status = $targetStatus;
                if ($notes) {
                    $sub->notes = $notes;
                }
                $sub->save();

                SubmissionStatusHistory::create([
                    'submission_id' => $sub->id,
                    'from_status' => $oldStatus,
                    'to_status' => $targetStatus,
                    'actor_id' => $actor?->id,
                    'role' => $actor?->role,
                    'notes' => $notes ?: 'Koreksi nominal transaksi dari Rp '.number_format($oldAmount, 0, ',', '.').' menjadi Rp '.number_format($newAmount, 0, ',', '.').'.',
                ]);

                AuditLogService::log(
                    'CORRECT_TRANSACTION',
                    Submission::class,
                    $sub->id,
                    ['amount' => $oldAmount, 'status' => $oldStatus],
                    ['amount' => $newAmount, 'status' => $targetStatus, 'available_balance' => $bucket->available_balance, 'notes' => $notes]
                );

                EarlyWarningService::evaluateAfterRbcChange($bucket, $sub);

                return $sub;
            });
        });
    }
}
