<?php

namespace App\Services;

use App\Models\Department;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeService
{
    /**
     * Get accessible department IDs for a user.
     * Returns null if the user has faculty-wide access.
     * Returns array of IDs if scoped to specific department.
     */
    public static function getAccessibleDepartmentIds(?User $user): ?array
    {
        if (! $user) {
            return [];
        }

        if ($user->hasRole(['PTK', 'KAJUR', 'KAPRODI'])) {
            return $user->department_id ? [$user->department_id] : [];
        }

        // PTU, BENDAHARA, KABAG, WAKIL_DEKAN, DEKAN, ADMIN have faculty-wide visibility
        return null;
    }

    /**
     * Check if a user can access or mutate data belonging to a department.
     */
    public static function canAccessDepartment(?User $user, ?int $departmentId): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasRole(['PTK', 'KAJUR', 'KAPRODI'])) {
            return (int) $user->department_id === (int) $departmentId;
        }

        return true;
    }

    /**
     * Check if user is authorized to create / submit new transactions.
     * PTK & Admin can create.
     * KAJUR, KAPRODI, PTU, BENDAHARA, DEKAN, WAKIL_DEKAN are reviewers / monitors unless explicit permission.
     */
    public static function canCreateTransaction(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole(['PTK', 'ADMIN']);
    }

    /**
     * Check if user is authorized to perform batch import.
     */
    public static function canImportTransaction(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole(['ADMIN', 'PTK']);
    }

    /**
     * Apply department and optional study program scope filter to any Eloquent query builder.
     */
    public static function applyDepartmentScope(Builder $query, ?User $user, ?int $requestedDeptId = null, string $column = 'department_id'): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('KAPRODI') && $user->study_program_id) {
            $query->where($column, $user->department_id);

            // If the model has study_program_id, also scope to it
            if ($query->getModel()->isFillable('study_program_id') || in_array('study_program_id', $query->getModel()->getDates() ?? [])) {
                $query->where(function ($q) use ($user) {
                    $q->where('study_program_id', $user->study_program_id)
                        ->orWhereNull('study_program_id');
                });
            }

            return $query;
        }

        if ($user->hasRole(['PTK', 'KAJUR'])) {
            return $query->where($column, $user->department_id);
        }

        if ($requestedDeptId) {
            return $query->where($column, $requestedDeptId);
        }

        return $query;
    }

    /**
     * Check if a role has financial verification/approval permission.
     */
    public static function canApproveFinancial(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole(['PTU', 'BENDAHARA', 'KABAG', 'WAKIL_DEKAN', 'WD', 'DEKAN']);
    }

    /**
     * Get list of selectable departments for filter UI based on user role.
     */
    public static function getSelectableDepartments(?User $user)
    {
        if (! $user) {
            return collect();
        }

        if ($user->hasRole(['PTK', 'KAJUR', 'KAPRODI'])) {
            return Department::where('id', $user->department_id)->get();
        }

        return Department::whereNotNull('parent_id')->where('is_active', true)->get();
    }

    /**
     * Get list of selectable study programs for filter / form UI.
     */
    public static function getSelectableStudyPrograms(?User $user, ?int $departmentId = null)
    {
        if (! $user) {
            return collect();
        }

        $query = StudyProgram::where('is_active', true);

        if ($user->hasRole('KAPRODI') && $user->study_program_id) {
            return $query->where('id', $user->study_program_id)->get();
        }

        if ($user->hasRole(['PTK', 'KAJUR'])) {
            return $query->where('department_id', $user->department_id)->get();
        }

        if ($departmentId) {
            return $query->where('department_id', $departmentId)->get();
        }

        return $query->get();
    }
}
