<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Models\LoyaltyCard;
use App\Models\Merchant;
use App\Models\Order;
use App\Support\BahrainPhone;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Ready orders: the till records an order against a customer's card, marks it
 * ready when it is prepared, and marks it collected when the customer picks it up.
 * Available on plans that include the order_queue feature.
 */
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        $shop = Filament::getTenant();

        return $shop instanceof Merchant
            && $shop->isOperational()
            && $shop->hasFeature('order_queue');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.orders');
    }

    public static function getModelLabel(): string
    {
        return __('app.order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.orders');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('note')
                ->label(__('app.order_note'))
                ->maxLength(200),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label(__('app.order_number'))
                    ->prefix('#'),
                TextColumn::make('loyaltyCard.customer.name')
                    ->label(__('app.customer_name'))
                    ->placeholder(__('app.walk_in')),
                TextColumn::make('note')
                    ->label(__('app.order_note'))
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('status')
                    ->label(__('app.order_status'))
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => $state->color()),
                TextColumn::make('created_at')
                    ->label(__('app.order_created_at'))
                    ->since(),
            ])
            ->recordActions([
                Action::make('markReady')
                    ->label(__('app.order_mark_ready'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::Preparing)
                    ->action(function (Order $record): void {
                        abort_unless(static::canAccess(), 403);
                        $record->markReady();
                    }),
                Action::make('markCollected')
                    ->label(__('app.order_mark_collected'))
                    ->icon(Heroicon::OutlinedHandRaised)
                    ->color('gray')
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::Ready)
                    ->action(function (Order $record): void {
                        abort_unless(static::canAccess(), 403);
                        $record->markCollected();
                    }),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOrders::route('/'),
        ];
    }

    /**
     * Finds the customer's card by its code or by the customer's phone number,
     * the same way the till does.
     */
    public static function findCard(Merchant $shop, string $term): ?LoyaltyCard
    {
        $term = trim($term);

        $card = $shop->loyaltyCards()->where('code', strtoupper($term))->first();

        if ($card === null && BahrainPhone::isValid($term)) {
            $card = $shop->loyaltyCards()
                ->whereRelation('customer', 'phone', BahrainPhone::normalize($term))
                ->latest('id')
                ->first();
        }

        return $card;
    }

    /**
     * Order numbers are typed by staff (the number on the paper ticket or the bag),
     * so one shop cannot use the same number twice in a day.
     */
    public static function numberTakenToday(Merchant $shop, int $number): bool
    {
        $start = now($shop->timezone)->startOfDay()->utc();

        return Order::query()
            ->where('merchant_id', $shop->id)
            ->where('created_at', '>=', $start)
            ->where('number', $number)
            ->exists();
    }
}
