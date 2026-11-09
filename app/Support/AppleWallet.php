<?php

namespace App\Support;

class AppleWallet
{
    /**
     * Apple passes can only be downloaded once they can be signed with the Pass Type ID certificate.
     */
    public static function isConfigured(): bool
    {
        return filled(config('mobile-pass.apple.type_identifier'))
            && filled(config('mobile-pass.apple.team_identifier'))
            && (filled(config('mobile-pass.apple.certificate'))
                || filled(config('mobile-pass.apple.certificate_path')));
    }
}
