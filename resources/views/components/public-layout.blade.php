<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <meta name="theme-color" content="#FAF7F2">
        <title>{{ $title ?? config('app.name') }}</title>
        <x-font-links />
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-dvh bg-cream text-ink antialiased">
        <a href="#main" class="skip-link">{{ __('app.skip_to_content') }}</a>

        <header class="border-b border-ink/10 bg-cream">
            <div class="mx-auto flex h-14 max-w-md items-center gap-2.5 px-4">
                <span class="flex size-8 items-center justify-center rounded-lg bg-brand text-base font-black text-ink" aria-hidden="true">ت</span>
                <span class="text-lg font-extrabold tracking-tight">T3al</span>
            </div>
        </header>

        <main id="main" class="mx-auto w-full max-w-md px-4 py-6 sm:py-10">
            {{ $slot }}
        </main>
    </body>
</html>
