<?php

namespace App\Filament\Resources\LoyaltyPrograms;

use App\Filament\Concerns\RestrictedToOwners;
use App\Filament\Resources\LoyaltyPrograms\Pages\ManageLoyaltyPrograms;
use App\Models\LoyaltyProgram;
use App\Models\Merchant;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class LoyaltyProgramResource extends Resource
{
    use RestrictedToOwners {
        canAccess as protected ownersOnlyCanAccess;
    }

    public static function canAccess(): bool
    {
        $shop = Filament::getTenant();

        return static::ownersOnlyCanAccess()
            && $shop instanceof Merchant
            && $shop->isOperational()
            && $shop->hasFeature('loyalty_cards');
    }

    protected static ?string $model = LoyaltyProgram::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    public static function getModelLabel(): string
    {
        return __('app.loyalty_card');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.loyalty_cards');
    }

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columns(['default' => 1, 'lg' => 5])
                    ->columnSpanFull()
                    ->schema([
                        Group::make()
                            ->columnSpan(['default' => 1, 'lg' => 3])
                            ->schema([
                                Section::make(__('app.card'))
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('app.card_name'))
                                            ->placeholder(__('app.coffee_card'))
                                            ->required()
                                            ->maxLength(60)
                                            ->live(debounce: 300),
                                        TextInput::make('reward_label')
                                            ->label(__('app.reward'))
                                            ->placeholder(__('app.free_coffee'))
                                            ->required()
                                            ->maxLength(40)
                                            ->live(debounce: 300),
                                        TextInput::make('stamps_required')
                                            ->label(__('app.stamps_required_label'))
                                            ->numeric()
                                            ->integer()
                                            ->minValue(2)
                                            ->maxValue(30)
                                            ->default(10)
                                            ->required()
                                            ->disabledOn('edit')
                                            ->live(debounce: 300)
                                            ->helperText(__('app.stamps_required_helper')),
                                        Toggle::make('is_active')
                                            ->label(__('app.active'))
                                            ->default(true)
                                            ->inline(false),
                                    ]),
                                Section::make(__('app.colors'))
                                    ->columns(3)
                                    ->schema([
                                        ColorPicker::make('background_color')->label(__('app.background'))->default('#3B2416')->required()->live()->extraInputAttributes(['dir' => 'ltr']),
                                        ColorPicker::make('foreground_color')->label(__('app.text'))->default('#FFFFFF')->required()->live()->extraInputAttributes(['dir' => 'ltr']),
                                        ColorPicker::make('label_color')->label(__('app.labels'))->default('#E7D7C9')->required()->live()->extraInputAttributes(['dir' => 'ltr']),
                                    ]),
                                Textarea::make('terms')
                                    ->label(__('app.terms_back'))
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->live(debounce: 300)
                                    ->columnSpanFull(),
                            ]),
                        Section::make(__('app.card_preview'))
                            ->columnSpan(['default' => 1, 'lg' => 2])
                            ->schema([
                                Placeholder::make('preview')
                                    ->hiddenLabel()
                                    ->content(fn (Get $get): HtmlString => new HtmlString(
                                        view('filament.loyalty-card-preview', self::previewData($get))->render()
                                    )),
                            ]),
                    ]),
            ]);
    }

    /**
     * The values the preview draws with. Colours are checked as hex so the preview
     * can never inject anything into the page; invalid input falls back to the defaults.
     *
     * @return array{name: string, reward: string, stamps: int, background: string, foreground: string, label: string, terms: string}
     */
    public static function previewData(callable $get): array
    {
        $hex = fn (mixed $value, string $fallback): string => is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value)
            ? strtoupper($value)
            : $fallback;

        return [
            'name' => (string) ($get('name') ?: __('app.card_name')),
            'reward' => (string) ($get('reward_label') ?: __('app.reward')),
            'stamps' => max(2, min(30, (int) ($get('stamps_required') ?: 10))),
            'background' => $hex($get('background_color'), '#3B2416'),
            'foreground' => $hex($get('foreground_color'), '#FFFFFF'),
            'label' => $hex($get('label_color'), '#E7D7C9'),
            'terms' => (string) ($get('terms') ?? ''),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('background_color')->label(''),
                TextColumn::make('name')->label(__('app.card'))->searchable(),
                TextColumn::make('reward_label')->label(__('app.reward')),
                TextColumn::make('stamps_required')->label(__('app.stamps')),
                TextColumn::make('loyalty_cards_count')->label(__('app.customers'))->counts('loyaltyCards'),
                IconColumn::make('is_active')->label(__('app.active'))->boolean(),
            ])
            ->recordActions([
                EditAction::make()->modalWidth('5xl'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLoyaltyPrograms::route('/'),
        ];
    }
}
