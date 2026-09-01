<?php

namespace App\Services;

use App\Models\Department;
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

        if (in_array($user->role, ['PTK', 'KAJUR'])) {
            return $user->department_id ? [$user->department_id] : [];
        }

        // PTU, KABAG, WAKIL_DEKAN, DEKAN, ADMIN have faculty-wide visibility
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

        if (in_array($user->role, ['PTK', 'KAJUR'])) {
            return (int) $user->department_id === (int) $departmentId;
        }

        return true;
    }

    /**
     * Apply department scope filter to any Eloquent query builder.
     */
    public static function applyDepartmentScope(Builder $query, ?User $user, ?int $requestedDeptId = null, string $column = 'department_id'): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (in_array($user->role, ['PTK', 'KAJUR'])) {
            return $query->where($column, $user->department_id);
        }

        if ($requestedDeptId) {
            return $query->where($column, $requestedDeptId);
        }

        return $query;
    }

    /**
     * Check if a role has financial approval permission.
     * ADMIN is explicitly FALSE - Admin only manages config & master data.
     */
    public static function canApproveFinancial(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return in_array($user->role, ['KAJUR', 'PTU', 'KABAG', 'WAKIL_DEKAN', 'WD', 'DEKAN']);
    }

    /**
     * Get list of selectable departments for filter UI based on user role.
     */
    public static function getSelectableDepartments(?User $user)
    {
        if (! $user) {
            return collect();
        }

        if (in_array($user->role, ['PTK', 'KAJUR'])) {
            return Department::where('id', $user->department_id)->get();
        }

        return Department::whereNotNull('parent_id')->where('is_active', true)->get();
    }
}
