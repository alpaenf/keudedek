<?php

namespace App\Http\Middleware;

use App\Services\ScopeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDepartmentScope
{
    /**
     * Handle an incoming request and enforce department isolation.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // If request provides department_id in input or query
        $departmentId = $request->input('department_id') ?? $request->query('department_id');

        if ($departmentId && ! ScopeService::canAccessDepartment($user, (int) $departmentId)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki otoritas untuk mengakses data di luar unit/jurusan Anda.');
        }

        return $next($request);
    }
}
