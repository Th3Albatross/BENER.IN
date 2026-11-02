<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'nis', 'classroom', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** Roles that triage reports. Admin is deliberately excluded. */
    public const STAFF_ROLES = ['petugas', 'attendant', 'staff'];

    public const ADMIN_ROLE = 'admin';

    /** Roles an admin may assign when managing accounts. */
    public const ASSIGNABLE_ROLES = ['siswa', 'petugas'];

    /** @return list<string> */
    public static function assignableRoles(): array
    {
        return self::ASSIGNABLE_ROLES;
    }

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isStaff(): bool
    {
        return in_array(mb_strtolower(trim((string) $this->role)), self::STAFF_ROLES, true);
    }

    public function isAdmin(): bool
    {
        return mb_strtolower(trim((string) $this->role)) === self::ADMIN_ROLE;
    }

    public function isStudent(): bool
    {
        return ! $this->isStaff() && ! $this->isAdmin();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'users_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class, 'users_id');
    }
}
