<?php

namespace App\Filament\Resources\Offers;

use App\Enums\OfferType;
use App\Filament\Concerns\RestrictedToOwners;
use App\Filament\Resources\Offers\Pages\ManageOffers;
use App\Models\Merchant;
use App\Models\Offer;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Smart offers the shop owner turns on. They apply automatically at the till.
 * Available on plans that include the smart_offers feature.
 */
class OfferResource extends Resource
{
    use RestrictedToOwners {
        canAccess as protected ownersOnlyCanAccess;
    }

    protected static ?string $model = Offer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        $shop = Filament::getTenant();

        return static::ownersOnlyCanAccess()
            && $shop instanceof Merchant
            && $shop->isOperational()
            && $shop->hasFeature('smart_offers');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.smart_offers');
    }

    public static function getModelLabel(): string
    {
        return __('app.offer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.smart_offers');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('app.offer_name'))
                ->required()
                ->maxLength(60),
            Select::make('type')
                ->label(__('app.offer_type'))
                ->options(collect(OfferType::cases())->mapWithKeys(fn (OfferType $type) => [$type->value => $type->label()])->all())
                ->required()
                ->live(),
            Select::make('multiplier')
                ->label(__('app.offer_multiplier'))
                ->options([2 => '× 2', 3 => '× 3', 4 => '× 4'])
                ->default(2)
                ->required()
                ->visible(fn (Get $get): bool => $get('type') === OfferType::Multiplier->value),
            CheckboxList::make('weekdays')
                ->label(__('app.offer_weekdays'))
                ->helperText(__('app.offer_weekdays_help'))
                ->options(self::weekdayOptions())
                ->columns(4)
                ->visible(fn (Get $get): bool => $get('type') === OfferType::Multiplier->value),
            TextInput::make('inactive_days')
                ->label(__('app.offer_inactive_days'))
                ->numeric()
                ->integer()
                ->minValue(7)
                ->maxValue(365)
                ->default(30)
                ->required()
                ->visible(fn (Get $get): bool => $get('type') === OfferType::WinBack->value),
            TextInput::make('bonus_stamps')
                ->label(__('app.offer_bonus_stamps'))
                ->numeric()
                ->integer()
                ->minValue(1)
                ->maxValue(5)
                ->default(1)
                ->required()
                ->visible(fn (Get $get): bool => in_array($get('type'), [OfferType::WinBack->value, OfferType::Birthday->value], true)),
            Toggle::make('is_active')
                ->label(__('app.active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('app.offer_name'))->searchable(),
                TextColumn::make('type')
                    ->label(__('app.offer_type'))
                    ->formatStateUsing(fn (OfferType $state): string => $state->label()),
                TextColumn::make('bonus_stamps')->label(__('app.offer_bonus_stamps')),
                IconColumn::make('is_active')->label(__('app.active'))->boolean(),
            ])
            ->recordActions([
                EditAction::make()->modalWidth('2xl'),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOffers::route('/'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function weekdayOptions(): array
    {
        // Keys match Carbon's dayOfWeek: 0 = Sunday.
        $sunday = now()->startOfWeek(CarbonInterface::SUNDAY);

        return collect(range(0, 6))
            ->mapWithKeys(fn (int $day): array => [$day => $sunday->copy()->addDays($day)->locale(app()->getLocale())->isoFormat('dddd')])
            ->all();
    }
}
