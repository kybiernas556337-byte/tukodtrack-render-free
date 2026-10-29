<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public const ROLES = ['owner' => 'Owner / Manager', 'cashier' => 'Cashier', 'stock' => 'Stock Personnel'];

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active', 'must_change_password'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = ['is_active' => 'boolean', 'email_verified_at' => 'datetime'];

    public function accessRole()
    {
        return $this->belongsTo(Role::class, 'role', 'key');
    }

    public function roleLabel(): string
    {
        return $this->accessRole?->name ?? self::ROLES[$this->role] ?? $this->role;
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function hasRole(string ...$r): bool
    {
        return in_array($this->accessRole?->access_profile ?? $this->role, $r, true);
    }
}
