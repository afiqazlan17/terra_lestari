<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

#[Fillable(['name', 'email', 'password', 'pin', 'role', 'project_id', 'is_active', 'locale', 'must_change_password'])]
#[Hidden(['password', 'pin', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable;

    public const ROLE_SUPERUSER = 'superuser';
    public const ROLE_OWNER = 'owner';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_CASHIER = 'cashier';

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
            'pin' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isSuperuser(): bool
    {
        return $this->role === self::ROLE_SUPERUSER;
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isCashier(): bool
    {
        return $this->role === self::ROLE_CASHIER;
    }

    /**
     * Superuser (system admin, e.g. the developer/technical team) has every
     * permission owner has, plus can manage staff. Kept as a distinct role
     * from owner so Ben's business-owner account stays conceptually
     * separate from technical admin accounts.
     */
    public function hasFullAccess(): bool
    {
        return $this->isOwner() || $this->isSuperuser();
    }

    /**
     * Can see the dashboard, purchases and menu management, but (unlike
     * owner/superuser) cannot manage staff accounts.
     */
    public function canManageOperations(): bool
    {
        return $this->hasFullAccess() || $this->isManager();
    }

    /**
     * The project this user is currently operating in. Cashiers are pinned
     * to their assigned project; owners fall back to their first active
     * project until multi-project switching is built.
     */
    public function currentProject(): ?Project
    {
        if ($this->project_id) {
            return $this->project;
        }

        return Project::where('is_active', true)->orderBy('id')->first();
    }
}
