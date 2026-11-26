<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Payment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * The latest payments recorded across the platform.
 */
class RecentPayments extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('app.recent_payments'))
            ->query(fn () => Payment::query()->with('merchant')->latest('paid_on')->limit(8))
            ->columns([
                TextColumn::make('merchant.name')->label(__('app.shop')),
                TextColumn::make('amount')->label(__('app.amount'))->money('BHD'),
                TextColumn::make('months')->label(__('app.months_paid')),
                TextColumn::make('paid_on')->label(__('app.paid_on'))->date(),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('app.no_payments_yet'));
    }
}
