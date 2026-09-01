<?php

namespace App\Http\Middleware;

use App\Models\FiscalYear;
use App\Models\Notification;
use App\Services\ScopeService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role === 'WD' ? 'WAKIL_DEKAN' : $user->role,
                    'roles' => $user->roles()->pluck('code')->toArray(),
                    'department' => $user->department ? [
                        'id' => $user->department->id,
                        'code' => $user->department->code,
                        'name' => $user->department->name,
                    ] : null,
                    'study_program' => $user->studyProgram ? [
                        'id' => $user->studyProgram->id,
                        'code' => $user->studyProgram->code,
                        'name' => $user->studyProgram->name,
                    ] : null,
                    'has_faculty_scope' => $user->hasFacultyScope(),
                    'can_approve_financial' => ScopeService::canApproveFinancial($user),
                    'can_create_transaction' => ScopeService::canCreateTransaction($user),
                    'can_import_transaction' => ScopeService::canImportTransaction($user),
                ] : null,
            ],
            'unreadNotificationsCount' => fn () => $user
                ? Notification::where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhere('role', $user->role);
                })->where('is_read', false)->count()
                : 0,
            'activeFiscalYear' => fn () => FiscalYear::where('status', 'ACTIVE')->first()?->year ?? 2026,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
