<?php

namespace App\Filament\Pages;

use App\Actions\AddStamp;
use App\Actions\IssueLoyaltyCard;
use App\Actions\RedeemReward;
use App\Exceptions\NoRewardAvailable;
use App\Models\LoyaltyCard;
use App\Models\Merchant;
use App\Models\User;
use App\Support\BahrainPhone;
use BackedEnum;
use chillerlan\QRCode\QRCode;
use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Computed;

/**
 * The till screen. Cashiers register new customers here, hand them their card
 * link, and stamp or redeem by scanning (or typing) a card code or phone number.
 */
class ScanCard extends Page
{
    protected string $view = 'filament.pages.scan-card';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    public static function canAccess(): bool
    {
        return Filament::getTenant()?->isOperational() ?? false;
    }

    public function getTitle(): string
    {
        return __('app.scan_card');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.scan_card');
    }

    protected static ?int $navigationSort = 1;

    public string $search = '';

    public ?int $cardId = null;

    public bool $showCardLink = false;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('registerCustomer')
                ->label(__('app.new_customer'))
                ->icon(Heroicon::OutlinedUserPlus)
                ->modalHeading(__('app.register_customer'))
                ->modalSubmitActionLabel(__('app.register_and_give_card'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('app.customer_name'))
                        ->required()
                        ->maxLength(60),
                    TextInput::make('phone')
                        ->label(__('app.phone_number'))
                        ->tel()
                        ->required()
                        ->maxLength(20)
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->placeholder(__('app.phone_placeholder'))
                        ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            if (! BahrainPhone::isValid((string) $value)) {
                                $fail(__('app.invalid_phone'));
                            }
                        }),
                ])
                ->action(function (array $data, IssueLoyaltyCard $issueLoyaltyCard): void {
                    $this->registerCustomer($data['name'], $data['phone'], $issueLoyaltyCard);
                }),
        ];
    }

    public function registerCustomer(string $name, string $phone, IssueLoyaltyCard $issueLoyaltyCard): void
    {
        $program = $this->merchant()->activeProgram();

        if ($program === null) {
            Notification::make()
                ->title(__('app.no_active_program'))
                ->body(__('app.owner_must_activate'))
                ->danger()
                ->send();

            return;
        }

        $card = $issueLoyaltyCard->handle($program, $name, $phone);

        $this->search = '';
        $this->cardId = $card->id;
        $this->showCardLink = true;
        unset($this->card);

        Notification::make()
            ->title($card->wasRecentlyCreated ? __('app.customer_registered') : __('app.customer_already_registered'))
            ->body(__('app.ask_customer_scan'))
            ->success()
            ->send();
    }

    public function toggleCardLink(): void
    {
        $this->showCardLink = ! $this->showCardLink;
    }

    public function lookup(): void
    {
        $this->cardId = null;
        $term = trim($this->search);

        if ($term === '') {
            return;
        }

        $card = $this->merchant()->loyaltyCards()
            ->where('code', strtoupper($term))
            ->first();

        if ($card === null && BahrainPhone::isValid($term)) {
            $card = $this->merchant()->loyaltyCards()
                ->whereRelation('customer', 'phone', BahrainPhone::normalize($term))
                ->latest('id')
                ->first();
        }

        if ($card === null) {
            Notification::make()->title(__('app.card_not_found'))->danger()->send();

            return;
        }

        $this->cardId = $card->id;
        $this->showCardLink = false;
        unset($this->card);
    }

    public function lookupScanned(string $code): void
    {
        $this->search = $code;
        $this->lookup();
    }

    public function addStamp(AddStamp $addStamp, int $quantity = 1): void
    {
        $card = $this->card;

        if ($card === null) {
            return;
        }

        $rewardsBefore = $card->rewards_available;
        $card = $addStamp->handle($card, $this->staff(), $quantity);
        unset($this->card);

        if ($card->rewards_available > $rewardsBefore) {
            Notification::make()->title(__('app.reward_earned'))->success()->send();

            return;
        }

        Notification::make()
            ->title($quantity === 1 ? __('app.stamp_added') : __('app.stamps_added_many', ['count' => $quantity]))
            ->success()
            ->send();
    }

    public function redeem(RedeemReward $redeemReward): void
    {
        $card = $this->card;

        if ($card === null) {
            return;
        }

        try {
            $redeemReward->handle($card, $this->staff());
        } catch (NoRewardAvailable $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();

            return;
        }

        unset($this->card);
        Notification::make()->title(__('app.reward_redeemed'))->success()->send();
    }

    public function clear(): void
    {
        $this->reset('search', 'cardId', 'showCardLink');
    }

    /**
     * @return array{url: string, qrCode: string, whatsappUrl: string}|null
     */
    #[Computed]
    public function cardLink(): ?array
    {
        $card = $this->card;

        if ($card === null) {
            return null;
        }

        $url = route('cards.show', $card);
        $message = __('app.customer_whatsapp', [
            'name' => $card->customer->name,
            'shop' => $this->merchant()->name,
            'url' => $url,
        ]);

        return [
            'url' => $url,
            'qrCode' => (new QRCode)->render($url),
            'whatsappUrl' => 'https://wa.me/'.$card->customer->phone.'?text='.rawurlencode($message),
        ];
    }

    #[Computed]
    public function card(): ?LoyaltyCard
    {
        if ($this->cardId === null) {
            return null;
        }

        return $this->merchant()->loyaltyCards()
            ->with(['customer', 'loyaltyProgram'])
            ->find($this->cardId);
    }

    protected function merchant(): Merchant
    {
        /** @var Merchant */
        return Filament::getTenant();
    }

    protected function staff(): User
    {
        /** @var User */
        return Filament::auth()->user();
    }
}
