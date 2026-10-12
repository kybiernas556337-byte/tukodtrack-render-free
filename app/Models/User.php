<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public const ROLES = ['owner' => 'Owner / Manager', 'cashier' => 'Cashier', 'stock' => 'Stock Personnel'];

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['is_active' => 'boolean', 'email_verified_at' => 'datetime'];

    public function roleLabel(): string { return self::ROLES[$this->role] ?? $this->role; }
    public function isOwner(): bool { return $this->role === 'owner'; }
    public function hasRole(string ...$r): bool { return in_array($this->role, $r, true); }
}
