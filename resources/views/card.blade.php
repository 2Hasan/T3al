<x-public-layout :title="$program->name">
    <div class="rounded-3xl p-6 shadow-lg" style="background: {{ $program->background_color }}; color: {{ $program->foreground_color }};">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm" style="color: {{ $program->label_color }};">{{ $merchant->name }}</p>
                <h1 class="mt-1 text-xl font-bold">{{ $card->customer->name }}</h1>
            </div>
            <div class="text-left">
                <p class="text-xs" style="color: {{ $program->label_color }};">{{ __('app.rewards') }}</p>
                <p class="text-2xl font-bold">{{ $card->rewards_available }}</p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-5 gap-3">
            @for ($i = 0; $i < $program->stamps_required; $i++)
                <div class="flex aspect-square items-center justify-center rounded-full border-2 text-lg font-bold"
                    style="border-color: {{ $program->label_color }}; {{ $i < $card->stamps ? 'background:'.$program->foreground_color.'; color:'.$program->background_color.';' : '' }}">
                    {{ $i < $card->stamps ? '✓' : '' }}
                </div>
            @endfor
        </div>

        <p class="mt-4 text-sm" style="color: {{ $program->label_color }};">
            {{ __('app.collect_stamps', ['stamps' => $program->stamps_required, 'reward' => $program->reward_label]) }}
        </p>
    </div>

    <div class="mt-6 space-y-3">
        @if ($appleWalletEnabled)
            <a href="{{ route('cards.apple', $card) }}" class="block w-full rounded-xl bg-black px-4 py-4 text-center text-lg font-bold text-white">
                {{ __('app.add_to_apple') }}
            </a>
        @endif
        @if ($googleWalletEnabled)
            <a href="{{ route('cards.google', $card) }}" class="block w-full rounded-xl border-2 border-stone-900 bg-white px-4 py-4 text-center text-lg font-bold text-stone-900">
                {{ __('app.add_to_google') }}
            </a>
        @endif

        @unless ($appleWalletEnabled || $googleWalletEnabled)
            <p class="rounded-xl bg-amber-50 p-4 text-center text-sm text-amber-800">{{ __('app.wallet_unavailable') }}</p>
        @endunless
    </div>

    <p class="mt-8 text-center text-sm text-stone-600">
        {{ __('app.after_adding_show_cashier') }}
    </p>
</x-public-layout>
