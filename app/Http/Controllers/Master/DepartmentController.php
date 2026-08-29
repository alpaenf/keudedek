<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Department::with('parent')->withCount(['users', 'budgetBuckets', 'submissions']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $departments = $query->orderBy('code')->paginate(15)->withQueryString();
        $parentDepartments = Department::whereNull('parent_id')->get();

        return Inertia::render('Master/Departments/Index', [
            'departments' => $departments,
            'parentDepartments' => $parentDepartments,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:departments,code',
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:departments,id',
            'is_active' => 'boolean',
        ]);

        $department = Department::create($validated);

        AuditLogService::log(
            'CREATE_DEPARTMENT',
            Department::class,
            $department->id,
            null,
            $department->toArray()
        );

        return redirect()->route('master.departments.index')
            ->with('success', "Unit / Jurusan {$department->name} ({$department->code}) berhasil ditambahkan.");
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:departments,code,'.$department->id,
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:departments,id',
            'is_active' => 'boolean',
        ]);

        $oldValues = $department->toArray();
        $department->update($validated);

        AuditLogService::log(
            'UPDATE_DEPARTMENT',
            Department::class,
            $department->id,
            $oldValues,
            $department->toArray()
        );

        return redirect()->route('master.departments.index')
            ->with('success', "Data unit {$department->name} berhasil diperbarui.");
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->budgetBuckets()->exists() || $department->submissions()->exists() || $department->users()->exists()) {
            return redirect()->route('master.departments.index')
                ->with('error', "Unit {$department->name} tidak dapat dihapus karena masih memiliki relasi data pengguna, pagu, atau pengajuan.");
        }

        $oldValues = $department->toArray();
        $name = $department->name;
        $department->delete();

        AuditLogService::log(
            'DELETE_DEPARTMENT',
            Department::class,
            $department->id,
            $oldValues,
            null
        );

        return redirect()->route('master.departments.index')
            ->with('success', "Unit {$name} berhasil dihapus.");
    }

    public function toggleActive(Department $department): RedirectResponse
    {
        $department->is_active = ! $department->is_active;
        $department->save();

        AuditLogService::log(
            'TOGGLE_ACTIVE_DEPARTMENT',
            Department::class,
            $department->id,
            null,
            ['is_active' => $department->is_active]
        );

        return redirect()->route('master.departments.index')
            ->with('success', "Status aktif unit {$department->name} berhasil diubah.");
    }
}
