<?php

namespace App\Filament\Admin\Resources\Payments;

use App\Filament\Admin\Resources\Payments\Pages\ManagePayments;
use App\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Payment history. Payments are recorded from the shop's row on the Shops page.
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('app.admin_payments');
    }

    public static function getModelLabel(): string
    {
        return __('app.payment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.admin_payments');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['merchant', 'recordedBy'])->latest('paid_on'))
            ->columns([
                TextColumn::make('paid_on')->label(__('app.paid_on'))->date(),
                TextColumn::make('merchant.name')->label(__('app.shop'))->searchable(),
                TextColumn::make('amount')
                    ->label(__('app.amount'))
                    ->formatStateUsing(fn (string $state): string => number_format((float) $state, 3).' BHD'),
                TextColumn::make('months')->label(__('app.months')),
                TextColumn::make('reference')->label(__('app.reference'))->placeholder('—'),
                TextColumn::make('recordedBy.name')->label(__('app.recorded_by'))->placeholder('—'),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePayments::route('/'),
        ];
    }
}
