<?php

namespace App\Filament\Resources\LoyaltyPrograms;

use App\Filament\Concerns\RestrictedToOwners;
use App\Filament\Resources\LoyaltyPrograms\Pages\ManageLoyaltyPrograms;
use App\Models\LoyaltyProgram;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoyaltyProgramResource extends Resource
{
    use RestrictedToOwners;

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
                Section::make(__('app.card'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('app.card_name'))
                            ->placeholder(__('app.coffee_card'))
                            ->required()
                            ->maxLength(60),
                        TextInput::make('reward_label')
                            ->label(__('app.reward'))
                            ->placeholder(__('app.free_coffee'))
                            ->required()
                            ->maxLength(40),
                        TextInput::make('stamps_required')
                            ->label(__('app.stamps_required_label'))
                            ->numeric()
                            ->integer()
                            ->minValue(2)
                            ->maxValue(30)
                            ->default(10)
                            ->required()
                            ->disabledOn('edit')
                            ->helperText(__('app.stamps_required_helper')),
                        Toggle::make('is_active')
                            ->label(__('app.active'))
                            ->default(true)
                            ->inline(false),
                    ]),
                Section::make(__('app.colors'))
                    ->columns(3)
                    ->schema([
                        ColorPicker::make('background_color')->label(__('app.background'))->default('#3B2416')->required()->extraInputAttributes(['dir' => 'ltr']),
                        ColorPicker::make('foreground_color')->label(__('app.text'))->default('#FFFFFF')->required()->extraInputAttributes(['dir' => 'ltr']),
                        ColorPicker::make('label_color')->label(__('app.labels'))->default('#E7D7C9')->required()->extraInputAttributes(['dir' => 'ltr']),
                    ]),
                Textarea::make('terms')
                    ->label(__('app.terms_back'))
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
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
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLoyaltyPrograms::route('/'),
        ];
    }
}
