<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5 lg:items-start">

        {{-- Left: find the card (search or camera). Stays in view on large screens. --}}
        <div class="min-w-0 space-y-6 lg:col-span-2 lg:sticky lg:top-24">
            <x-filament::section>
                <x-slot name="heading">{{ __('app.find_a_card') }}</x-slot>
                <x-slot name="description">{{ __('app.find_a_card_help') }}</x-slot>

                <form wire:submit="lookup" class="flex flex-col gap-3">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            wire:model="search"
                            placeholder="{{ __('app.card_or_phone_placeholder') }}"
                            autofocus
                            autocomplete="off"
                            inputmode="search"
                        />
                    </x-filament::input.wrapper>
                    <x-filament::button type="submit" size="lg" icon="heroicon-o-magnifying-glass" class="w-full">{{ __('app.search') }}</x-filament::button>
                </form>

                <div x-data="cardScanner" class="mt-5 border-t border-ink/10 pt-5 dark:border-white/10" wire:ignore>
                    <x-filament::button color="gray" size="lg" icon="heroicon-o-camera" class="w-full" x-on:click="toggle" x-text="running ? '{{ __('app.stop_camera') }}' : '{{ __('app.scan_with_camera') }}'">
                        {{ __('app.scan_with_camera') }}
                    </x-filament::button>
                    <div id="card-scanner" class="mx-auto mt-3 w-full max-w-sm overflow-hidden rounded-xl"></div>
                    <p x-show="error" x-text="error" role="alert" class="mt-2 text-sm font-medium text-danger dark:text-red-400"></p>
                </div>
            </x-filament::section>
        </div>

        {{-- Right: the open card, or the recent stamps when no card is open. --}}
        <div class="min-w-0 space-y-6 lg:col-span-3">
            @if ($this->card)
                @php($card = $this->card)
                @php($program = $card->loyaltyProgram)
                @php($fill = $program->foreground_color)
                @php($fillText = \App\Support\Contrast::readable($program->background_color, $fill))
                <x-filament::section>
                    <x-slot name="heading">{{ $card->customer->name }}</x-slot>
                    <x-slot name="description">{{ $program->name }} · {{ $card->code }}</x-slot>

                    <div class="grid grid-cols-5 gap-2 sm:gap-3" role="list" aria-label="{{ __('app.stamps_label') }}">
                        @for ($i = 0; $i < $program->stamps_required; $i++)
                            @php($filled = $i < $card->stamps)
                            <div role="listitem"
                                class="flex aspect-square items-center justify-center rounded-full border-2 text-sm font-bold sm:text-base {{ $filled ? 'border-transparent' : 'border-dashed border-stone-500 text-stone-700 dark:border-gray-400 dark:text-gray-300' }}"
                                style="{{ $filled ? 'background: '.$fill.'; color: '.$fillText.';' : '' }}">
                                <span aria-hidden="true">{{ $filled ? '✓' : $i + 1 }}</span>
                                <span class="sr-only">{{ $filled ? __('app.stamp_collected') : __('app.stamp_empty') }}</span>
                            </div>
                        @endfor
                    </div>

                    <dl class="mt-5 grid grid-cols-3 gap-3 rounded-xl bg-stone-50 p-4 text-center ring-1 ring-ink/10 dark:bg-white/5 dark:ring-white/10">
                        <div class="min-w-0">
                            <dt class="text-xs font-semibold text-stone-700 dark:text-gray-300">{{ __('app.stamps_label') }}</dt>
                            <dd class="mt-1 text-lg font-bold tabular-nums">{{ $card->stamps }} / {{ $program->stamps_required }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-xs font-semibold text-stone-700 dark:text-gray-300">{{ __('app.rewards_available_label') }}</dt>
                            <dd class="mt-1 text-lg font-bold tabular-nums">{{ $card->rewards_available }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-xs font-semibold text-stone-700 dark:text-gray-300">{{ __('app.reward_label') }}</dt>
                            <dd class="mt-1 text-sm font-semibold break-words">{{ $program->reward_label }}</dd>
                        </div>
                    </dl>

                    {{-- The main action is the full-width button; the rest sit in one row below it. --}}
                    <div class="mt-5 space-y-3">
                        <x-filament::button size="xl" icon="heroicon-o-plus-circle" class="w-full" wire:click="addStamp(1)">{{ __('app.add_stamp') }}</x-filament::button>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <x-filament::button size="lg" color="gray" outlined class="w-full" wire:click="addStamp(2)">+2</x-filament::button>
                            <x-filament::button size="lg" color="success" icon="heroicon-o-gift" class="w-full" wire:click="redeem" :disabled="$card->rewards_available < 1">
                                {{ __('app.redeem_reward') }}
                            </x-filament::button>
                            <x-filament::button size="lg" color="gray" outlined icon="heroicon-o-device-phone-mobile" class="w-full" wire:click="toggleCardLink">
                                {{ __('app.card_link') }}
                            </x-filament::button>
                            <x-filament::button size="lg" color="gray" outlined class="w-full" wire:click="clear">{{ __('app.finish') }}</x-filament::button>
                        </div>
                    </div>
                </x-filament::section>

                @if ($showCardLink && $this->cardLink)
                    @php($link = $this->cardLink)
                    <x-filament::section>
                        <x-slot name="heading">{{ __('app.add_card_to_phone') }}</x-slot>
                        <x-slot name="description">{{ __('app.ask_customer_camera_or_whatsapp') }}</x-slot>

                        <div class="flex flex-col items-center gap-6 sm:flex-row sm:items-start">
                            <img src="{{ $link['qrCode'] }}" alt="{{ __('app.card_link') }}" class="size-56 shrink-0 rounded-xl bg-white p-2 ring-1 ring-ink/10">
                            <div class="flex w-full flex-col gap-3 sm:w-auto">
                                <x-filament::button tag="a" :href="$link['whatsappUrl']" target="_blank" rel="noopener noreferrer" color="success" icon="heroicon-o-chat-bubble-left-right" class="w-full sm:w-auto">
                                    {{ __('app.send_whatsapp') }}
                                </x-filament::button>
                                <x-filament::button tag="a" :href="$link['url']" target="_blank" rel="noopener noreferrer" color="gray" icon="heroicon-o-arrow-top-right-on-square" class="w-full sm:w-auto">
                                    {{ __('app.open_link') }}
                                </x-filament::button>
                            </div>
                        </div>
                    </x-filament::section>
                @endif
            @else
                <x-filament::section>
                    <x-slot name="heading">{{ __('app.recent_stamps') }}</x-slot>
                    <x-slot name="description">{{ __('app.recent_stamps_help') }}</x-slot>

                    @if ($this->recentActivities->isEmpty())
                        <div class="flex flex-col items-center gap-2 py-10 text-center">
                            <span class="flex size-12 items-center justify-center rounded-full bg-brand/40 text-ink" aria-hidden="true">
                                <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3M7 12h10"/></svg>
                            </span>
                            <p class="font-bold">{{ __('app.no_activity_yet') }}</p>
                            <p class="text-sm text-ink-soft">{{ __('app.no_activity_hint') }}</p>
                        </div>
                    @else
                        <ul class="divide-y divide-ink/10 dark:divide-white/10">
                            @foreach ($this->recentActivities as $activity)
                                <li class="flex items-center justify-between gap-3 py-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-bold">{{ $activity->loyaltyCard?->customer?->name ?? __('app.walk_in') }}</p>
                                        <p class="text-xs text-ink-soft">{{ $activity->created_at->diffForHumans() }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-bold {{ $activity->type === \App\Enums\CardActivityType::Stamp ? 'bg-teal/40 text-ink' : 'bg-brand/50 text-ink' }}">
                                        {{ $activity->type->label() }}{{ $activity->type === \App\Enums\CardActivityType::Stamp ? ' +'.$activity->quantity : '' }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-filament::section>
            @endif
        </div>
    </div>

    @script
    <script>
        Alpine.data('cardScanner', () => ({
            running: false,
            error: null,
            scanner: null,
            async toggle() {
                this.running ? await this.stop() : await this.start();
            },
            async start() {
                this.error = null;
                if (! window.Html5Qrcode) {
                    await new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js';
                        script.onload = resolve;
                        script.onerror = reject;
                        document.head.appendChild(script);
                    }).catch(() => this.error = '{{ __('app.scanner_load_failed') }}');
                }
                if (! window.Html5Qrcode) return;
                this.scanner = new Html5Qrcode('card-scanner');
                try {
                    await this.scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: 220 }, async (text) => {
                        await this.stop();
                        $wire.lookupScanned(text);
                    });
                    this.running = true;
                } catch (e) {
                    this.error = '{{ __('app.camera_failed') }}';
                }
            },
            async stop() {
                if (this.scanner && this.running) await this.scanner.stop();
                this.running = false;
            },
        }));
    </script>
    @endscript
</x-filament-panels::page>
