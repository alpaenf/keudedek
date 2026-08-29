<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Role yang diperbolehkan untuk dibuat/dikelola oleh Admin.
     * Role ADMIN dikecualikan dari opsi pembuatan akun baru.
     */
    protected array $allowedRoles = ['PTK', 'KAJUR', 'PTU', 'KABAG', 'WD', 'DEKAN'];

    public function index(Request $request): Response
    {
        $query = User::with('department');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15)->withQueryString();
        $departments = Department::where('is_active', true)->get();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'departments' => $departments,
            'roles' => $this->allowedRoles,
            'filters' => $request->only(['search', 'role', 'department_id']),
        ]);
    }

    public function create(): Response
    {
        $departments = Department::where('is_active', true)->get();

        return Inertia::render('Users/Create', [
            'departments' => $departments,
            'roles' => $this->allowedRoles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'department_id' => 'required|exists:departments,id',
            'role' => 'required|string|in:'.implode(',', $this->allowedRoles),
        ], [
            'role.in' => 'Role Admin tidak dapat dibuat secara manual melalui form ini.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'department_id' => $request->department_id,
            'role' => $request->role,
        ]);

        AuditLogService::log(
            'CREATE_USER',
            User::class,
            $user->id,
            null,
            ['name' => $user->name, 'email' => $user->email, 'role' => $user->role]
        );

        return redirect()->route('users.index')
            ->with('success', "Akun pengguna baru untuk {$user->name} ({$user->role}) berhasil dibuat.");
    }

    public function edit(User $user): Response
    {
        $departments = Department::where('is_active', true)->get();

        return Inertia::render('Users/Edit', [
            'user' => $user->load('department'),
            'departments' => $departments,
            'roles' => $this->allowedRoles,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$user->id}",
            'password' => 'nullable|string|min:6',
            'department_id' => 'required|exists:departments,id',
            'role' => 'required|string|in:'.implode(',', $this->allowedRoles),
        ]);

        $oldData = $user->only(['name', 'email', 'role', 'department_id']);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->department_id = $request->department_id;
        $user->role = $request->role;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        AuditLogService::log(
            'UPDATE_USER',
            User::class,
            $user->id,
            $oldData,
            $user->only(['name', 'email', 'role', 'department_id'])
        );

        return redirect()->route('users.index')
            ->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $userName = $user->name;
        $user->delete();

        AuditLogService::log('DELETE_USER', User::class, $user->id, ['name' => $userName], null);

        return redirect()->route('users.index')
            ->with('success', "Akun pengguna {$userName} berhasil dihapus.");
    }
}
