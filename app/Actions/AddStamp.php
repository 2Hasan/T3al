<?php

namespace App\Actions;

use App\Enums\CardActivityType;
use App\Models\LoyaltyCard;
use App\Models\User;
use App\Support\StampOffers;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Adds stamps at the till. Every time the card fills up, a reward is unlocked
 * and the extra stamps carry over to the next round.
 */
class AddStamp
{
    public const MAX_STAMPS_PER_SCAN = 5;

    public function __construct(private SyncWalletPasses $syncWalletPasses) {}

    public function handle(LoyaltyCard $card, User $staff, int $quantity = 1): LoyaltyCard
    {
        if ($quantity < 1 || $quantity > self::MAX_STAMPS_PER_SCAN) {
            throw new InvalidArgumentException(__('app.stamps_range', ['max' => self::MAX_STAMPS_PER_SCAN]));
        }

        $card = DB::transaction(function () use ($card, $staff, $quantity): LoyaltyCard {
            /** @var LoyaltyCard $locked */
            $locked = LoyaltyCard::query()
                ->with(['loyaltyProgram', 'merchant', 'customer'])
                ->lockForUpdate()
                ->findOrFail($card->id);

            $required = $locked->loyaltyProgram->stamps_required;

            // Offers are worked out before the last visit is updated, so win-back sees the old visit.
            $offer = StampOffers::bonusFor($locked, $locked->merchant, $quantity, now());
            $added = $quantity + $offer['bonus'];
            $total = $locked->stamps + $added;

            $locked->update([
                'stamps' => $total % $required,
                'rewards_available' => $locked->rewards_available + intdiv($total, $required),
                'lifetime_stamps' => $locked->lifetime_stamps + $added,
                'last_visit_at' => now(),
                'birthday_bonus_at' => $offer['birthday'] ? now() : $locked->birthday_bonus_at,
            ]);

            $locked->activities()->create([
                'merchant_id' => $locked->merchant_id,
                'user_id' => $staff->id,
                'type' => CardActivityType::Stamp,
                'quantity' => $added,
            ]);

            return $locked;
        });

        $this->syncWalletPasses->handle($card);

        return $card;
    }
}
