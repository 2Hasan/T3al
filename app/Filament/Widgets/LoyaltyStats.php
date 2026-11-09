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

    protected function getStats(): array
    {
        /** @var Merchant $merchant */
        $merchant = Filament::getTenant();
        $today = now($merchant->timezone)->startOfDay()->utc();
        $monthStart = now($merchant->timezone)->startOfMonth()->utc();

        $activities = $merchant->cardActivities();

        return [
            Stat::make('العملاء', $merchant->loyaltyCards()->count())
                ->description('انضموا هذا الشهر: '.$merchant->loyaltyCards()->where('created_at', '>=', $monthStart)->count()),
            Stat::make('أختام اليوم', (clone $activities)->where('type', CardActivityType::Stamp)->where('created_at', '>=', $today)->sum('quantity')),
            Stat::make('زيارات هذا الشهر', (clone $activities)->where('type', CardActivityType::Stamp)->where('created_at', '>=', $monthStart)->count()),
            Stat::make('مكافآت مصروفة هذا الشهر', (clone $activities)->where('type', CardActivityType::Redeem)->where('created_at', '>=', $monthStart)->count()),
        ];
    }
}
