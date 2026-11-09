<?php

namespace App\Actions;

use App\Models\LoyaltyCard;
use App\Models\LoyaltyProgram;
use App\Support\GoogleWallet;
use Spatie\LaravelMobilePass\Builders\Google\LoyaltyPassBuilder;
use Spatie\LaravelMobilePass\Builders\Google\LoyaltyPassClass;
use Spatie\LaravelMobilePass\Enums\BarcodeType;
use Spatie\LaravelMobilePass\Models\MobilePass;

/**
 * Google Wallet splits a pass in two: one "class" per loyalty program (name, logo,
 * colour) and one "object" per customer card (balance, barcode). This keeps both
 * in step with our database.
 */
class SyncGoogleWalletPass
{
    /**
     * Creates the customer's Google pass, or returns it if it already exists.
     */
    public function create(LoyaltyCard $card): MobilePass
    {
        $existingPass = $card->firstGooglePass();

        if ($existingPass !== null) {
            return $existingPass;
        }

        $card->loadMissing('loyaltyProgram.merchant', 'customer');
        $this->syncClass($card->loyaltyProgram);

        $builder = LoyaltyPassBuilder::make()
            ->setClass($card->loyaltyProgram->googleClassSuffix())
            ->setObjectSuffix(app()->environment().'-card-'.$card->code)
            ->setAccountId($card->code)
            ->setAccountName($card->customer->name)
            ->setBarcode(BarcodeType::Qr, $card->code, altText: $card->code)
            ->addTextModule('المكافأة', $card->loyaltyProgram->reward_label, 'reward');

        $this->applyBalance($builder, $card);

        if (filled($card->loyaltyProgram->terms)) {
            $builder->addTextModule('الشروط', $card->loyaltyProgram->terms, 'terms');
        }

        $pass = $builder->save();
        $card->attachMobilePass($pass);

        return $pass;
    }

    /**
     * Pushes the card's current balance to the customer's Google pass, if they saved one.
     */
    public function refresh(LoyaltyCard $card): void
    {
        $pass = $card->firstGooglePass();

        if ($pass === null) {
            return;
        }

        $card->loadMissing('loyaltyProgram');
        $this->syncClass($card->loyaltyProgram);

        $content = $pass->content;
        $content['googleObjectPayload']['loyaltyPoints'] = $this->stampsPoints($card);
        $content['googleObjectPayload']['secondaryLoyaltyPoints'] = $this->rewardsPoints($card);

        $pass->update(['content' => $content]);
    }

    /**
     * Creates or updates the program's Google class when it is new or was edited
     * since the last sync (name, colour or reward changes show on every card).
     */
    public function syncClass(LoyaltyProgram $program): void
    {
        if ($program->google_class_synced_at !== null && $program->google_class_synced_at->gte($program->updated_at)) {
            return;
        }

        $program->loadMissing('merchant');

        LoyaltyPassClass::make($program->googleClassSuffix())
            ->setIssuerName($program->merchant->name)
            ->setProgramName($program->name, 'ar')
            ->setProgramLogoUrl(GoogleWallet::logoUrl())
            ->setAccountNameLabel('الاسم', 'ar')
            ->setAccountIdLabel('رقم البطاقة', 'ar')
            ->setBackgroundColor($program->background_color)
            ->save();

        $program->forceFill(['google_class_synced_at' => now()])->saveQuietly();
    }

    protected function applyBalance(LoyaltyPassBuilder $builder, LoyaltyCard $card): void
    {
        $builder
            ->setBalanceLabel('الأختام')
            ->setBalanceString($this->stampsValue($card))
            ->setSecondaryBalanceLabel('مكافآت')
            ->setSecondaryBalanceString((string) $card->rewards_available);
    }

    /**
     * @return array{label: string, balance: array{string: string}}
     */
    protected function stampsPoints(LoyaltyCard $card): array
    {
        return ['label' => 'الأختام', 'balance' => ['string' => $this->stampsValue($card)]];
    }

    /**
     * @return array{label: string, balance: array{string: string}}
     */
    protected function rewardsPoints(LoyaltyCard $card): array
    {
        return ['label' => 'مكافآت', 'balance' => ['string' => (string) $card->rewards_available]];
    }

    protected function stampsValue(LoyaltyCard $card): string
    {
        return $card->stamps.' / '.$card->loyaltyProgram->stamps_required;
    }
}
