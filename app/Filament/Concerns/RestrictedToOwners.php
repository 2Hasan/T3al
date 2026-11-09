<?php

namespace App\Filament\Concerns;

use App\Models\Merchant;
use App\Models\User;
use Filament\Facades\Filament;

/**
 * Hides a resource or page from cashiers: only the merchant's owners may open it.
 */
trait RestrictedToOwners
{
    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();
        $merchant = Filament::getTenant();

        return $user instanceof User
            && $merchant instanceof Merchant
            && $user->isOwnerOf($merchant);
    }
}
