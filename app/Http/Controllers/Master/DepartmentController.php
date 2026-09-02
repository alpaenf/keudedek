<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\StudyProgram;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Authorization Check: Page manage hanya untuk ADMIN / authorized
        if ($user && ! $user->hasRole(['ADMIN'])) {
            abort(403, 'Akses Ditolak: Modul Master Organisasi hanya dapat dikelola oleh Administrator Sistem.');
        }

        $activeTab = $request->query('tab', 'departments'); // departments | study-programs
        $search = $request->query('search', '');

        // ==========================================
        // 1. QUERY DEPARTMENTS (UNIT & JURUSAN)
        // ==========================================
        $deptQuery = Department::with('parent')
            ->withCount(['users', 'budgetBuckets', 'submissions', 'studyPrograms']);

        if ($request->filled('search') && $activeTab === 'departments') {
            $deptQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('official_code', 'like', "%{$search}%");
            });
        }

        $departments = $deptQuery->orderBy('code')->paginate(20, ['*'], 'dept_page')->withQueryString();

        // Add can_delete flag for delete safety
        $departments->getCollection()->transform(function ($dept) {
            $isReferenced = ($dept->budget_buckets_count > 0)
                || ($dept->submissions_count > 0)
                || ($dept->users_count > 0)
                || ($dept->study_programs_count > 0);

            $dept->can_delete = ! $isReferenced;
            $dept->reference_reason = $isReferenced
                ? "Direferensikan oleh {$dept->budget_buckets_count} pos pagu, {$dept->submissions_count} transaksi, {$dept->users_count} pengguna, dan {$dept->study_programs_count} prodi."
                : null;

            return $dept;
        });

        $parentDepartments = Department::whereNull('parent_id')->get();
        $selectableDepartments = Department::where('type', 'DEPARTMENT')->orWhereNotNull('parent_id')->get();
        if ($selectableDepartments->isEmpty()) {
            $selectableDepartments = Department::all();
        }

        // ==========================================
        // 2. QUERY STUDY PROGRAMS (PROGRAM STUDI)
        // ==========================================
        $prodiQuery = StudyProgram::with(['department', 'users' => function ($q) {
            $q->where('role', 'KAPRODI');
        }])->withCount(['users', 'submissions']);

        if ($request->filled('search') && $activeTab === 'study-programs') {
            $prodiQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('official_code', 'like', "%{$search}%")
                    ->orWhereHas('department', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        $studyPrograms = $prodiQuery->orderBy('code')->paginate(20, ['*'], 'prodi_page')->withQueryString();

        // Add Kaprodi name and can_delete flag
        $studyPrograms->getCollection()->transform(function ($prodi) {
            $kaprodiUser = $prodi->users->first();
            $isReferenced = ($prodi->submissions_count > 0) || ($prodi->users_count > 0);

            $prodi->kaprodi_name = $kaprodiUser ? $kaprodiUser->name : 'Belum Ditugaskan';
            $prodi->can_delete = ! $isReferenced;
            $prodi->reference_reason = $isReferenced
                ? "Direferensikan oleh {$prodi->submissions_count} transaksi dan {$prodi->users_count} pengguna."
                : null;

            return $prodi;
        });

        return Inertia::render('Master/Departments/Index', [
            'departments' => $departments,
            'studyPrograms' => $studyPrograms,
            'parentDepartments' => $parentDepartments,
            'selectableDepartments' => $selectableDepartments,
            'activeTab' => $activeTab,
            'filters' => [
                'search' => $search,
                'tab' => $activeTab,
            ],
        ]);
    }

    // ==========================================
    // DEPARTMENTS ACTIONS
    // ==========================================

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:departments,code',
            'official_code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|in:FACULTY,DEPARTMENT',
            'parent_id' => 'nullable|exists:departments,id',
            'is_active' => 'boolean',
            'status' => 'nullable|in:ACTIVE,INACTIVE,ARCHIVED',
            'source_type' => 'nullable|in:OFFICIAL_IMPORT,OFFICIAL_DOCUMENT,INTERNAL,NEEDS_VALIDATION',
            'effective_year' => 'nullable|integer|min:2020|max:2030',
        ]);

        $validated['status'] = $validated['status'] ?? ($request->boolean('is_active') ? 'ACTIVE' : 'INACTIVE');
        $validated['source_type'] = $validated['source_type'] ?? 'INTERNAL';
        $validated['effective_year'] = $validated['effective_year'] ?? 2026;

        $department = Department::create($validated);

        AuditLogService::log(
            'CREATE_DEPARTMENT',
            Department::class,
            $department->id,
            null,
            $department->toArray()
        );

        return redirect()->route('master.departments.index', ['tab' => 'departments'])
            ->with('success', "Unit / Jurusan {$department->name} ({$department->code}) berhasil ditambahkan.");
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:departments,code,'.$department->id,
            'official_code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|in:FACULTY,DEPARTMENT',
            'parent_id' => 'nullable|exists:departments,id',
            'is_active' => 'boolean',
            'status' => 'nullable|in:ACTIVE,INACTIVE,ARCHIVED',
            'source_type' => 'nullable|in:OFFICIAL_IMPORT,OFFICIAL_DOCUMENT,INTERNAL,NEEDS_VALIDATION',
            'effective_year' => 'nullable|integer|min:2020|max:2030',
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

        return redirect()->route('master.departments.index', ['tab' => 'departments'])
            ->with('success', "Data unit {$department->name} berhasil diperbarui.");
    }

    public function destroy(Department $department): RedirectResponse
    {
        // Delete Safety Check
        if ($department->budgetBuckets()->exists() || $department->submissions()->exists() || $department->users()->exists() || $department->studyPrograms()->exists()) {
            return redirect()->route('master.departments.index', ['tab' => 'departments'])
                ->with('error', "Penghapusan ditolak: Unit {$department->name} tidak dapat dihapus karena masih direferensikan oleh data anggaran, transaksi, pengguna, atau prodi. Gunakan status NONAKTIF atau ARCHIVED.");
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

        return redirect()->route('master.departments.index', ['tab' => 'departments'])
            ->with('success', "Unit {$name} berhasil dihapus dari sistem.");
    }

    public function toggleActive(Department $department): RedirectResponse
    {
        $department->is_active = ! $department->is_active;
        $department->status = $department->is_active ? 'ACTIVE' : 'INACTIVE';
        $department->save();

        AuditLogService::log(
            'TOGGLE_ACTIVE_DEPARTMENT',
            Department::class,
            $department->id,
            null,
            ['is_active' => $department->is_active, 'status' => $department->status]
        );

        return redirect()->route('master.departments.index', ['tab' => 'departments'])
            ->with('success', "Status aktif unit {$department->name} diubah menjadi {$department->status}.");
    }

    // ==========================================
    // STUDY PROGRAMS ACTIONS
    // ==========================================

    public function storeStudyProgram(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:study_programs,code',
            'official_code' => 'nullable|string|max:50',
            'name' => 'required|string|max:150',
            'department_id' => 'required|exists:departments,id',
            'is_active' => 'boolean',
            'status' => 'nullable|in:ACTIVE,INACTIVE,ARCHIVED',
            'source_type' => 'nullable|in:OFFICIAL_IMPORT,OFFICIAL_DOCUMENT,INTERNAL,NEEDS_VALIDATION',
            'effective_year' => 'nullable|integer|min:2020|max:2030',
        ]);

        $validated['status'] = $validated['status'] ?? ($request->boolean('is_active') ? 'ACTIVE' : 'INACTIVE');
        $validated['source_type'] = $validated['source_type'] ?? 'INTERNAL';
        $validated['effective_year'] = $validated['effective_year'] ?? 2026;

        $prodi = StudyProgram::create($validated);

        AuditLogService::log(
            'CREATE_STUDY_PROGRAM',
            StudyProgram::class,
            $prodi->id,
            null,
            $prodi->toArray()
        );

        return redirect()->route('master.departments.index', ['tab' => 'study-programs'])
            ->with('success', "Program Studi {$prodi->name} ({$prodi->code}) berhasil ditambahkan.");
    }

    public function updateStudyProgram(Request $request, StudyProgram $studyProgram): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:study_programs,code,'.$studyProgram->id,
            'official_code' => 'nullable|string|max:50',
            'name' => 'required|string|max:150',
            'department_id' => 'required|exists:departments,id',
            'is_active' => 'boolean',
            'status' => 'nullable|in:ACTIVE,INACTIVE,ARCHIVED',
            'source_type' => 'nullable|in:OFFICIAL_IMPORT,OFFICIAL_DOCUMENT,INTERNAL,NEEDS_VALIDATION',
            'effective_year' => 'nullable|integer|min:2020|max:2030',
        ]);

        $oldValues = $studyProgram->toArray();
        $studyProgram->update($validated);

        AuditLogService::log(
            'UPDATE_STUDY_PROGRAM',
            StudyProgram::class,
            $studyProgram->id,
            $oldValues,
            $studyProgram->toArray()
        );

        return redirect()->route('master.departments.index', ['tab' => 'study-programs'])
            ->with('success', "Data Program Studi {$studyProgram->name} berhasil diperbarui.");
    }

    public function destroyStudyProgram(StudyProgram $studyProgram): RedirectResponse
    {
        // Delete Safety Check
        if ($studyProgram->submissions()->exists() || $studyProgram->users()->exists()) {
            return redirect()->route('master.departments.index', ['tab' => 'study-programs'])
                ->with('error', "Penghapusan ditolak: Program Studi {$studyProgram->name} tidak dapat dihapus karena masih memiliki relasi transaksi atau data pengguna. Gunakan status NONAKTIF atau ARCHIVED.");
        }

        $oldValues = $studyProgram->toArray();
        $name = $studyProgram->name;
        $studyProgram->delete();

        AuditLogService::log(
            'DELETE_STUDY_PROGRAM',
            StudyProgram::class,
            $studyProgram->id,
            $oldValues,
            null
        );

        return redirect()->route('master.departments.index', ['tab' => 'study-programs'])
            ->with('success', "Program Studi {$name} berhasil dihapus.");
    }

    public function toggleActiveStudyProgram(StudyProgram $studyProgram): RedirectResponse
    {
        $studyProgram->is_active = ! $studyProgram->is_active;
        $studyProgram->status = $studyProgram->is_active ? 'ACTIVE' : 'INACTIVE';
        $studyProgram->save();

        AuditLogService::log(
            'TOGGLE_ACTIVE_STUDY_PROGRAM',
            StudyProgram::class,
            $studyProgram->id,
            null,
            ['is_active' => $studyProgram->is_active, 'status' => $studyProgram->status]
        );

        return redirect()->route('master.departments.index', ['tab' => 'study-programs'])
            ->with('success', "Status aktif prodi {$studyProgram->name} diubah menjadi {$studyProgram->status}.");
    }
}
