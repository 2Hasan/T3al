<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\MerchantStatus;
use App\Models\Merchant;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * The headline numbers for the platform owner: shops by status, monthly revenue and
 * renewals coming up.
 */
class PlatformStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    /**
     * Two tiles per row on phones, three on large screens, so the six tiles
     * do not stack into one long column.
     */
    protected function getColumns(): int|array|null
    {
        return ['default' => 2, 'xl' => 3];
    }

    protected function getStats(): array
    {
        $shops = Merchant::query();
        $active = (clone $shops)->where('status', MerchantStatus::Active->value)->with('plan')->withCount('branches')->get();

        $monthlyRevenue = $active->reduce(
            fn (float $sum, Merchant $shop): float => $sum + (float) $shop->monthlyAmount($shop->branches_count),
            0.0,
        );

        $paidThisMonth = (float) Payment::query()
            ->whereBetween('paid_on', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        return [
            Stat::make(__('app.stats_shops'), (clone $shops)->count())
                ->description(__('app.stats_shops_new', ['count' => (clone $shops)->where('created_at', '>=', now()->startOfMonth())->count()]))
                ->icon('heroicon-o-building-storefront'),
            Stat::make(__('app.stats_active'), (clone $shops)->where('status', MerchantStatus::Active->value)->count())
                ->color('success')
                ->icon('heroicon-o-check-circle'),
            Stat::make(__('app.stats_trial'), (clone $shops)->where('status', MerchantStatus::Trial->value)->count())
                ->color('warning')
                ->icon('heroicon-o-clock'),
            Stat::make(__('app.stats_suspended'), (clone $shops)->where('status', MerchantStatus::Suspended->value)->count())
                ->color('danger')
                ->icon('heroicon-o-pause-circle'),
            Stat::make(__('app.stats_monthly_revenue'), number_format($monthlyRevenue, 3).' BHD')
                ->description(__('app.stats_collected_month', ['amount' => number_format($paidThisMonth, 3)]))
                ->icon('heroicon-o-banknotes'),
            Stat::make(__('app.stats_ending_soon'), Merchant::query()->endingWithin(7)->count())
                ->description(__('app.stats_ending_soon_help'))
                ->color('warning')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
