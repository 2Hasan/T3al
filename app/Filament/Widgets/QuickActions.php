<?php

namespace App\Filament\Widgets;

use App\Models\Merchant;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

/**
 * The few things a shop does most, as large buttons at the top of the dashboard.
 * Only the actions the shop's plan allows are shown.
 */
class QuickActions extends Widget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.quick-actions';

    /**
     * @return array<int, array{label: string, url: string, icon: string}>
     */
    protected function getViewData(): array
    {
        /** @var Merchant $shop */
        $shop = Filament::getTenant();
        $base = url('/app/'.$shop->slug);

        $actions = [
            ['label' => __('app.action_scan'), 'url' => $base.'/scan-card', 'icon' => 'scan'],
            ['label' => __('app.action_cards'), 'url' => $base.'/loyalty-cards', 'icon' => 'card'],
        ];

        if ($shop->hasFeature('order_queue')) {
            $actions[] = ['label' => __('app.action_orders'), 'url' => $base.'/orders', 'icon' => 'orders'];
        }

        if ($shop->hasFeature('analytics')) {
            $actions[] = ['label' => __('app.action_analytics'), 'url' => $base.'/analytics', 'icon' => 'chart'];
        }

        return ['actions' => $actions];
    }
}
