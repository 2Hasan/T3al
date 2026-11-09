<?php

namespace App\Actions;

use App\Enums\MerchantStatus;
use App\Models\Merchant;

/**
 * Suspends shops whose trial or paid period has ended. Run daily.
 */
class SuspendExpiredMerchants
{
    /**
     * @return int number of shops suspended
     */
    public function handle(): int
    {
        $expired = Merchant::query()
            ->where('status', '!=', MerchantStatus::Suspended->value)
            ->where(function ($query) {
                $query->where(function ($trial) {
                    $trial->where('status', MerchantStatus::Trial->value)
                        ->where('trial_ends_at', '<', now());
                })->orWhere(function ($paid) {
                    $paid->where('status', MerchantStatus::Active->value)
                        ->where('paid_until', '<', now());
                });
            });

        return $expired->update(['status' => MerchantStatus::Suspended->value]);
    }
}
