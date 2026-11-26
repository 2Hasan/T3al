<?php

namespace App\Filament\Widgets;

use App\Enums\CardActivityType;
use App\Models\CardActivity;
use App\Models\Merchant;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * The latest stamps and redemptions at this shop. It sits low on the dashboard so
 * the cashier can see that the till is working without opening a report.
 */
class TodayActivity extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        /** @var Merchant $shop */
        $shop = Filament::getTenant();

        return $table
            ->heading(__('app.recent_activity'))
            ->query(fn () => CardActivity::query()
                ->where('merchant_id', $shop->id)
                ->with(['loyaltyCard.customer', 'user'])
                ->latest('id')
                ->limit(8))
            ->columns([
                TextColumn::make('loyaltyCard.customer.name')
                    ->label(__('app.customer_name'))
                    ->placeholder(__('app.walk_in')),
                TextColumn::make('type')
                    ->label(__('app.activity'))
                    ->formatStateUsing(fn (CardActivityType $state): string => $state->label())
                    ->badge()
                    ->color(fn (CardActivityType $state): string => $state === CardActivityType::Stamp ? 'success' : 'warning'),
                TextColumn::make('quantity')
                    ->label(__('app.quantity'))
                    ->visibleFrom('md'),
                TextColumn::make('user.name')
                    ->label(__('app.by_staff'))
                    ->visibleFrom('md'),
                TextColumn::make('created_at')
                    ->label(__('app.when'))
                    ->since(),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('app.no_activity_yet'))
            ->emptyStateDescription(__('app.no_activity_hint'));
    }
}
