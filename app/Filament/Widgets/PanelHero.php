<?php

namespace App\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;

/**
 * Greeting card at the top of each dashboard. It uses the same dark surface and
 * amber accent as the marketing hero, so the panels feel like the same product.
 */
class PanelHero extends Widget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.panel-hero';

    /**
     * @return array{title: string, subtitle: string, label: string}
     */
    protected function getViewData(): array
    {
        $user = Filament::auth()->user();
        $tenant = Filament::getTenant();

        $isAdmin = Filament::getCurrentPanel()?->getId() === 'admin';

        return [
            'title' => __('app.hero_welcome', ['name' => $user?->name ?? '']),
            'subtitle' => $isAdmin
                ? __('app.hero_admin_subtitle')
                : __('app.hero_shop_subtitle'),
            'label' => $isAdmin ? __('app.hero_platform') : ($tenant?->name ?? ''),
        ];
    }
}
