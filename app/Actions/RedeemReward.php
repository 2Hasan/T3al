<?php

namespace App\Actions;

use App\Enums\CardActivityType;
use App\Exceptions\NoRewardAvailable;
use App\Models\LoyaltyCard;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RedeemReward
{
    public function __construct(private SyncWalletPasses $syncWalletPasses) {}

    /**
     * @throws NoRewardAvailable
     */
    public function handle(LoyaltyCard $card, User $staff): LoyaltyCard
    {
        $card = DB::transaction(function () use ($card, $staff): LoyaltyCard {
            /** @var LoyaltyCard $locked */
            $locked = LoyaltyCard::query()->with('loyaltyProgram')->lockForUpdate()->findOrFail($card->id);

            if ($locked->rewards_available < 1) {
                throw new NoRewardAvailable;
            }

            $locked->update([
                'rewards_available' => $locked->rewards_available - 1,
                'rewards_redeemed' => $locked->rewards_redeemed + 1,
                'last_visit_at' => now(),
            ]);

            $locked->activities()->create([
                'merchant_id' => $locked->merchant_id,
                'user_id' => $staff->id,
                'type' => CardActivityType::Redeem,
                'quantity' => 1,
            ]);

            return $locked;
        });

        $this->syncWalletPasses->handle($card);

        return $card;
    }
}
