<?php

namespace App\Support;

use RuntimeException;

class GoogleWallet
{
    public static function isConfigured(): bool
    {
        return filled(config('mobile-pass.google.issuer_id'))
            && (filled(config('mobile-pass.google.service_account_key'))
                || filled(config('mobile-pass.google.service_account_key_path')));
    }

    public static function logoUrl(): string
    {
        $configured = config('services.google_wallet.logo_url');

        if (filled($configured)) {
            return $configured;
        }

        if (str_starts_with((string) config('app.url'), 'https://')) {
            return asset('wallet/logo.png');
        }

        throw new RuntimeException(
            'Google Wallet needs a public HTTPS logo. Set GOOGLE_WALLET_LOGO_URL, or serve the app over HTTPS.'
        );
    }
}
