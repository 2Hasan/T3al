<?php

namespace App\Filament\Admin\Resources\Plans;

use App\Filament\Admin\Resources\Plans\Pages\ManagePlans;
use App\Models\Plan;
use App\Support\PlanFeatures;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Plans are bundles of features with a price per branch per month. The owner
 * creates them here; shops are assigned to one of them.
 */
class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('app.admin_plans');
    }

    public static function getModelLabel(): string
    {
        return __('app.plan');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.admin_plans');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('app.plan_name'))
                ->required()
                ->maxLength(60),
            TextInput::make('slug')
                ->label(__('app.plan_slug'))
                ->required()
                ->unique(ignoreRecord: true)
                ->alphaDash()
                ->maxLength(60),
            TextInput::make('price_per_branch')
                ->label(__('app.price_per_branch'))
                ->helperText(__('app.price_per_branch_help'))
                ->numeric()
                ->minValue(0)
                ->step('0.001')
                ->suffix('BHD')
                ->required(),
            CheckboxList::make('features')
                ->label(__('app.plan_features'))
                ->options(PlanFeatures::options())
                ->columns(2)
                ->required(),
            Toggle::make('is_active')
                ->label(__('app.active'))
                ->default(true),
            TextInput::make('sort_order')
                ->label(__('app.sort_order'))
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label(__('app.plan_name'))->searchable(),
                TextColumn::make('price_per_branch')
                    ->label(__('app.price_per_branch'))
                    ->formatStateUsing(fn (string $state): string => number_format((float) $state, 3).' BHD'),
                TextColumn::make('features')
                    ->label(__('app.plan_features'))
                    ->state(fn (Plan $record): string => count($record->features ?? []).' '.__('app.features_count')),
                TextColumn::make('merchants_count')->label(__('app.shops'))->counts('merchants'),
                IconColumn::make('is_active')->label(__('app.active'))->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePlans::route('/'),
        ];
    }
}
