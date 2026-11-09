<?php

namespace App\Actions;

use App\Models\LoyaltyCard;
use Spatie\LaravelMobilePass\Builders\Apple\StoreCardPassBuilder;
use Spatie\LaravelMobilePass\Enums\BarcodeType;
use Spatie\LaravelMobilePass\Models\MobilePass;

/**
 * Creates the Apple Wallet store card for a loyalty card, or pushes the
 * card's latest balance to the copy already sitting in the customer's Wallet.
 */
class SyncAppleWalletPass
{
    public function handle(LoyaltyCard $card): MobilePass
    {
        $card->loadMissing('loyaltyProgram.merchant', 'customer');

        $existingPass = $card->firstApplePass();

        if ($existingPass === null) {
            $pass = $this->build($card)->save();
            $card->attachMobilePass($pass);

            return $pass;
        }

        $existingPass->builder()
            ->updateField('stamps', $this->stampsValue($card))
            ->updateField('rewards', (string) $card->rewards_available)
            ->save();

        return $existingPass;
    }

    protected function build(LoyaltyCard $card): StoreCardPassBuilder
    {
        $program = $card->loyaltyProgram;
        $merchant = $program->merchant;

        $builder = StoreCardPassBuilder::make()
            ->setOrganizationName($merchant->name)
            ->setDescription($program->name)
            ->setLogoText($merchant->name)
            ->setBackgroundColor($program->background_color)
            ->setForegroundColor($program->foreground_color)
            ->setLabelColor($program->label_color)
            ->setIconImage(
                resource_path('wallet/icon.png'),
                resource_path('wallet/icon@2x.png'),
                resource_path('wallet/icon@3x.png'),
            )
            ->addHeaderField('rewards', (string) $card->rewards_available, label: 'مكافآت', changeMessage: 'مكافآتك المتاحة: :value')
            ->addField('stamps', $this->stampsValue($card), label: 'الأختام', changeMessage: 'أختامك الحين: :value')
            ->addSecondaryField('reward', $program->reward_label, label: 'المكافأة')
            ->addSecondaryField('customer', $card->customer->name, label: 'الاسم')
            ->setBarcode(BarcodeType::Qr, $card->code, altText: $card->code)
            ->setDownloadName('loyalty-card');

        if (filled($program->terms)) {
            $builder->addBackField('terms', $program->terms, label: 'الشروط');
        }

        return $builder;
    }

    protected function stampsValue(LoyaltyCard $card): string
    {
        return $card->stamps.' / '.$card->loyaltyProgram->stamps_required;
    }
}
