<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function isPtk(): bool
    {
        return $this->role === 'PTK';
    }

    public function isKetuaPtk(): bool
    {
        return $this->role === 'KETUA_PTK';
    }

    public function isKajur(): bool
    {
        return $this->role === 'KAJUR';
    }

    public function isPtu(): bool
    {
        return $this->role === 'PTU';
    }

    public function isKabag(): bool
    {
        return $this->role === 'KABAG';
    }

    public function isWakilDekan(): bool
    {
        return in_array($this->role, ['WAKIL_DEKAN', 'WD']);
    }

    public function isDekan(): bool
    {
        return $this->role === 'DEKAN';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function hasFacultyScope(): bool
    {
        return in_array($this->role, ['KETUA_PTK', 'PTU', 'KABAG', 'WAKIL_DEKAN', 'WD', 'DEKAN', 'ADMIN']);
    }
}
