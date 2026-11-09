<?php

namespace App\Models;

use Database\Factories\LoyaltyProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'merchant_id', 'name', 'stamps_required', 'reward_label',
    'background_color', 'foreground_color', 'label_color', 'terms', 'is_active',
    'google_class_synced_at',
])]
class LoyaltyProgram extends Model
{
    /** @use HasFactory<LoyaltyProgramFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stamps_required' => 'integer',
            'is_active' => 'boolean',
            'google_class_synced_at' => 'datetime',
        ];
    }

    /**
     * The Google Wallet class suffix for this program. The environment prefix keeps
     * local and production data apart when they share one Google issuer account.
     */
    public function googleClassSuffix(): string
    {
        return app()->environment().'-program-'.$this->id;
    }

    /**
     * @return BelongsTo<Merchant, $this>
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * @return HasMany<LoyaltyCard, $this>
     */
    public function loyaltyCards(): HasMany
    {
        return $this->hasMany(LoyaltyCard::class);
    }
}
