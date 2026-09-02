<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'department_id',
        'study_program_id',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    /**
     * Check if user has a specific role (checks pivot table and fallback single role).
     */
    public function hasRole(string|array $roles): bool
    {
        $roleList = is_array($roles) ? $roles : func_get_args();

        if (in_array($this->role, $roleList)) {
            return true;
        }

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(fn ($r) => in_array($r->code, $roleList));
        }

        return $this->roles()->whereIn('code', $roleList)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->hasRole($roles);
    }

    public function assignRole(string|Role $role): void
    {
        $roleModel = is_string($role) ? Role::where('code', $role)->firstOrFail() : $role;
        if (! $this->roles()->where('role_id', $roleModel->id)->exists()) {
            $this->roles()->attach($roleModel->id);
        }
    }

    // Role Constants for SIKARA
    public const ROLE_PTK = 'PTK';
    public const ROLE_KAJUR = 'KAJUR';
    public const ROLE_KAPRODI = 'KAPRODI';
    public const ROLE_PTU = 'PTU';                 // Penguji Tagihan Unit BLU
    public const ROLE_BENDAHARA = 'BENDAHARA';     // Bendahara Pengeluaran Pembantu
    public const ROLE_KABAG = 'KABAG';
    public const ROLE_WAKIL_DEKAN = 'WAKIL_DEKAN';
    public const ROLE_WD = 'WD';
    public const ROLE_DEKAN = 'DEKAN';
    public const ROLE_ADMIN = 'ADMIN';

    public function isPtk(): bool
    {
        return $this->hasRole(self::ROLE_PTK);
    }

    public function isKajur(): bool
    {
        return $this->hasRole(self::ROLE_KAJUR);
    }

    public function isKaprodi(): bool
    {
        return $this->hasRole(self::ROLE_KAPRODI);
    }

    public function isPtu(): bool
    {
        return $this->hasRole(self::ROLE_PTU);
    }

    public function isBendahara(): bool
    {
        return $this->hasRole(self::ROLE_BENDAHARA);
    }

    public function isKabag(): bool
    {
        return $this->hasRole('KABAG');
    }

    public function isWakilDekan(): bool
    {
        return $this->hasRole(['WAKIL_DEKAN', 'WD']);
    }

    public function isDekan(): bool
    {
        return $this->hasRole('DEKAN');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ADMIN');
    }

    public function hasFacultyScope(): bool
    {
        return $this->hasRole(['PTU', 'BENDAHARA', 'KABAG', 'WAKIL_DEKAN', 'WD', 'DEKAN', 'ADMIN']);
    }
}
