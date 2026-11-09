<?php

namespace App\Models;

use Database\Factories\LoyaltyCardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\LaravelMobilePass\Models\Concerns\HasMobilePasses;
use Spatie\LaravelMobilePass\Models\MobilePass;

#[Fillable([
    'merchant_id', 'loyalty_program_id', 'customer_id', 'code', 'stamps',
    'rewards_available', 'lifetime_stamps', 'rewards_redeemed', 'last_visit_at',
])]
class LoyaltyCard extends Model
{
    /** @use HasFactory<LoyaltyCardFactory> */
    use HasFactory, HasMobilePasses;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stamps' => 'integer',
            'rewards_available' => 'integer',
            'lifetime_stamps' => 'integer',
            'rewards_redeemed' => 'integer',
            'last_visit_at' => 'datetime',
        ];
    }

    /**
     * Unguessable code encoded in the card's QR: it identifies the card at the till
     * and also serves as the customer's private link to their card page.
     */
    public static function generateCode(): string
    {
        do {
            $code = Str::upper(Str::random(12));
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * Links a freshly created wallet pass to this card. Saved quietly because the
     * package treats any update as a balance change and would push it to the wallet.
     */
    public function attachMobilePass(MobilePass $mobilePass): void
    {
        $mobilePass->model()->associate($this);
        $mobilePass->saveQuietly();
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * @return BelongsTo<Merchant, $this>
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * @return BelongsTo<LoyaltyProgram, $this>
     */
    public function loyaltyProgram(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<CardActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(CardActivity::class);
    }

    public function stampsRemaining(): int
    {
        return max(0, $this->loyaltyProgram->stamps_required - $this->stamps);
    }
}
