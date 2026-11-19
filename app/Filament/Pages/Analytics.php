<?php

namespace App\Filament\Pages;

use App\Models\Merchant;
use App\Models\User;
use App\Support\ShopAnalytics;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Shop analytics for the owner: stamps, visits, new and returning customers, and
 * the busiest days. Available on plans that include the analytics feature.
 */
class Analytics extends Page
{
    protected string $view = 'filament.pages.analytics';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 6;

    public static function canAccess(): bool
    {
        $shop = Filament::getTenant();
        $user = Filament::auth()->user();

        return $shop instanceof Merchant
            && $shop->isOperational()
            && $shop->hasFeature('analytics')
            && $user instanceof User
            && $user->isOwnerOf($shop);
    }

    public static function getNavigationLabel(): string
    {
        return __('app.analytics');
    }

    public function getTitle(): string
    {
        return __('app.analytics');
    }

    /**
     * @return array<string, mixed>
     */
    public function figures(): array
    {
        /** @var Merchant $shop */
        $shop = Filament::getTenant();

        return ShopAnalytics::for($shop);
    }
}
