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

    protected static ?string $modelLabel = 'عميل';

    protected static ?string $pluralModelLabel = 'العملاء';

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
                Section::make('العميل')
                    ->schema([
                        TextEntry::make('customer.name')->label('الاسم'),
                        TextEntry::make('customer.phone')->label('الجوال')->formatStateUsing(fn (string $state): HtmlString => static::phoneHtml($state)),
                        TextEntry::make('loyaltyProgram.name')->label('البطاقة'),
                        TextEntry::make('code')->label('رقم البطاقة')->copyable(),
                    ]),
                Section::make('الرصيد')
                    ->schema([
                        TextEntry::make('stamps')
                            ->label('الأختام الحالية')
                            ->formatStateUsing(fn (LoyaltyCard $record): string => $record->stamps.' / '.$record->loyaltyProgram->stamps_required),
                        TextEntry::make('rewards_available')->label('مكافآت متاحة'),
                        TextEntry::make('lifetime_stamps')->label('مجموع الأختام'),
                        TextEntry::make('rewards_redeemed')->label('مكافآت مصروفة'),
                        TextEntry::make('last_visit_at')->label('آخر زيارة')->since()->placeholder('ما زار للحين'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer', 'loyaltyProgram']))
            ->defaultSort('last_visit_at', 'desc')
            ->columns([
                TextColumn::make('customer.name')->label('الاسم')->searchable(),
                TextColumn::make('customer.phone')
                    ->label('الجوال')
                    ->formatStateUsing(fn (string $state): HtmlString => static::phoneHtml($state))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereRelation('customer', 'phone', 'like', '%'.preg_replace('/\D+/', '', $search).'%')),
                TextColumn::make('stamps')
                    ->label('الأختام')
                    ->formatStateUsing(fn (LoyaltyCard $record): string => $record->stamps.' / '.$record->loyaltyProgram->stamps_required),
                TextColumn::make('rewards_available')->label('مكافآت متاحة')->sortable(),
                TextColumn::make('lifetime_stamps')->label('مجموع الأختام')->sortable(),
                TextColumn::make('last_visit_at')->label('آخر زيارة')->since()->sortable()->placeholder('—'),
                TextColumn::make('created_at')->label('انضم')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('has_reward')
                    ->label('عنده مكافأة')
                    ->query(fn (Builder $query) => $query->where('rewards_available', '>', 0)),
                Filter::make('inactive')
                    ->label('ما زار من 30 يوم')
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
