<x-filament-widgets::widget class="fi-t3al-quick">
    <nav aria-label="{{ __('app.quick_actions') }}" class="grid grid-cols-2 gap-3 md:grid-cols-[repeat(auto-fit,minmax(11rem,1fr))]">
        @foreach ($actions as $action)
            <a href="{{ $action['url'] }}"
               class="last:odd:col-span-2 md:last:odd:col-span-1 flex min-h-24 flex-col items-start justify-between gap-3 rounded-[1.25rem] bg-white p-5 text-start shadow-sm ring-1 ring-ink/10 transition hover:ring-brand motion-safe:hover:-translate-y-0.5 dark:bg-gray-900 dark:ring-white/10">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand/40 text-ink" aria-hidden="true">
                    @switch($action['icon'])
                        @case('scan')
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3M7 12h10"/></svg>
                            @break
                        @case('card')
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/></svg>
                            @break
                        @case('orders')
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2z M9 8h6M9 12h6"/></svg>
                            @break
                        @default
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>
                    @endswitch
                </span>
                <span class="text-base font-bold">{{ $action['label'] }}</span>
            </a>
        @endforeach
    </nav>
</x-filament-widgets::widget>
