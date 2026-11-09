<?php

namespace App\Filament\Resources\Branches;

use App\Filament\Concerns\RestrictedToOwners;
use App\Filament\Resources\Branches\Pages\ManageBranches;
use App\Models\Branch;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Branches are what the subscription is billed on: each branch costs the plan's price.
 */
class BranchResource extends Resource
{
    use RestrictedToOwners;

    protected static ?string $model = Branch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('app.branches');
    }

    public static function getModelLabel(): string
    {
        return __('app.branch');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.branches');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('app.branch_name'))
                ->required()
                ->maxLength(60),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('app.branch_name'))->searchable(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBranches::route('/'),
        ];
    }
}
