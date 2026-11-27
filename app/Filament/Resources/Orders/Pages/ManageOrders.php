<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Merchant;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;

class ManageOrders extends ManageRecords
{
    protected static string $resource = OrderResource::class;

    protected string $view = 'filament.resources.orders.lanes';

    /**
     * Orders still being made, then ready for pickup, then collected today.
     *
     * @return array{preparing: Collection<int, Order>, ready: Collection<int, Order>, collected: Collection<int, Order>}
     */
    #[Computed]
    public function lanes(): array
    {
        /** @var Merchant $shop */
        $shop = Filament::getTenant();
        $startOfToday = now($shop->timezone)->startOfDay()->utc();

        $open = Order::query()
            ->where('merchant_id', $shop->id)
            ->with('loyaltyCard.customer')
            ->whereIn('status', [OrderStatus::Preparing, OrderStatus::Ready])
            ->oldest('id')
            ->get();

        return [
            'preparing' => $open->where('status', OrderStatus::Preparing)->values(),
            'ready' => $open->where('status', OrderStatus::Ready)->values(),
            'collected' => Order::query()
                ->where('merchant_id', $shop->id)
                ->with('loyaltyCard.customer')
                ->where('status', OrderStatus::Collected)
                ->where('collected_at', '>=', $startOfToday)
                ->latest('collected_at')
                ->limit(20)
                ->get(),
        ];
    }

    /** Public state for the inline "new order" form on the page. */
    public string $orderNumber = '';

    public string $orderLookup = '';

    public string $orderNote = '';

    public function submitOrder(): void
    {
        $this->placeOrder([
            'number' => $this->orderNumber,
            'lookup' => $this->orderLookup,
            'note' => $this->orderNote,
        ]);

        $this->reset('orderNumber', 'orderLookup', 'orderNote');
        unset($this->lanes);
    }

    /**
     * Creates an order for the card found by code or phone. Used by the inline
     * form and by the header action.
     *
     * @param  array{number: mixed, lookup: mixed, note?: mixed}  $data
     */
    public function placeOrder(array $data): void
    {
        abort_unless(OrderResource::canAccess(), 403);

        Validator::make($data, [
            'number' => ['required', 'integer', 'min:1', 'max:9999'],
            'lookup' => ['required', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:200'],
        ])->validate();

        /** @var Merchant $shop */
        $shop = Filament::getTenant();
        $number = (int) $data['number'];

        $card = OrderResource::findCard($shop, (string) $data['lookup']);

        if ($card === null) {
            Notification::make()->title(__('app.card_not_found'))->danger()->send();

            return;
        }

        DB::transaction(function () use ($shop, $card, $data, $number): void {
            // Lock the shop row so concurrent orders take turns.
            Merchant::query()->whereKey($shop->id)->lockForUpdate()->first();

            Order::create([
                'merchant_id' => $shop->id,
                'loyalty_card_id' => $card->id,
                'created_by' => Filament::auth()->id(),
                'number' => $number,
                'note' => filled($data['note'] ?? null) ? (string) $data['note'] : null,
                'status' => OrderStatus::Preparing,
            ]);
        });

        Notification::make()->title(__('app.order_created'))->success()->send();
    }

    public function markReady(int $orderId): void
    {
        abort_unless(OrderResource::canAccess(), 403);

        $this->orderFor($orderId)->markReady();
        unset($this->lanes);
    }

    public function markCollected(int $orderId): void
    {
        abort_unless(OrderResource::canAccess(), 403);

        $this->orderFor($orderId)->markCollected();
        unset($this->lanes);
    }

    protected function orderFor(int $orderId): Order
    {
        /** @var Merchant $shop */
        $shop = Filament::getTenant();

        return Order::query()->where('merchant_id', $shop->id)->findOrFail($orderId);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newOrder')
                ->label(__('app.order_new'))
                ->icon(Heroicon::OutlinedPlus)
                ->modalHeading(__('app.order_new_heading'))
                ->modalSubmitActionLabel(__('app.order_save'))
                ->schema([
                    TextInput::make('number')
                        ->label(__('app.order_number'))
                        ->helperText(__('app.order_number_help'))
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(9999)
                        ->required(),
                    TextInput::make('lookup')
                        ->label(__('app.order_lookup'))
                        ->helperText(__('app.order_lookup_help'))
                        ->required()
                        ->maxLength(20),
                    TextInput::make('note')
                        ->label(__('app.order_note'))
                        ->maxLength(200),
                ])
                ->action(fn (array $data) => $this->placeOrder($data)),
        ];
    }
}
