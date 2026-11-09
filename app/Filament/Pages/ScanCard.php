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

    protected static ?string $navigationLabel = 'مسح البطاقة';

    protected static ?string $title = 'مسح البطاقة';

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
                ->label('عميل جديد')
                ->icon(Heroicon::OutlinedUserPlus)
                ->modalHeading('تسجيل عميل جديد')
                ->modalSubmitActionLabel('سجّل وأعطه البطاقة')
                ->schema([
                    TextInput::make('name')
                        ->label('اسم العميل')
                        ->required()
                        ->maxLength(60),
                    TextInput::make('phone')
                        ->label('رقم الجوال')
                        ->tel()
                        ->required()
                        ->maxLength(20)
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->placeholder('3XXX XXXX')
                        ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            if (! BahrainPhone::isValid((string) $value)) {
                                $fail('رقم الجوال غير صحيح. اكتب رقم بحريني من 8 أرقام.');
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
                ->title('ما فيه بطاقة ولاء مفعّلة')
                ->body('صاحب المحل لازم يفعّل بطاقة من صفحة "بطاقات الولاء".')
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
            ->title($card->wasRecentlyCreated ? 'تم تسجيل العميل' : 'العميل مسجّل من قبل')
            ->body('خلّ العميل يمسح الكود بكاميرا جواله ويضيف البطاقة للمحفظة.')
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
            Notification::make()->title('ما لقينا بطاقة بهذا الرقم')->danger()->send();

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
            Notification::make()->title('🎉 العميل حصل على مكافأة!')->success()->send();

            return;
        }

        Notification::make()
            ->title($quantity === 1 ? 'تمت إضافة ختم' : "تمت إضافة {$quantity} أختام")
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
        Notification::make()->title('تم صرف المكافأة')->success()->send();
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
        $message = "أهلاً {$card->customer->name}، هذي بطاقة الولاء حقتك في {$this->merchant()->name}. افتح الرابط وأضفها للمحفظة: {$url}";

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
