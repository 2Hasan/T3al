<?php

namespace App\Models;

use App\Enums\MerchantRole;
use App\Enums\MerchantStatus;
use Carbon\Carbon;
use Database\Factories\MerchantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'logo_path', 'timezone', 'trial_ends_at', 'plan_id', 'status', 'paid_until'])]
class Merchant extends Model
{
    /** @use HasFactory<MerchantFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'trial',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'paid_until' => 'datetime',
            'status' => MerchantStatus::class,
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
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return HasMany<Branch, $this>
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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

    /**
     * Whether the shop can use its tools. Suspended shops keep their data but cannot stamp.
     */
    public function isOperational(): bool
    {
        return $this->status->isOperational();
    }

    /**
     * The monthly bill: every branch at the plan's price per branch.
     */
    public function monthlyAmount(?int $branches = null): string
    {
        // Work in fils (thousandths of a dinar) so money never goes through floating point.
        $filsPerBranch = (int) round(((float) ($this->plan?->price_per_branch ?? 0)) * 1000);
        $count = $branches ?? $this->branches()->count();
        $fils = $filsPerBranch * max(1, $count);

        return number_format($fils / 1000, 3, '.', '');
    }

    /**
     * The date the current period ends: the trial end while on trial, otherwise the paid end.
     */
    public function periodEndsAt(): ?Carbon
    {
        $end = $this->status === MerchantStatus::Trial ? $this->trial_ends_at : $this->paid_until;

        return $end ?? $this->paid_until ?? $this->trial_ends_at;
    }

    public function daysLeft(): ?int
    {
        $end = $this->periodEndsAt();

        return $end === null ? null : (int) now()->startOfDay()->diffInDays($end->copy()->startOfDay(), false);
    }

    /**
     * @param  Builder<Merchant>  $query
     * @return Builder<Merchant>
     */
    public function scopeEndingWithin(Builder $query, int $days): Builder
    {
        return $query
            ->where('status', '!=', MerchantStatus::Suspended->value)
            ->where(function (Builder $query) use ($days) {
                $query->whereBetween('trial_ends_at', [now(), now()->addDays($days)])
                    ->orWhereBetween('paid_until', [now(), now()->addDays($days)]);
            });
    }

    public function hasFeature(string $feature): bool
    {
        return $this->plan?->includes($feature) ?? false;
    }
}
