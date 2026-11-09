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
        <header class="mx-auto flex max-w-5xl items-center justify-between px-6 py-5">
            <span class="text-2xl font-extrabold text-brand">T3al</span>
            <nav class="flex items-center gap-4 text-sm">
                @if (app()->getLocale() === 'ar')
                    <a href="{{ route('locale.switch', 'en') }}" class="text-ink/70 hover:text-ink">English</a>
                @else
                    <a href="{{ route('locale.switch', 'ar') }}" class="text-ink/70 hover:text-ink">العربية</a>
                @endif
            </nav>
        </header>

        <main class="mx-auto max-w-5xl px-6 pt-16 pb-24 text-center">
            <h1 class="text-5xl font-extrabold leading-tight">{{ __('app.tagline') }}</h1>
            <p class="mx-auto mt-5 max-w-xl text-lg text-ink/70">{{ __('app.description') }}</p>
            <a href="{{ url('/app') }}" class="mt-10 inline-block rounded-2xl bg-brand px-8 py-4 text-lg font-bold text-ink">
                {{ __('app.cta') }}
            </a>
        </main>
    </body>
</html>
