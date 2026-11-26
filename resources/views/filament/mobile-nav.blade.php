@php($shop = \Filament\Facades\Filament::getTenant())
@if ($shop)
    @php($base = url('/app/'.$shop->slug))
    @php($current = url()->current())
    <nav aria-label="{{ __('app.main_menu') }}" class="fixed inset-x-0 bottom-0 z-40 border-t border-ink/10 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden dark:bg-gray-900/95">
        <ul class="mx-auto grid max-w-md grid-cols-4 items-end px-2 pt-1 text-xs font-semibold">
            @php($items = [
                ['url' => $base, 'label' => __('app.nav_home'), 'icon' => 'home'],
                ['url' => $base.'/loyalty-cards', 'label' => __('app.action_cards'), 'icon' => 'card'],
                ['url' => $base.'/scan-card', 'label' => __('app.action_scan'), 'icon' => 'scan', 'center' => true],
                ['url' => url('/app/profile'), 'label' => __('app.nav_profile'), 'icon' => 'user'],
            ])
            @foreach ($items as $item)
                @php($active = $current === $item['url'] || str_starts_with($current, $item['url'].'/') && ($item['url'] !== $base))
                <li class="flex justify-center">
                    <a href="{{ $item['url'] }}" @if ($active) aria-current="page" @endif
                       class="flex min-h-14 min-w-14 flex-col items-center justify-center gap-1 rounded-2xl px-2 {{ $active ? 'text-ink' : 'text-ink-soft' }}">
                        @if (! empty($item['center']))
                            <span class="-mt-7 flex size-14 items-center justify-center rounded-full bg-brand text-ink shadow-lg ring-4 ring-white dark:ring-gray-900" aria-hidden="true">
                                <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3M7 12h10"/></svg>
                            </span>
                        @else
                            <span class="flex size-9 items-center justify-center rounded-xl {{ $active ? 'bg-brand/50' : '' }}" aria-hidden="true">
                                @if ($item['icon'] === 'home')
                                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z"/></svg>
                                @elseif ($item['icon'] === 'card')
                                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                                @endif
                            </span>
                        @endif
                        <span class="{{ ! empty($item['center']) ? 'mt-1' : '' }}">{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
