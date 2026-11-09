<?php

namespace App\Models;

use App\Enums\MerchantRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'password', 'is_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Merchant, $this>
     */
    public function merchants(): BelongsToMany
    {
        return $this->belongsToMany(Merchant::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->is_admin;
        }

        return true;
    }

    /**
     * @return Collection<int, Merchant>
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->merchants;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->merchants()->whereKey($tenant->getKey())->exists();
    }

    public function isOwnerOf(Merchant $merchant): bool
    {
        return $merchant->roleOf($this) === MerchantRole::Owner;
    }
}
