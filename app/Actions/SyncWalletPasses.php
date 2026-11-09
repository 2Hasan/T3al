<?php

namespace App\Actions;

use App\Models\LoyaltyCard;
use Throwable;

/**
 * Brings every wallet copy of a card up to date after its balance changes.
 * A wallet provider being down must never undo a stamp the cashier just gave,
 * so failures are reported rather than thrown.
 */
class SyncWalletPasses
{
    public function __construct(
        private SyncAppleWalletPass $syncAppleWalletPass,
        private SyncGoogleWalletPass $syncGoogleWalletPass,
    ) {}

    public function handle(LoyaltyCard $card): void
    {
        try {
            $this->syncAppleWalletPass->handle($card);
        } catch (Throwable $exception) {
            report($exception);
        }

        try {
            $this->syncGoogleWalletPass->refresh($card);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
