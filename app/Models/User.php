<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'roles',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
            'roles' => 'json',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * Get the user's roles as UserRole enums.
     *
     * @return array<UserRole>
     */
    public function getRoles(): array
    {
        return array_map(
            fn (string $role) => UserRole::from($role),
            $this->roles ?? []
        );
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(UserRole $role): bool
    {
        return in_array($role->value, $this->roles ?? [], true);
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin);
    }

    /**
     * Check if the user has discount store role.
     */
    public function isDiscountStoreManager(): bool
    {
        return $this->hasRole(UserRole::DiscountStore);
    }

    /**
     * Assign roles to the user.
     *
     * @param  array<UserRole|string>  $roles
     */
    public function assignRoles(array $roles): void
    {
        $this->roles = array_map(
            fn (UserRole|string $role) => $role instanceof UserRole ? $role->value : $role,
            $roles
        );
        $this->save();
    }

    /**
     * Add a role to the user.
     */
    public function addRole(UserRole|string $role): void
    {
        $roleValue = $role instanceof UserRole ? $role->value : $role;
        if (! in_array($roleValue, $this->roles ?? [], true)) {
            $this->roles = [...($this->roles ?? []), $roleValue];
            $this->save();
        }
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(UserRole|string $role): void
    {
        $roleValue = $role instanceof UserRole ? $role->value : $role;
        $this->roles = array_filter(
            $this->roles ?? [],
            fn (string $r) => $r !== $roleValue
        );
        $this->save();
    }
}
