<?php

namespace App\Models;

use App\Enums\OfferType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A smart offer the shop turns on. Offers are applied automatically at the till
 * when a stamp is added. Requires the smart_offers feature in the shop's plan.
 */
#[Fillable(['merchant_id', 'name', 'type', 'multiplier', 'weekdays', 'inactive_days', 'bonus_stamps', 'is_active'])]
class Offer extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => OfferType::class,
            'multiplier' => 'integer',
            'weekdays' => 'array',
            'inactive_days' => 'integer',
            'bonus_stamps' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Merchant, $this>
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
