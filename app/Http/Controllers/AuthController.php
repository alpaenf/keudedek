<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showLogin(): Response
    {
        $isDemoOrLocal = app()->environment(['local', 'demo', 'testing']) || config('app.env') !== 'production';

        $users = $isDemoOrLocal
            ? User::with(['department', 'roles', 'studyProgram'])->get()
            : [];

        return Inertia::render('Auth/Login', [
            'users' => $users,
            'isDemoOrLocal' => $isDemoOrLocal,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('login')).'|'.$request->ip());

        // Rate limiting check (5 attempts per minute)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return redirect()->back()->withErrors([
                'login' => "Terlalu banyak percobaan masuk. Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }

        $loginInput = $request->input('login');
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // Determine if login is email or username
        $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
        $credentials = [
            $fieldType => $loginInput,
            'password' => $password,
        ];

        // Attempt server-side authentication
        if (Auth::attempt($credentials, $remember)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $user = Auth::user();
            $roleLabel = $user->roles->pluck('name')->implode(' / ');
            if (! $roleLabel) {
                $roleLabel = $user->role;
            }

            return redirect()->intended(route('dashboard'))
                ->with('success', "Selamat datang kembali, {$user->name} ({$roleLabel}).");
        }

        // Hit rate limiter on failed attempt
        RateLimiter::hit($throttleKey, 60);

        // Generic error message to prevent user enumeration
        return redirect()->back()->withErrors([
            'login' => 'Email/Username atau kata sandi yang Anda masukkan tidak valid.',
        ]);
    }

    public function loginAs(User $user): RedirectResponse
    {
        if (! app()->environment(['local', 'demo', 'testing']) && config('app.env') === 'production') {
            abort(403, 'Aksi demo role switcher dinonaktifkan pada environment production.');
        }

        Auth::login($user);
        request()->session()->regenerate();

        $roleLabel = $user->roles->pluck('name')->implode(' / ');
        if (! $roleLabel) {
            $roleLabel = $user->role;
        }

        return redirect()->route('dashboard')
            ->with('success', "Berhasil masuk sebagai {$user->name} ({$roleLabel} - {$user->department?->name}).");
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}
