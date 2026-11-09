<x-filament-panels::page>
    <x-filament::section>
        <form wire:submit="lookup" style="display:flex; gap:.75rem; flex-wrap:wrap; align-items:flex-end;">
            <div style="flex:1; min-width:14rem;">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model="search"
                        placeholder="{{ __('app.card_or_phone_placeholder') }}"
                        autofocus
                        autocomplete="off"
                    />
                </x-filament::input.wrapper>
            </div>
            <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">{{ __('app.search') }}</x-filament::button>
        </form>

        <div
            x-data="cardScanner"
            style="margin-top:1rem;"
            wire:ignore
        >
            <x-filament::button color="gray" icon="heroicon-o-camera" x-on:click="toggle" x-text="running ? '{{ __('app.stop_camera') }}' : '{{ __('app.scan_with_camera') }}'">
                {{ __('app.scan_with_camera') }}
            </x-filament::button>
            <div id="card-scanner" style="max-width:22rem; margin-top:.75rem;"></div>
            <p x-show="error" x-text="error" style="color:#dc2626; margin-top:.5rem;"></p>
        </div>
    </x-filament::section>

    @if ($this->card)
        @php($card = $this->card)
        @php($program = $card->loyaltyProgram)
        <x-filament::section>
            <x-slot name="heading">{{ $card->customer->name }}</x-slot>
            <x-slot name="description">{{ $program->name }} · {{ $card->code }}</x-slot>

            <div style="display:grid; grid-template-columns:repeat(5, 2.75rem); gap:.5rem; margin-bottom:1rem;">
                @for ($i = 0; $i < $program->stamps_required; $i++)
                    <div style="width:2.75rem; height:2.75rem; border-radius:9999px; display:flex; align-items:center; justify-content:center; font-weight:700;
                        {{ $i < $card->stamps ? 'background:'.$program->background_color.'; color:'.$program->foreground_color.';' : 'border:2px dashed #9ca3af; color:#9ca3af;' }}">
                        {{ $i < $card->stamps ? '✓' : $i + 1 }}
                    </div>
                @endfor
            </div>

            <p style="margin-bottom:1rem;">
                {{ __('app.stamps_label') }}: <strong>{{ $card->stamps }} / {{ $program->stamps_required }}</strong>
                · {{ __('app.rewards_available_label') }}: <strong>{{ $card->rewards_available }}</strong>
                · {{ __('app.reward_label') }}: {{ $program->reward_label }}
            </p>

            <div style="display:flex; gap:.75rem; flex-wrap:wrap;">
                <x-filament::button size="xl" icon="heroicon-o-plus-circle" wire:click="addStamp(1)">{{ __('app.add_stamp') }}</x-filament::button>
                <x-filament::button size="xl" color="gray" wire:click="addStamp(2)">+2</x-filament::button>
                <x-filament::button size="xl" color="success" icon="heroicon-o-gift" wire:click="redeem" :disabled="$card->rewards_available < 1">
                    {{ __('app.redeem_reward') }}
                </x-filament::button>
                <x-filament::button size="xl" color="gray" outlined icon="heroicon-o-device-phone-mobile" wire:click="toggleCardLink">
                    {{ __('app.card_link') }}
                </x-filament::button>
                <x-filament::button size="xl" color="gray" outlined wire:click="clear">{{ __('app.finish') }}</x-filament::button>
            </div>
        </x-filament::section>

        @if ($showCardLink && $this->cardLink)
            @php($link = $this->cardLink)
            <x-filament::section>
                <x-slot name="heading">{{ __('app.add_card_to_phone') }}</x-slot>
                <x-slot name="description">{{ __('app.ask_customer_camera_or_whatsapp') }}</x-slot>

                <div style="display:flex; gap:1.5rem; align-items:center; flex-wrap:wrap;">
                    <img src="{{ $link['qrCode'] }}" alt="QR" style="width:14rem; height:14rem; background:#fff; padding:.5rem; border-radius:.75rem;">
                    <div style="display:flex; flex-direction:column; gap:.75rem;">
                        <x-filament::button tag="a" :href="$link['whatsappUrl']" target="_blank" color="success" icon="heroicon-o-chat-bubble-left-right">
                            {{ __('app.send_whatsapp') }}
                        </x-filament::button>
                        <x-filament::button tag="a" :href="$link['url']" target="_blank" color="gray" icon="heroicon-o-arrow-top-right-on-square">
                            {{ __('app.open_link') }}
                        </x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        @endif
    @endif

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
