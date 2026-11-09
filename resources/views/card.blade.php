@php
    $bg = $program->background_color;
    $fg = $program->foreground_color;
    $text = \App\Support\Contrast::readable($fg, $bg);
    $label = \App\Support\Contrast::readable($program->label_color, $bg);
    $outline = \App\Support\Contrast::readable($program->label_color, $bg, 3.0);
    $fillText = \App\Support\Contrast::readable($bg, $fg);
@endphp
<x-public-layout :title="$program->name">
    <section aria-labelledby="card-title" class="overflow-hidden rounded-3xl p-6 shadow-xl ring-1 ring-ink/10 sm:p-7" style="background: {{ $bg }}; color: {{ $text }};">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold" style="color: {{ $label }};">{{ $merchant->name }}</p>
                <h1 id="card-title" class="mt-1 text-2xl leading-tight font-bold break-words">{{ $card->customer->name }}</h1>
            </div>
            <div class="shrink-0 text-end">
                <p class="text-xs font-semibold" style="color: {{ $label }};">{{ __('app.rewards') }}</p>
                <p class="text-3xl leading-none font-black tabular-nums">{{ $card->rewards_available }}</p>
            </div>
        </div>

        <ol class="mt-6 grid grid-cols-5 gap-2.5" aria-label="{{ __('app.stamps_label') }}">
            @for ($i = 0; $i < $program->stamps_required; $i++)
                @php($filled = $i < $card->stamps)
                <li class="flex aspect-square items-center justify-center rounded-full border-2 text-base font-bold"
                    style="border-color: {{ $outline }}; {{ $filled ? 'background: '.$fg.'; color: '.$fillText.';' : '' }}">
                    <span aria-hidden="true">{{ $filled ? '✓' : $i + 1 }}</span>
                    <span class="sr-only">{{ $filled ? __('app.stamp_collected') : __('app.stamp_empty') }}</span>
                </li>
            @endfor
        </ol>

        <p class="mt-5 text-sm leading-relaxed" style="color: {{ $label }};">
            {{ __('app.collect_stamps', ['stamps' => $program->stamps_required, 'reward' => $program->reward_label]) }}
        </p>
    </section>

    <div class="mt-6 grid gap-3">
        @if ($appleWalletEnabled)
            <a href="{{ route('cards.apple', $card) }}" class="inline-flex min-h-14 items-center justify-center rounded-2xl bg-black px-5 text-center text-base font-bold text-white motion-safe:transition-transform motion-safe:hover:-translate-y-0.5">
                {{ __('app.add_to_apple') }}
            </a>
        @endif
        @if ($googleWalletEnabled)
            <a href="{{ route('cards.google', $card) }}" class="inline-flex min-h-14 items-center justify-center rounded-2xl border-2 border-ink bg-white px-5 text-center text-base font-bold text-ink motion-safe:transition-transform motion-safe:hover:-translate-y-0.5">
                {{ __('app.add_to_google') }}
            </a>
        @endif

        @unless ($appleWalletEnabled || $googleWalletEnabled)
            <p role="status" class="rounded-2xl bg-brand/20 p-4 text-center text-sm font-medium text-ink">{{ __('app.wallet_unavailable') }}</p>
        @endunless
    </div>

    <p class="mt-8 text-center text-sm leading-relaxed text-ink-soft">
        {{ __('app.after_adding_show_cashier') }}
    </p>
</x-public-layout>
