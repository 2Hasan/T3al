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
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class ManageOrders extends ManageRecords
{
    protected static string $resource = OrderResource::class;

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
                ->action(function (array $data): void {
                    abort_unless(OrderResource::canAccess(), 403);

                    /** @var Merchant $shop */
                    $shop = Filament::getTenant();

                    $number = (int) $data['number'];

                    $card = OrderResource::findCard($shop, $data['lookup']);

                    if ($card === null) {
                        Notification::make()->title(__('app.card_not_found'))->danger()->send();

                        throw new Halt;
                    }

                    DB::transaction(function () use ($shop, $card, $data, $number): void {
                        // Lock the shop row so concurrent orders take turns computing the number.
                        Merchant::query()->whereKey($shop->id)->lockForUpdate()->first();

                        Order::create([
                            'merchant_id' => $shop->id,
                            'loyalty_card_id' => $card->id,
                            'created_by' => Filament::auth()->id(),
                            'number' => $number,
                            'note' => filled($data['note'] ?? null) ? $data['note'] : null,
                            'status' => OrderStatus::Preparing,
                        ]);
                    });

                    Notification::make()->title(__('app.order_created'))->success()->send();
                }),
        ];
    }
}
