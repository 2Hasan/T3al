<?php

namespace App\Http\Controllers;

use App\Actions\SyncAppleWalletPass;
use App\Actions\SyncGoogleWalletPass;
use App\Models\LoyaltyCard;
use App\Support\AppleWallet;
use App\Support\GoogleWallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The link the cashier hands to a newly registered customer. Its only job is to
 * let the customer add the card to their wallet; the URL contains the card's
 * unguessable code, so nobody else can open it.
 */
class CardController extends Controller
{
    public function show(LoyaltyCard $card): View
    {
        $card->load('loyaltyProgram.merchant', 'customer');

        return view('card', [
            'card' => $card,
            'program' => $card->loyaltyProgram,
            'merchant' => $card->loyaltyProgram->merchant,
            'appleWalletEnabled' => AppleWallet::isConfigured(),
            'googleWalletEnabled' => GoogleWallet::isConfigured(),
        ]);
    }

    public function addToAppleWallet(LoyaltyCard $card, SyncAppleWalletPass $syncAppleWalletPass): RedirectResponse
    {
        abort_unless(AppleWallet::isConfigured(), 404);

        $pass = $card->firstApplePass() ?? $syncAppleWalletPass->handle($card);

        return redirect()->away($pass->addToWalletUrl());
    }

    public function addToGoogleWallet(LoyaltyCard $card, SyncGoogleWalletPass $syncGoogleWalletPass): RedirectResponse
    {
        abort_unless(GoogleWallet::isConfigured(), 404);

        return redirect()->away($syncGoogleWalletPass->create($card)->addToWalletUrl());
    }
}
