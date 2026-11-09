<?php

namespace App\Filament\Resources\LoyaltyCards;

use App\Filament\Resources\LoyaltyCards\Pages\ManageLoyaltyCards;
use App\Models\LoyaltyCard;
use App\Support\BahrainPhone;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * Customers are registered by the cashier on the scan page, so this list is read-only.
 */
class LoyaltyCardResource extends Resource
{
    protected static ?string $model = LoyaltyCard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function getModelLabel(): string
    {
        return __('app.customer_singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.customers');
    }

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /**
     * Phone numbers are left-to-right text; isolating them stops the RTL layout from moving the "+".
     */
    protected static function phoneHtml(string $storedPhone): HtmlString
    {
        return new HtmlString('<bdi dir="ltr">'.e(BahrainPhone::format($storedPhone)).'</bdi>');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make(__('app.customer'))
                    ->schema([
                        TextEntry::make('customer.name')->label(__('app.name')),
                        TextEntry::make('customer.phone')->label(__('app.phone'))->formatStateUsing(fn (string $state): HtmlString => static::phoneHtml($state)),
                        TextEntry::make('loyaltyProgram.name')->label(__('app.card')),
                        TextEntry::make('code')->label(__('app.card_number'))->copyable(),
                    ]),
                Section::make(__('app.balance'))
                    ->schema([
                        TextEntry::make('stamps')
                            ->label(__('app.current_stamps'))
                            ->formatStateUsing(fn (LoyaltyCard $record): string => $record->stamps.' / '.$record->loyaltyProgram->stamps_required),
                        TextEntry::make('rewards_available')->label(__('app.rewards_available')),
                        TextEntry::make('lifetime_stamps')->label(__('app.lifetime_stamps')),
                        TextEntry::make('rewards_redeemed')->label(__('app.rewards_redeemed')),
                        TextEntry::make('last_visit_at')->label(__('app.last_visit'))->since()->placeholder(__('app.never_visited')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer', 'loyaltyProgram']))
            ->defaultSort('last_visit_at', 'desc')
            ->columns([
                TextColumn::make('customer.name')->label(__('app.name'))->searchable(),
                TextColumn::make('customer.phone')
                    ->label(__('app.phone'))
                    ->formatStateUsing(fn (string $state): HtmlString => static::phoneHtml($state))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereRelation('customer', 'phone', 'like', '%'.preg_replace('/\D+/', '', $search).'%')),
                TextColumn::make('stamps')
                    ->label(__('app.stamps'))
                    ->formatStateUsing(fn (LoyaltyCard $record): string => $record->stamps.' / '.$record->loyaltyProgram->stamps_required),
                TextColumn::make('rewards_available')->label(__('app.rewards_available'))->sortable(),
                TextColumn::make('lifetime_stamps')->label(__('app.lifetime_stamps'))->sortable(),
                TextColumn::make('last_visit_at')->label(__('app.last_visit'))->since()->sortable()->placeholder('—'),
                TextColumn::make('created_at')->label(__('app.joined'))->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('has_reward')
                    ->label(__('app.has_reward'))
                    ->query(fn (Builder $query) => $query->where('rewards_available', '>', 0)),
                Filter::make('inactive')
                    ->label(__('app.not_visited_30'))
                    ->query(fn (Builder $query) => $query->where('last_visit_at', '<', now()->subDays(30))),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLoyaltyCards::route('/'),
        ];
    }
}
