<?php

namespace App\Filament\Admin\Resources\Merchants\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = null;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('app.payment_history');
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('paid_on', 'desc')
            ->columns([
                TextColumn::make('paid_on')->label(__('app.paid_on'))->date(),
                TextColumn::make('amount')
                    ->label(__('app.amount'))
                    ->formatStateUsing(fn (string $state): string => number_format((float) $state, 3).' BHD'),
                TextColumn::make('months')->label(__('app.months')),
                TextColumn::make('reference')->label(__('app.reference'))->placeholder('—'),
                TextColumn::make('note')->label(__('app.note'))->placeholder('—')->limit(40),
                TextColumn::make('recordedBy.name')->label(__('app.recorded_by'))->placeholder('—'),
            ])
            ->emptyStateHeading(__('app.no_payments'))
            ->emptyStateDescription(__('app.no_payments_help'))
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
