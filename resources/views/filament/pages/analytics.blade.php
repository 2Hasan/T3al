@php($f = $this->figures())
<x-filament-panels::page>
    <p class="text-sm text-ink-soft">{{ __('app.analytics_window', ['days' => \App\Support\ShopAnalytics::WINDOW_DAYS]) }}</p>

    <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['label' => __('app.analytics_stamps'), 'value' => $f['stamps']],
            ['label' => __('app.analytics_visits'), 'value' => $f['visits']],
            ['label' => __('app.analytics_new_customers'), 'value' => $f['new_customers']],
            ['label' => __('app.analytics_active_customers'), 'value' => $f['active_customers']],
            ['label' => __('app.analytics_redemptions'), 'value' => $f['redemptions']],
            ['label' => __('app.analytics_returning_rate'), 'value' => $f['returning_rate'].'%'],
        ] as $tile)
            <div class="rounded-[1.25rem] bg-white p-5 shadow-sm ring-1 ring-ink/10 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-sm text-ink-soft">{{ $tile['label'] }}</p>
                <p class="mt-2 text-3xl font-black tracking-tight">{{ $tile['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        @foreach ([
            ['title' => __('app.analytics_daily'), 'rows' => $f['daily']],
            ['title' => __('app.analytics_weekdays'), 'rows' => $f['weekdays']],
        ] as $chart)
            @php($max = max(1, max(array_column($chart['rows'], 'count'))))
            <div class="rounded-[1.25rem] bg-white p-5 shadow-sm ring-1 ring-ink/10 dark:bg-gray-900 dark:ring-white/10">
                <h2 class="font-bold">{{ $chart['title'] }}</h2>
                <div class="mt-5 flex h-48 items-end gap-2" role="img" aria-label="{{ $chart['title'] }}">
                    @foreach ($chart['rows'] as $row)
                        <div class="flex h-full min-w-0 flex-1 flex-col justify-end gap-1">
                            <span class="text-center text-[0.65rem] font-semibold text-ink-soft">{{ $row['count'] }}</span>
                            <div class="w-full rounded-t-lg bg-brand/80"
                                 style="height: {{ max(3, round($row['count'] / $max * 100)) }}%;"></div>
                            <span class="truncate text-center text-[0.65rem] text-ink-soft">{{ $row['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
