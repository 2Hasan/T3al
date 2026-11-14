<x-filament-widgets::widget class="fi-t3al-hero">
    <div class="relative isolate overflow-hidden rounded-[1.25rem] bg-ink px-6 py-7 text-cream shadow-[0_18px_40px_-24px_rgb(28_25_23/0.6)] sm:px-8 sm:py-8">
        <div class="pointer-events-none absolute -end-16 -top-16 size-64 rounded-full bg-brand/25 blur-3xl" aria-hidden="true"></div>

        <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                @if ($label !== '')
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand">{{ $label }}</p>
                @endif
                <h2 class="mt-1 text-2xl font-black tracking-tight sm:text-3xl">{{ $title }}</h2>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-cream/75">{{ $subtitle }}</p>
            </div>

            <span class="hidden size-14 shrink-0 items-center justify-center rounded-2xl bg-brand text-2xl font-black text-ink sm:flex" aria-hidden="true">ت</span>
        </div>
    </div>
</x-filament-widgets::widget>
