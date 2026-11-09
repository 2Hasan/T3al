<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('app.tagline') }} · T3al</title>
        <meta name="description" content="{{ __('app.description') }}">
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-cream text-ink antialiased">
        <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-[60vh] bg-[radial-gradient(60%_50%_at_50%_0%,rgba(245,158,11,0.22),transparent)]"></div>

        <header class="sticky top-0 z-20 border-b border-ink/5 bg-cream/80 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-3">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-brand text-lg font-black text-ink">ت</span>
                    <span class="text-xl font-extrabold tracking-tight">T3al</span>
                </a>

                <nav class="hidden items-center gap-7 text-sm font-medium text-ink/70 md:flex">
                    <a href="#how" class="hover:text-ink">{{ __('app.nav_how') }}</a>
                    <a href="#features" class="hover:text-ink">{{ __('app.nav_features') }}</a>
                    <a href="#pricing" class="hover:text-ink">{{ __('app.nav_pricing') }}</a>
                </nav>

                <div class="flex items-center gap-2">
                    @if (app()->getLocale() === 'ar')
                        <a href="{{ route('locale.switch', 'en') }}" class="inline-flex min-h-10 items-center rounded-full px-3 text-sm font-semibold text-ink/70 hover:bg-ink/5">English</a>
                    @else
                        <a href="{{ route('locale.switch', 'ar') }}" class="inline-flex min-h-10 items-center rounded-full px-3 text-sm font-semibold text-ink/70 hover:bg-ink/5">العربية</a>
                    @endif
                    <a href="{{ url('/app') }}" class="hidden min-h-10 items-center rounded-full bg-ink px-4 text-sm font-semibold text-cream transition hover:-translate-y-0.5 sm:inline-flex">{{ __('app.nav_start') }}</a>
                </div>
            </div>
        </header>

        <main class="overflow-x-clip">
            {{-- Hero --}}
            <section class="mx-auto grid max-w-6xl items-center gap-12 px-5 pt-16 pb-24 md:grid-cols-2 md:pt-24">
                <div class="reveal text-center md:text-start">
                    <p class="inline-flex items-center gap-2 rounded-full border border-ink/10 bg-white/70 px-4 py-1.5 text-xs font-semibold text-ink/70 shadow-sm">
                        <span class="size-1.5 rounded-full bg-teal"></span>
                        {{ __('app.hero_eyebrow') }}
                    </p>
                    <h1 class="mt-6 text-5xl font-black leading-[1.1] tracking-tight md:text-6xl">
                        {{ __('app.tagline') }}
                    </h1>
                    <p class="mx-auto mt-6 max-w-lg text-lg leading-relaxed text-ink/70 md:mx-0">{{ __('app.description') }}</p>
                    <div class="mt-9 flex flex-col items-center gap-3 sm:flex-row sm:justify-center md:justify-start">
                        <a href="{{ url('/app') }}" class="inline-flex min-h-13 w-full items-center justify-center rounded-2xl bg-brand px-8 py-4 text-lg font-bold text-ink shadow-[0_10px_30px_-10px_rgba(245,158,11,0.8)] transition hover:-translate-y-0.5 sm:w-auto">
                            {{ __('app.cta') }}
                        </a>
                        <a href="#how" class="inline-flex min-h-13 items-center px-4 py-4 text-base font-semibold text-ink/70 hover:text-ink">{{ __('app.hero_secondary') }} ↓</a>
                    </div>
                </div>

                {{-- Card mockup with stamps animating in --}}
                <div class="reveal relative mx-auto w-full max-w-sm" style="--d: 150ms">
                    <div class="absolute -inset-10 -z-10 rounded-full bg-brand/30 blur-3xl"></div>
                    <div class="float-card relative rounded-[28px] bg-gradient-to-br from-[#2a1d12] via-ink to-[#0c0a09] p-7 text-cream shadow-2xl ring-1 ring-white/10">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-cream/60">Seef Cafe</p>
                                <p class="mt-1 text-lg font-bold">{{ __('app.hero_stamps_label') }}</p>
                            </div>
                            <span class="flex size-10 items-center justify-center rounded-xl bg-brand text-lg font-black text-ink">ت</span>
                        </div>

                        <div class="mt-7 grid grid-cols-5 gap-3">
                            @for ($i = 0; $i < 10; $i++)
                                <div class="stamp-slot flex aspect-square items-center justify-center rounded-full border-2 border-dashed border-cream/25 text-sm font-bold text-cream/30 {{ $i < 7 ? 'is-filled' : '' }}"
                                     style="--i: {{ $i }}">
                                    @if ($i < 7)
                                        <svg viewBox="0 0 24 24" class="size-5 text-ink" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 4.5 4.5L19 7"/></svg>
                                    @else
                                        {{ $i + 1 }}
                                    @endif
                                </div>
                            @endfor
                        </div>

                        <div class="mt-7 flex items-center justify-between rounded-2xl bg-white/5 px-4 py-3 ring-1 ring-white/10">
                            <span class="text-sm text-cream/70">{{ __('app.hero_reward') }}</span>
                            <span class="rounded-full bg-teal px-3 py-1 text-xs font-bold text-cream">3 / 10</span>
                        </div>
                    </div>

                    <div class="notify-pill absolute -bottom-5 -start-4 flex items-center gap-3 rounded-2xl bg-white px-4 py-3 shadow-xl ring-1 ring-ink/5">
                        <span class="flex size-9 items-center justify-center rounded-full bg-teal/15 text-teal">
                            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 4.5 4.5L19 7"/></svg>
                        </span>
                        <span class="text-sm font-bold text-ink">{{ __('app.feature_3_title') }}</span>
                    </div>
                </div>
            </section>

            {{-- How it works --}}
            <section id="how" class="mx-auto max-w-6xl px-5 py-20">
                <h2 class="reveal max-w-2xl text-3xl font-black tracking-tight md:text-4xl">{{ __('app.how_title') }}</h2>
                <div class="mt-12 grid gap-5 md:grid-cols-3">
                    @foreach (['1', '2', '3'] as $n)
                        <div class="reveal rounded-3xl bg-white p-7 shadow-sm ring-1 ring-ink/5 transition hover:-translate-y-1 hover:shadow-xl" style="--d: {{ $loop->index * 120 }}ms">
                            <span class="flex size-12 items-center justify-center rounded-2xl bg-brand/15 text-xl font-black text-ink">{{ $n }}</span>
                            <h3 class="mt-6 text-xl font-bold">{{ __('app.how_'.$n.'_title') }}</h3>
                            <p class="mt-3 leading-relaxed text-ink/65">{{ __('app.how_'.$n.'_body') }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Features --}}
            <section id="features" class="bg-ink py-24 text-cream">
                <div class="mx-auto max-w-6xl px-5">
                    <h2 class="reveal max-w-2xl text-3xl font-black tracking-tight md:text-4xl">{{ __('app.features_title') }}</h2>
                    <div class="mt-12 grid gap-5 sm:grid-cols-2">
                        @foreach (['1', '2', '3', '4'] as $n)
                            <div class="reveal rounded-3xl bg-white/[0.04] p-7 ring-1 ring-white/10 transition hover:bg-white/[0.07]" style="--d: {{ $loop->index * 100 }}ms">
                                <div class="mb-5 h-1 w-12 rounded-full bg-brand"></div>
                                <h3 class="text-xl font-bold">{{ __('app.feature_'.$n.'_title') }}</h3>
                                <p class="mt-3 leading-relaxed text-cream/65">{{ __('app.feature_'.$n.'_body') }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Pricing --}}
            <section id="pricing" class="mx-auto max-w-6xl px-5 py-24">
                <div class="reveal grid items-center gap-10 rounded-[32px] bg-white p-8 shadow-sm ring-1 ring-ink/5 md:grid-cols-2 md:p-12">
                    <div>
                        <h2 class="text-3xl font-black tracking-tight md:text-4xl">{{ __('app.pricing_title') }}</h2>
                        <ul class="mt-8 space-y-4 text-ink/75">
                            @foreach (['1', '2', '3'] as $n)
                                <li class="flex items-start gap-3">
                                    <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-teal text-cream">
                                        <svg viewBox="0 0 24 24" class="size-3.5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 4.5 4.5L19 7"/></svg>
                                    </span>
                                    {{ __('app.pricing_'.$n) }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="rounded-3xl bg-gradient-to-br from-brand to-amber-400 p-8 text-center text-ink shadow-[0_20px_50px_-20px_rgba(245,158,11,0.9)]">
                        <p class="text-6xl font-black tracking-tight">{{ __('app.pricing_price') }}</p>
                        <p class="mt-2 font-semibold text-ink/80">{{ __('app.pricing_unit') }}</p>
                        <a href="{{ url('/app') }}" class="mt-8 inline-flex min-h-12 w-full items-center justify-center rounded-2xl bg-ink px-6 py-3 font-bold text-cream transition hover:-translate-y-0.5">
                            {{ __('app.pricing_cta') }}
                        </a>
                    </div>
                </div>
            </section>

            {{-- Final call --}}
            <section class="mx-auto max-w-6xl px-5 pb-24">
                <div class="reveal relative overflow-hidden rounded-[32px] bg-teal px-8 py-16 text-center text-cream">
                    <div class="pointer-events-none absolute -top-24 -end-24 size-72 rounded-full bg-white/10 blur-2xl"></div>
                    <h2 class="relative text-3xl font-black tracking-tight md:text-5xl">{{ __('app.final_title') }}</h2>
                    <a href="{{ url('/app') }}" class="relative mt-8 inline-flex min-h-13 items-center rounded-2xl bg-brand px-8 py-4 text-lg font-bold text-ink transition hover:-translate-y-0.5">
                        {{ __('app.cta') }}
                    </a>
                </div>
            </section>
        </main>

        <footer class="border-t border-ink/10">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-5 py-8 text-sm text-ink/60 md:flex-row">
                <p class="font-semibold text-ink">{{ __('app.footer_tagline') }}</p>
                <p>© {{ now()->year }} T3al · {{ __('app.footer_rights') }}</p>
            </div>
        </footer>

        <style>
            .reveal { opacity: 0; transform: translateY(16px); transition: opacity .7s ease, transform .7s ease; transition-delay: var(--d, 0ms); }
            .reveal.in { opacity: 1; transform: none; }

            .stamp-slot.is-filled { background: var(--color-brand); border-style: solid; border-color: var(--color-brand); color: var(--color-ink); animation: stamp 520ms cubic-bezier(.2,.9,.3,1.4) both; animation-delay: calc(var(--i) * 140ms + 400ms); }
            @keyframes stamp { 0% { transform: scale(1.6) rotate(-12deg); opacity: 0; } 60% { transform: scale(.92) rotate(2deg); opacity: 1; } 100% { transform: scale(1) rotate(0); } }

            .float-card { animation: float 6s ease-in-out infinite; }
            @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }

            .notify-pill { animation: pop 600ms cubic-bezier(.2,.9,.3,1.3) 2.4s both; }
            @keyframes pop { from { opacity: 0; transform: translateY(12px) scale(.9); } to { opacity: 1; transform: none; } }

            @media (prefers-reduced-motion: reduce) {
                .reveal { opacity: 1; transform: none; transition: none; }
                .float-card, .stamp-slot.is-filled, .notify-pill { animation: none; }
                .stamp-slot.is-filled { transform: none; }
            }
        </style>

        <script>
            (() => {
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
                    document.querySelectorAll('.reveal').forEach(el => el.classList.add('in'));
                    return;
                }
                const io = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('in');
                            io.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.15 });
                document.querySelectorAll('.reveal').forEach(el => io.observe(el));
            })();
        </script>
    </body>
</html>
