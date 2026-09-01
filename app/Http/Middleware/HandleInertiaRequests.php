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
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role === 'WD' ? 'WAKIL_DEKAN' : $request->user()->role,
                    'department' => $request->user()->department ? [
                        'id' => $request->user()->department->id,
                        'code' => $request->user()->department->code,
                        'name' => $request->user()->department->name,
                    ] : null,
                    'has_faculty_scope' => $request->user()->hasFacultyScope(),
                    'can_approve_financial' => ScopeService::canApproveFinancial($request->user()),
                ] : null,
            ],
            'unreadNotificationsCount' => fn () => $request->user()
                ? Notification::where(function ($q) use ($request) {
                    $q->where('user_id', $request->user()->id)
                        ->orWhere('role', $request->user()->role);
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
