<?php

namespace App\Filament\Widgets;

use App\Enums\CardActivityType;
use App\Models\Merchant;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LoyaltyStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /**
     * Two tiles per row on phones, four on large screens.
     */
    protected function getColumns(): int|array|null
    {
        return ['default' => 2, 'xl' => 4];
    }

    protected function getStats(): array
    {
        /** @var Merchant $merchant */
        $merchant = Filament::getTenant();
        $today = now($merchant->timezone)->startOfDay()->utc();
        $monthStart = now($merchant->timezone)->startOfMonth()->utc();

        $activities = $merchant->cardActivities();

        return [
            Stat::make(__('app.customers'), $merchant->loyaltyCards()->count())
                ->description(__('app.joined_this_month', ['count' => $merchant->loyaltyCards()->where('created_at', '>=', $monthStart)->count()])),
            Stat::make(__('app.stamps_today'), (clone $activities)->where('type', CardActivityType::Stamp)->where('created_at', '>=', $today)->sum('quantity')),
            Stat::make(__('app.visits_this_month'), (clone $activities)->where('type', CardActivityType::Stamp)->where('created_at', '>=', $monthStart)->count()),
            Stat::make(__('app.rewards_redeemed_month'), (clone $activities)->where('type', CardActivityType::Redeem)->where('created_at', '>=', $monthStart)->count()),
        ];
    }
}
