<?php

namespace App\Services;

use App\Models\BudgetBucket;
use App\Models\BudgetLine;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BudgetCalculationService
{
    /**
     * Working baseline grain: 'SUBCOMPONENT_ACCOUNT'
     * Future flexible grain: 'ACCOUNT_ONLY'
     */
    public const GRAIN_SUBCOMPONENT_ACCOUNT = 'SUBCOMPONENT_ACCOUNT';

    public const GRAIN_ACCOUNT_ONLY = 'ACCOUNT_ONLY';

    /**
     * Resolve or find the corresponding Control Bucket for a given Budget Line.
     * Maps many Budget Lines -> one Control Bucket based on active resolution grain.
     */
    public static function resolveControlBucket(BudgetLine $line, string $grain = self::GRAIN_SUBCOMPONENT_ACCOUNT): ?BudgetBucket
    {
        // 1. If explicit budget_bucket_id is already assigned, use it
        if ($line->budget_bucket_id) {
            $bucket = BudgetBucket::find($line->budget_bucket_id);
            if ($bucket) {
                return $bucket;
            }
        }

        // 2. Base query: must match version and department
        $query = BudgetBucket::where('budget_version_id', $line->budget_version_id)
            ->where('department_id', $line->department_id);

        $accountCode = $line->account?->code;
        $subcompFullCode = $line->subcomponent?->full_code;

        if ($grain === self::GRAIN_SUBCOMPONENT_ACCOUNT) {
            // Working baseline grain: version + department + subcomponent + account
            if ($subcompFullCode && $accountCode) {
                $bucket = (clone $query)->where('subcomponent_full_code', $subcompFullCode)
                    ->where('account_code', $accountCode)
                    ->first();
                if ($bucket) {
                    return $bucket;
                }
            }
        }

        // Fallback / ACCOUNT_ONLY grain: version + department + account
        if ($accountCode) {
            $bucket = (clone $query)->where('account_code', $accountCode)->first();
            if ($bucket) {
                return $bucket;
            }
        }

        return null;
    }

    /**
     * Link many Budget Lines to their respective Control Buckets in bulk.
     */
    public static function mapLinesToBuckets(int $budgetVersionId, string $grain = self::GRAIN_SUBCOMPONENT_ACCOUNT): int
    {
        $lines = BudgetLine::with(['account', 'subcomponent'])
            ->where('budget_version_id', $budgetVersionId)
            ->get();

        $linkedCount = 0;
        foreach ($lines as $line) {
            $bucket = self::resolveControlBucket($line, $grain);
            if ($bucket && $line->budget_bucket_id !== $bucket->id) {
                $line->budget_bucket_id = $bucket->id;
                $line->save();
                $linkedCount++;
            }
        }

        return $linkedCount;
    }

    /**
     * Calculate financial snapshot for a Budget Line.
     * Returns:
     * - line_budget: pagu nominal baris RBA
     * - line_diajukan: total transaksi diajukan yang mengarah spesifik ke baris ini
     * - line_realisasi: total transaksi selesai yang mengarah spesifik ke baris ini
     * - line_saldo: sisa saldo alokasi baris RBA
     * - bucket_allocated: pagu kendali pos Control Bucket
     * - bucket_diajukan: total transaksi diajukan pada Control Bucket
     * - bucket_realisasi: total transaksi selesai pada Control Bucket
     * - bucket_available: saldo kendali sistem (Sisa Pagu yang bisa dibelanjakan)
     */
    public static function getLineFinancialSnapshot(BudgetLine $line): array
    {
        $linePagu = (float) $line->budget_amount;

        // Transactions referencing this line
        $lineDiajukan = (float) Submission::where('budget_line_id', $line->id)
            ->whereIn('status', ['SUBMITTED', 'PROCESSING', 'UNDER_REVIEW', 'REVIEW', 'APPROVED', 'RESERVED'])
            ->sum('amount');

        $lineRealisasi = (float) Submission::where('budget_line_id', $line->id)
            ->whereIn('status', ['FINAL', 'COMPLETED'])
            ->sum('amount');

        $lineSaldo = $linePagu - $lineDiajukan - $lineRealisasi;

        // Control Bucket metrics
        $bucket = $line->budgetBucket ?: self::resolveControlBucket($line);

        $bucketAllocated = $bucket ? (float) $bucket->allocated_budget : $linePagu;
        $bucketReserved = $bucket ? (float) $bucket->reserved_budget : $lineDiajukan;
        $bucketRealized = $bucket ? (float) $bucket->realized_budget : $lineRealisasi;
        $bucketAvailable = $bucket ? (float) $bucket->available_balance : $lineSaldo;

        return [
            'line_budget' => $linePagu,
            'line_diajukan' => $lineDiajukan,
            'line_realisasi' => $lineRealisasi,
            'line_saldo' => $lineSaldo,
            'bucket_id' => $bucket?->id,
            'bucket_allocated' => $bucketAllocated,
            'bucket_reserved' => $bucketReserved,
            'bucket_realized' => $bucketRealized,
            'bucket_available' => $bucketAvailable,
            'is_controlled' => $bucket !== null,
        ];
    }

    /**
     * Search and format Budget Lines with scope enforcement and complete hierarchy snapshots.
     */
    public static function searchBudgetLines(
        User $user,
        ?string $search = null,
        ?int $departmentId = null,
        ?int $budgetVersionId = null,
        int $limit = 25
    ): array {
        $query = BudgetLine::with([
            'budgetVersion',
            'department',
            'fundingSource',
            'budgetBucket',
            'program',
            'activity',
            'kro',
            'ro',
            'component',
            'subcomponent',
            'account',
            'subaccount',
        ])->where('status', 'ACTIVE');

        // 1. Enforce user department scope
        ScopeService::applyDepartmentScope($query, $user, $departmentId);

        // 2. Budget Version filter (defaults to active version if unspecified)
        if ($budgetVersionId) {
            $query->where('budget_version_id', $budgetVersionId);
        } else {
            $query->whereHas('budgetVersion', function (Builder $vq) {
                $vq->where('status', 'ACTIVE');
            });
        }

        // 3. Multi-field Search: No RBA, uraian, akun, kegiatan, subkomponen
        if (! empty(trim((string) $search))) {
            $terms = explode(' ', trim((string) $search));
            $query->where(function (Builder $sq) use ($terms) {
                foreach ($terms as $term) {
                    $t = "%{$term}%";
                    $sq->where(function (Builder $sub) use ($t) {
                        $sub->where('rba_sequence_no', 'like', $t)
                            ->orWhere('description', 'like', $t)
                            ->orWhereHas('account', fn ($aq) => $aq->where('code', 'like', $t)->orWhere('name', 'like', $t))
                            ->orWhereHas('subcomponent', fn ($scq) => $scq->where('code', 'like', $t)->orWhere('full_code', 'like', $t)->orWhere('name', 'like', $t))
                            ->orWhereHas('activity', fn ($actq) => $actq->where('code', 'like', $t)->orWhere('name', 'like', $t))
                            ->orWhereHas('kro', fn ($kq) => $kq->where('code', 'like', $t)->orWhere('name', 'like', $t))
                            ->orWhereHas('ro', fn ($rq) => $rq->where('code', 'like', $t)->orWhere('name', 'like', $t))
                            ->orWhereHas('component', fn ($cq) => $cq->where('code', 'like', $t)->orWhere('name', 'like', $t));
                    });
                }
            });
        }

        $lines = $query->orderBy('rba_sequence_no')->limit($limit)->get();

        return $lines->map(function (BudgetLine $line) {
            $snapshot = self::getLineFinancialSnapshot($line);

            return [
                'id' => $line->id,
                'rba_sequence_no' => $line->rba_sequence_no,
                'description' => $line->description,
                'volume' => (float) $line->volume,
                'unit' => $line->unit,
                'unit_price' => (float) $line->unit_price,
                'budget_amount' => (float) $line->budget_amount,
                'department' => [
                    'id' => $line->department_id,
                    'code' => $line->department?->code,
                    'name' => $line->department?->name,
                ],
                'funding_source' => [
                    'id' => $line->funding_source_id,
                    'code' => $line->fundingSource?->code,
                    'name' => $line->fundingSource?->name,
                ],
                'hierarchy' => [
                    'program' => $line->program ? ['code' => $line->program->code, 'name' => $line->program->name] : null,
                    'activity' => $line->activity ? ['code' => $line->activity->code, 'name' => $line->activity->name] : null,
                    'kro' => $line->kro ? ['code' => $line->kro->code, 'name' => $line->kro->name] : null,
                    'ro' => $line->ro ? ['code' => $line->ro->code, 'name' => $line->ro->name] : null,
                    'component' => $line->component ? ['code' => $line->component->code, 'name' => $line->component->name] : null,
                    'subcomponent' => $line->subcomponent ? [
                        'code' => $line->subcomponent->code,
                        'full_code' => $line->subcomponent->full_code,
                        'name' => $line->subcomponent->name,
                    ] : null,
                    'account' => $line->account ? ['code' => $line->account->code, 'name' => $line->account->name] : null,
                    'subaccount' => $line->subaccount ? ['code' => $line->subaccount->code, 'name' => $line->subaccount->name] : null,
                ],
                'control_bucket' => $snapshot['bucket_id'] ? [
                    'id' => $snapshot['bucket_id'],
                    'allocated' => $snapshot['bucket_allocated'],
                    'reserved' => $snapshot['bucket_reserved'],
                    'realized' => $snapshot['bucket_realized'],
                    'available' => $snapshot['bucket_available'],
                ] : null,
                'financial_snapshot' => [
                    'pagu_line' => $snapshot['line_budget'],
                    'diajukan_line' => $snapshot['line_diajukan'],
                    'realisasi_line' => $snapshot['line_realisasi'],
                    'saldo_line' => $snapshot['line_saldo'],
                    'pagu_bucket' => $snapshot['bucket_allocated'],
                    'diajukan_bucket' => $snapshot['bucket_reserved'],
                    'realisasi_bucket' => $snapshot['bucket_realized'],
                    'saldo_tersedia' => $snapshot['bucket_available'],
                ],
                'badge' => sprintf(
                    'No. %s • %s • %s',
                    $line->rba_sequence_no,
                    $line->account?->code ?? '52XXXX',
                    $line->department?->code ?? 'FT'
                ),
            ];
        })->toArray();
    }

    /**
     * Resolve all transactions that belong to a Pos Anggaran (Budget Bucket).
     * Matches transactions by department, fiscal year, and bucket identity across revisions.
     */
    public static function getSubmissionsForBucket(BudgetBucket $bucket): Builder
    {
        return Submission::where('fiscal_year_id', $bucket->fiscal_year_id)
            ->where('department_id', $bucket->department_id)
            ->where(function (Builder $query) use ($bucket) {
                $query->where('budget_bucket_id', $bucket->id)
                    ->orWhere('original_budget_bucket_id', $bucket->id)
                    ->orWhereHas('budgetBucket', function (Builder $bq) use ($bucket) {
                        $bq->where('department_id', $bucket->department_id)
                            ->where('account_code', $bucket->account_code)
                            ->where(function ($sq) use ($bucket) {
                                if ($bucket->subcomponent_full_code) {
                                    $sq->where('subcomponent_full_code', $bucket->subcomponent_full_code);
                                }
                            });
                    });
            });
    }

    /**
     * Synchronize a Pos Anggaran (BudgetBucket) from its underlying transactions.
     * Implements Persamaan (3) & (4):
     * C = sum(DP), R = sum(RI), S = P - C - R.
     */
    public static function synchronizeBucketFromTransactions(BudgetBucket $bucket): BudgetBucket
    {
        $submissions = self::getSubmissionsForBucket($bucket)->get();

        $reserved = 0.0;
        $realized = 0.0;
        $hasUnmappedStatus = false;

        foreach ($submissions as $sub) {
            $status = $sub->status;
            $amount = (float) $sub->amount;

            if (in_array($status, BudgetControlService::COMMITMENT_STATUSES)) {
                $reserved += $amount;
            } elseif (in_array($status, BudgetControlService::REALIZATION_STATUSES)) {
                $realized += $amount;
            } elseif (! in_array($status, BudgetControlService::RELEASED_STATUSES)) {
                $hasUnmappedStatus = true;
            }
        }

        $allocated = (float) $bucket->allocated_budget;
        $available = $allocated - $reserved - $realized;

        $bucket->reserved_budget = $reserved;
        $bucket->realized_budget = $realized;
        $bucket->available_balance = $available;
        $bucket->needs_review = ($available < 0);
        $bucket->data_incomplete = $hasUnmappedStatus;
        $bucket->save();

        return $bucket;
    }

    /**
     * Get authoritative financial snapshot for a Pos Anggaran (Control Bucket).
     */
    public static function getBucketFinancialSnapshot(BudgetBucket $bucket): array
    {
        $p = (float) $bucket->allocated_budget;
        $c = (float) $bucket->reserved_budget;
        $r = (float) $bucket->realized_budget;
        $s = $p - $c - $r;

        return [
            'bucket_id' => $bucket->id,
            'pagu' => $p,
            'commitment' => $c,
            'realization' => $r,
            'available' => $s,
            'needs_review' => (bool) $bucket->needs_review || ($s < 0),
            'data_incomplete' => (bool) $bucket->data_incomplete,
        ];
    }
}
