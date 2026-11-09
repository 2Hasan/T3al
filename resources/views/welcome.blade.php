<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#FAF7F2">
        <title>{{ __('app.tagline') }} · T3al</title>
        <meta name="description" content="{{ __('app.description') }}">
        <x-font-links />
        @vite(['resources/css/app.css'])
        <script>document.documentElement.classList.add('js');</script>
    </head>
    <body class="min-h-dvh bg-cream text-ink antialiased">
        <a href="#main" class="skip-link">{{ __('app.skip_to_content') }}</a>

        <header class="sticky top-0 z-20 border-b border-ink/10 bg-cream/90 backdrop-blur-md">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6">
                <a href="{{ route('home') }}" class="flex min-h-11 items-center gap-2.5 rounded-xl">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-brand text-lg font-black text-ink" aria-hidden="true">ت</span>
                    <span class="text-xl font-extrabold tracking-tight">T3al</span>
                </a>

                <nav class="hidden items-center gap-1 text-sm font-medium text-ink-soft md:flex">
                    <a href="#how" class="inline-flex min-h-11 items-center rounded-full px-4 hover:bg-ink/5 hover:text-ink">{{ __('app.nav_how') }}</a>
                    <a href="#features" class="inline-flex min-h-11 items-center rounded-full px-4 hover:bg-ink/5 hover:text-ink">{{ __('app.nav_features') }}</a>
                    <a href="#pricing" class="inline-flex min-h-11 items-center rounded-full px-4 hover:bg-ink/5 hover:text-ink">{{ __('app.nav_pricing') }}</a>
                </nav>

                <div class="flex items-center gap-2">
                    @if (app()->getLocale() === 'ar')
                        <a href="{{ route('locale.switch', 'en') }}" lang="en" class="inline-flex min-h-11 items-center rounded-full px-3 text-sm font-semibold text-ink-soft hover:bg-ink/5 hover:text-ink">English</a>
                    @else
                        <a href="{{ route('locale.switch', 'ar') }}" lang="ar" class="inline-flex min-h-11 items-center rounded-full px-3 text-sm font-semibold text-ink-soft hover:bg-ink/5 hover:text-ink">العربية</a>
                    @endif
                    <a href="{{ url('/app') }}" class="hidden min-h-11 items-center rounded-full bg-ink px-5 text-sm font-semibold text-cream hover:bg-ink/85 sm:inline-flex">{{ __('app.nav_start') }}</a>
                </div>
            </div>
        </header>

        <main id="main" class="overflow-x-clip">
            {{-- Hero --}}
            <section class="mx-auto grid max-w-6xl items-center gap-12 px-4 pt-12 pb-20 sm:px-6 md:grid-cols-2 md:pt-20 lg:pt-24">
                <div class="reveal text-center md:text-start">
                    <p class="inline-flex max-w-full items-center gap-2 rounded-full border border-ink/10 bg-white px-4 py-2 text-xs font-semibold text-ink-soft shadow-sm">
                        <span class="size-1.5 shrink-0 rounded-full bg-teal" aria-hidden="true"></span>
                        {{ __('app.hero_eyebrow') }}
                    </p>
                    <h1 class="mt-6 text-balance text-4xl leading-[1.15] font-black tracking-tight sm:text-5xl lg:text-6xl">
                        {{ __('app.tagline') }}
                    </h1>
                    <p class="mx-auto mt-6 max-w-lg text-lg leading-relaxed text-ink-soft md:mx-0">{{ __('app.description') }}</p>
                    <div class="mt-9 flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-center md:justify-start">
                        <a href="{{ url('/app') }}" class="inline-flex min-h-14 items-center justify-center rounded-2xl bg-brand px-8 text-lg font-bold text-ink shadow-lg shadow-brand/30 motion-safe:transition-transform motion-safe:hover:-translate-y-0.5 motion-safe:active:scale-[0.98]">
                            {{ __('app.cta') }}
                        </a>
                        <a href="#how" class="inline-flex min-h-14 items-center justify-center gap-2 rounded-2xl px-6 text-base font-semibold text-ink-soft hover:text-ink">
                            {{ __('app.hero_secondary') }}
                            <span aria-hidden="true">↓</span>
                        </a>
                    </div>
                </div>

                {{-- Decorative card mockup --}}
                @php($demoStamps = 7)
                <div class="reveal relative mx-auto w-full max-w-sm" style="--d: 150ms" aria-hidden="true">
                    <div class="pointer-events-none absolute -inset-10 -z-10 rounded-full bg-brand/30 blur-3xl"></div>
                    <div class="motion-safe:animate-float relative rounded-[28px] bg-linear-to-br from-[#2a1d12] via-ink to-[#0c0a09] p-7 text-cream shadow-2xl ring-1 ring-white/10">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold tracking-widest text-cream/80 uppercase">Seef Cafe</p>
                                <p class="mt-1 text-lg font-bold">{{ __('app.hero_stamps_label') }}</p>
                            </div>
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand text-lg font-black text-ink">ت</span>
                        </div>

                        <div class="mt-7 grid grid-cols-5 gap-3">
                            @for ($i = 0; $i < 10; $i++)
                                <div class="flex aspect-square items-center justify-center rounded-full border-2 text-sm font-bold {{ $i < $demoStamps ? 'border-brand bg-brand text-ink motion-safe:animate-stamp' : 'border-dashed border-cream/40 text-cream/80' }}"
                                     style="animation-delay: calc({{ $i }} * 140ms + 400ms);">
                                    @if ($i < $demoStamps)
                                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4.5 4.5L19 7"/></svg>
                                    @else
                                        {{ $i + 1 }}
                                    @endif
                                </div>
                            @endfor
                        </div>

                        <div class="mt-7 flex items-center justify-between gap-4 rounded-2xl bg-white/5 px-4 py-3 ring-1 ring-white/10">
                            <span class="text-sm text-cream/85">{{ __('app.hero_reward') }}</span>
                            <span class="rounded-full bg-teal px-3 py-1 text-xs font-bold text-cream">3 / 10</span>
                        </div>
                    </div>

                    <div class="motion-safe:animate-pop absolute -bottom-5 -start-4 flex items-center gap-3 rounded-2xl bg-white px-4 py-3 shadow-xl ring-1 ring-ink/10" style="animation-delay: 2.4s;">
                        <span class="flex size-9 items-center justify-center rounded-full bg-teal/15 text-teal">
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4.5 4.5L19 7"/></svg>
                        </span>
                        <span class="text-sm font-bold text-ink">{{ __('app.feature_3_title') }}</span>
                    </div>
                </div>
            </section>

            {{-- How it works --}}
            <section id="how" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-20 sm:px-6 md:py-24">
                <h2 class="reveal max-w-2xl text-balance text-3xl font-black tracking-tight sm:text-4xl">{{ __('app.how_title') }}</h2>
                <ol class="mt-12 grid gap-5 md:grid-cols-3">
                    @foreach (['1', '2', '3'] as $n)
                        <li class="reveal h-full" style="--d: {{ $loop->index * 120 }}ms">
                            <div class="h-full rounded-3xl bg-white p-7 shadow-sm ring-1 ring-ink/10 motion-safe:transition-transform motion-safe:duration-300 motion-safe:hover:-translate-y-1">
                                <span class="flex size-12 items-center justify-center rounded-2xl bg-brand/20 text-xl font-black text-ink" aria-hidden="true">{{ $n }}</span>
                                <h3 class="mt-6 text-xl font-bold">{{ __('app.how_'.$n.'_title') }}</h3>
                                <p class="mt-3 leading-relaxed text-ink-soft">{{ __('app.how_'.$n.'_body') }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            {{-- Features --}}
            <section id="features" class="surface-dark scroll-mt-20 bg-ink py-20 text-cream md:py-24">
                <div class="mx-auto max-w-6xl px-4 sm:px-6">
                    <h2 class="reveal max-w-2xl text-balance text-3xl font-black tracking-tight sm:text-4xl">{{ __('app.features_title') }}</h2>
                    <ul class="mt-12 grid gap-5 sm:grid-cols-2">
                        @foreach (['1', '2', '3', '4'] as $n)
                            <li class="reveal h-full" style="--d: {{ $loop->index * 100 }}ms">
                                <div class="h-full rounded-3xl bg-white/5 p-7 ring-1 ring-white/10 hover:bg-white/10">
                                    <div class="mb-5 h-1 w-12 rounded-full bg-brand" aria-hidden="true"></div>
                                    <h3 class="text-xl font-bold">{{ __('app.feature_'.$n.'_title') }}</h3>
                                    <p class="mt-3 leading-relaxed text-cream/80">{{ __('app.feature_'.$n.'_body') }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>

            {{-- Pricing --}}
            <section id="pricing" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-20 sm:px-6 md:py-24">
                <div class="reveal grid items-center gap-10 rounded-[32px] bg-white p-6 shadow-sm ring-1 ring-ink/10 sm:p-8 md:grid-cols-2 md:p-12">
                    <div>
                        <h2 class="text-balance text-3xl font-black tracking-tight sm:text-4xl">{{ __('app.pricing_title') }}</h2>
                        <ul class="mt-8 space-y-4 text-ink">
                            @foreach (['1', '2', '3'] as $n)
                                <li class="flex items-start gap-3">
                                    <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-teal text-cream" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4.5 4.5L19 7"/></svg>
                                    </span>
                                    <span>{{ __('app.pricing_'.$n) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="surface-brand rounded-3xl bg-linear-to-br from-brand to-amber-400 p-8 text-center text-ink shadow-xl shadow-brand/30">
                        <p class="text-5xl font-black tracking-tight sm:text-6xl">{{ __('app.pricing_price') }}</p>
                        <p class="mt-2 font-semibold">{{ __('app.pricing_unit') }}</p>
                        <a href="{{ url('/app') }}" class="mt-8 inline-flex min-h-14 w-full items-center justify-center rounded-2xl bg-ink px-6 font-bold text-cream motion-safe:transition-transform motion-safe:hover:-translate-y-0.5">
                            {{ __('app.pricing_cta') }}
                        </a>
                    </div>
                </div>
            </section>

            {{-- Final call --}}
            <section class="mx-auto max-w-6xl px-4 pb-20 sm:px-6 md:pb-24">
                <div class="reveal surface-teal relative isolate overflow-hidden rounded-[32px] bg-teal px-6 py-16 text-center text-cream sm:px-8">
                    <div class="pointer-events-none absolute -top-24 -end-24 -z-10 size-72 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
                    <h2 class="relative text-balance text-3xl font-black tracking-tight sm:text-5xl">{{ __('app.final_title') }}</h2>
                    <a href="{{ url('/app') }}" class="relative mt-8 inline-flex min-h-14 items-center justify-center rounded-2xl bg-brand px-8 text-lg font-bold text-ink motion-safe:transition-transform motion-safe:hover:-translate-y-0.5">
                        {{ __('app.cta') }}
                    </a>
                </div>
            </section>
        </main>

        <footer class="border-t border-ink/10">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 py-8 text-center text-sm text-ink-soft sm:px-6 md:flex-row md:text-start">
                <p class="font-semibold text-ink">{{ __('app.footer_tagline') }}</p>
                <p>© {{ now()->year }} T3al · {{ __('app.footer_rights') }}</p>
            </div>
        </footer>

        <script>
            (() => {
                const items = document.querySelectorAll('.reveal');
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
                    items.forEach((el) => el.classList.add('is-in'));
                    return;
                }
                const io = new IntersectionObserver((entries, observer) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-in');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.15 });
                items.forEach((el) => io.observe(el));
            })();
        </script>
    </body>
</html>
