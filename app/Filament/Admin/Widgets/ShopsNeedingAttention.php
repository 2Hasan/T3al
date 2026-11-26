<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Merchants\MerchantResource;
use App\Models\Merchant;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Shops whose trial or paid period ends within two weeks, so the admin can renew
 * or follow up before they are suspended.
 */
class ShopsNeedingAttention extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('app.shops_ending_soon'))
            ->query(fn () => Merchant::query()->endingWithin(14)->with('plan')->orderBy('trial_ends_at'))
            ->columns([
                TextColumn::make('name')->label(__('app.shop')),
                TextColumn::make('plan.name')->label(__('app.plan'))->placeholder('—'),
                TextColumn::make('periodEndsAt')
                    ->label(__('app.ends'))
                    ->state(fn (Merchant $record) => $record->periodEndsAt()?->format('Y-m-d')),
                TextColumn::make('daysLeft')
                    ->label(__('app.days_remaining_header'))
                    ->state(fn (Merchant $record) => $record->daysLeft()),
            ])
            ->recordUrl(fn (Merchant $record): string => MerchantResource::getUrl('view', ['record' => $record]))
            ->paginated(false)
            ->emptyStateHeading(__('app.none_ending_soon'));
    }
}
