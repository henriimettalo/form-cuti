<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'department_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN_UNIT = 'admin_unit';

    public const ROLE_PENGGUNA = 'pengguna';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'department_id' => 'integer',
            'password' => 'hashed',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function canViewSensitiveSimpegData(): bool
    {
        return $this->canManageSystem();
    }

    public function normalizedRole(): string
    {
        return match (strtolower(trim((string) $this->role))) {
            self::ROLE_SUPER_ADMIN,
            'admin',
            'administrator',
            'pejabat',
            'simpeg_admin' => self::ROLE_SUPER_ADMIN,
            // Nilai ini dinormalisasi menjadi admin_unit oleh migrasi.
            'operator' => self::ROLE_ADMIN_UNIT,
            self::ROLE_ADMIN_UNIT => self::ROLE_ADMIN_UNIT,
            default => self::ROLE_PENGGUNA,
        };
    }

    public function hasAnyRole(string ...$roles): bool
    {
        return in_array($this->normalizedRole(), $roles, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->normalizedRole() === self::ROLE_SUPER_ADMIN;
    }

    public function isAdminUnit(): bool
    {
        return $this->normalizedRole() === self::ROLE_ADMIN_UNIT;
    }

    public function isPengguna(): bool
    {
        return $this->normalizedRole() === self::ROLE_PENGGUNA;
    }

    public function canManageAccounts(): bool
    {
        return $this->hasAnyRole(self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN_UNIT);
    }

    public function canManageSystem(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canCreateLeaveRequests(): bool
    {
        return $this->hasAnyRole(
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN_UNIT,
            self::ROLE_PENGGUNA,
        );
    }

    public function canAccessDepartment(int|string|null $departmentId): bool
    {
        return $this->isSuperAdmin()
            || ($departmentId !== null && (int) $this->department_id === (int) $departmentId);
    }

    public function roleLabel(): string
    {
        return match ($this->normalizedRole()) {
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_ADMIN_UNIT => 'Admin Unit',
            default => 'Pengguna',
        };
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'created_by');
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class, 'generated_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
