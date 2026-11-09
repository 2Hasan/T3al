<?php

namespace App\Models;

use App\Enums\MerchantRole;
use Database\Factories\MerchantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'logo_path', 'timezone', 'trial_ends_at'])]
class Merchant extends Model
{
    /** @use HasFactory<MerchantFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<LoyaltyProgram, $this>
     */
    public function loyaltyPrograms(): HasMany
    {
        return $this->hasMany(LoyaltyProgram::class);
    }

    /**
     * @return HasMany<Customer, $this>
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * @return HasMany<LoyaltyCard, $this>
     */
    public function loyaltyCards(): HasMany
    {
        return $this->hasMany(LoyaltyCard::class);
    }

    /**
     * @return HasMany<CardActivity, $this>
     */
    public function cardActivities(): HasMany
    {
        return $this->hasMany(CardActivity::class);
    }

    public function activeProgram(): ?LoyaltyProgram
    {
        return $this->loyaltyPrograms()->where('is_active', true)->latest('id')->first();
    }

    public function roleOf(User $user): ?MerchantRole
    {
        $role = $this->users()->whereKey($user->getKey())->first()?->pivot->role;

        return $role === null ? null : MerchantRole::from($role);
    }
}
