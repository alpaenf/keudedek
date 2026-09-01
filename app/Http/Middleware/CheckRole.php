<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Normalize WAKIL_DEKAN / WD
        $userRole = $user->role === 'WD' ? 'WAKIL_DEKAN' : $user->role;
        $normalizedRoles = array_map(fn ($r) => $r === 'WD' ? 'WAKIL_DEKAN' : $r, $roles);

        if (! in_array($userRole, $normalizedRoles)) {
            abort(403, 'Akses Ditolak: Peran Anda ('.$user->role.') tidak memiliki izin untuk mengakses sumber daya ini.');
        }

        return $next($request);
    }
}
