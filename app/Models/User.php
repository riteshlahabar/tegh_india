<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'initials', 'branch', 'region', 'city'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Counterpart to isAdmin(). It was called in four unrouted places and
     * defined nowhere, so wiring any of them up would have fataled.
     */
    public function isTelecaller(): bool
    {
        return $this->role === 'telecaller';
    }

    /**
     * Whether this account is pinned to one company's books. Admins never are,
     * and a telecaller with neither field set still sees everything -- which is
     * what every account created before this migration looks like.
     */
    public function isScoped(): bool
    {
        return ! $this->isAdmin() && (filled($this->region) || filled($this->city));
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'assigned_to');
    }

    public function parties(): HasMany
    {
        return $this->hasMany(Party::class, 'assigned_to');
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class, 'telecaller_id');
    }
}
