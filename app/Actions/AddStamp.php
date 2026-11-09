<?php

namespace App\Actions;

use App\Enums\CardActivityType;
use App\Models\LoyaltyCard;
use App\Models\User;
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
            $locked = LoyaltyCard::query()->with('loyaltyProgram')->lockForUpdate()->findOrFail($card->id);
            $required = $locked->loyaltyProgram->stamps_required;
            $total = $locked->stamps + $quantity;

            $locked->update([
                'stamps' => $total % $required,
                'rewards_available' => $locked->rewards_available + intdiv($total, $required),
                'lifetime_stamps' => $locked->lifetime_stamps + $quantity,
                'last_visit_at' => now(),
            ]);

            $locked->activities()->create([
                'merchant_id' => $locked->merchant_id,
                'user_id' => $staff->id,
                'type' => CardActivityType::Stamp,
                'quantity' => $quantity,
            ]);

            return $locked;
        });

        $this->syncWalletPasses->handle($card);

        return $card;
    }
}
