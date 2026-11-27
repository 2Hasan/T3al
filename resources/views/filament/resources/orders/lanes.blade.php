<x-filament-panels::page>
    @php($lanes = $this->lanes)

    <section class="rounded-[1.25rem] bg-white p-5 shadow-sm ring-1 ring-ink/10 dark:bg-gray-900 dark:ring-white/10" aria-labelledby="new-order">
        <h2 id="new-order" class="font-bold">{{ __('app.order_new_heading') }}</h2>
        <form wire:submit="submitOrder" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-[8rem_1fr_1fr_auto] md:items-end">
            <label class="block">
                <span class="mb-1 block text-sm font-semibold">{{ __('app.order_number') }}</span>
                <x-filament::input.wrapper>
                    <x-filament::input type="number" inputmode="numeric" min="1" max="9999" wire:model="orderNumber" required />
                </x-filament::input.wrapper>
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-semibold">{{ __('app.order_lookup') }}</span>
                <x-filament::input.wrapper>
                    <x-filament::input type="text" autocomplete="off" wire:model="orderLookup" required />
                </x-filament::input.wrapper>
            </label>
            <label class="block">
                <span class="mb-1 block text-sm font-semibold">{{ __('app.order_note') }}</span>
                <x-filament::input.wrapper>
                    <x-filament::input type="text" autocomplete="off" maxlength="200" wire:model="orderNote" />
                </x-filament::input.wrapper>
            </label>
            <x-filament::button type="submit" size="lg" class="w-full md:w-auto">{{ __('app.order_save') }}</x-filament::button>
        </form>
    </section>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-start">
        @foreach ([
            ['key' => 'preparing', 'title' => __('app.order_lane_preparing'), 'empty' => __('app.order_lane_preparing_empty'), 'tone' => 'bg-brand/40'],
            ['key' => 'ready', 'title' => __('app.order_lane_ready'), 'empty' => __('app.order_lane_ready_empty'), 'tone' => 'bg-teal/40'],
            ['key' => 'collected', 'title' => __('app.order_lane_collected'), 'empty' => __('app.order_lane_collected_empty'), 'tone' => 'bg-stone-200 dark:bg-white/10'],
        ] as $lane)
            @php($items = $lanes[$lane['key']])
            <section class="min-w-0 rounded-[1.25rem] bg-white p-4 shadow-sm ring-1 ring-ink/10 dark:bg-gray-900 dark:ring-white/10" aria-labelledby="lane-{{ $lane['key'] }}">
                <header class="mb-4 flex items-center justify-between gap-3">
                    <h2 id="lane-{{ $lane['key'] }}" class="font-bold">{{ $lane['title'] }}</h2>
                    <span class="rounded-full px-3 py-1 text-xs font-bold tabular-nums {{ $lane['tone'] }}">{{ $items->count() }}</span>
                </header>

                @if ($items->isEmpty())
                    <p class="flex min-h-40 items-center justify-center rounded-xl bg-cream/60 px-4 text-center text-sm text-ink-soft dark:bg-white/5">{{ $lane['empty'] }}</p>
                @else
                    <ul class="space-y-3">
                        @foreach ($items as $order)
                            <li class="rounded-2xl border border-ink/10 p-4 dark:border-white/10">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-2xl font-black tabular-nums">#{{ $order->number }}</p>
                                        <p class="mt-1 truncate font-semibold">{{ $order->loyaltyCard?->customer?->name ?? __('app.walk_in') }}</p>
                                        @if ($order->note)
                                            <p class="mt-1 text-sm text-ink-soft break-words">{{ $order->note }}</p>
                                        @endif
                                    </div>
                                    <time class="shrink-0 text-xs text-ink-soft" datetime="{{ $order->created_at->toIso8601String() }}">
                                        {{ $order->created_at->diffForHumans() }}
                                    </time>
                                </div>

                                @if ($lane['key'] === 'preparing')
                                    <x-filament::button size="lg" color="success" class="mt-4 w-full" wire:click="markReady({{ $order->id }})">
                                        {{ __('app.order_mark_ready') }}
                                    </x-filament::button>
                                @elseif ($lane['key'] === 'ready')
                                    <x-filament::button size="lg" class="mt-4 w-full" wire:click="markCollected({{ $order->id }})">
                                        {{ __('app.order_mark_collected') }}
                                    </x-filament::button>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
