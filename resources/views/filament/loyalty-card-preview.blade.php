@php
    $shown = min($stamps, 10);
@endphp
<div class="space-y-3">
    <p class="text-xs font-semibold text-ink-soft">{{ __('app.card_preview_hint') }}</p>

    <div class="mx-auto max-w-sm rounded-[24px] p-6 shadow-xl ring-1 ring-black/10"
         style="background-color: {{ $background }}; color: {{ $foreground }};">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-semibold tracking-widest uppercase" style="color: {{ $label }};">{{ __('app.card_preview_stamps', ['count' => $stamps]) }}</p>
                <p class="mt-1 truncate text-lg font-black">{{ $name }}</p>
            </div>
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl text-lg font-black" style="background-color: {{ $foreground }}; color: {{ $background }};" aria-hidden="true">ت</span>
        </div>

        <div class="mt-6 grid grid-cols-5 gap-2.5">
            @for ($i = 1; $i <= $shown; $i++)
                <div class="flex aspect-square items-center justify-center rounded-full border-2 text-xs font-bold"
                     style="{{ $i <= 3 ? 'background-color: '.$foreground.'; border-color: '.$foreground.'; color: '.$background.';' : 'border-color: '.$label.'; color: '.$label.'; border-style: dashed;' }}">
                    @if ($i <= 3)
                        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4.5 4.5L19 7"/></svg>
                    @else
                        {{ $i }}
                    @endif
                </div>
            @endfor
        </div>

        <div class="mt-5 flex items-center justify-between gap-3 rounded-2xl px-4 py-3" style="background-color: rgb(255 255 255 / 0.08);">
            <span class="text-sm" style="color: {{ $label }};">{{ $reward }}</span>
            <span class="rounded-full px-3 py-1 text-xs font-bold" style="background-color: {{ $foreground }}; color: {{ $background }};">3 / {{ $stamps }}</span>
        </div>

        @if ($terms !== '')
            <p class="mt-5 text-xs leading-relaxed" style="color: {{ $label }};">{{ $terms }}</p>
        @endif
    </div>
</div>
