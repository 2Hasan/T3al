<x-public-layout :title="__('app.invite_accept_title')">
    <main class="mx-auto max-w-md px-4 py-16">
        <div class="rounded-[1.5rem] bg-white p-8 shadow-sm ring-1 ring-ink/10">
            <h1 class="text-2xl font-black tracking-tight">{{ __('app.invite_accept_heading', ['shop' => $invitation->merchant->name]) }}</h1>
            <p class="mt-2 text-sm text-ink-soft">{{ __('app.invite_accept_for', ['email' => $invitation->email]) }}</p>

            @php($hasAccount = \App\Models\User::where('email', $invitation->email)->exists())

            <form method="POST" action="{{ route('invitations.accept', $token) }}" class="mt-8 space-y-5">
                @csrf

                @unless ($hasAccount)
                    <div>
                        <label for="name" class="block text-sm font-bold">{{ __('app.customer_name') }}</label>
                        <input id="name" name="name" type="text" required maxlength="60" value="{{ old('name') }}"
                               class="mt-2 min-h-12 w-full rounded-xl border border-ink/15 bg-white px-4">
                        @error('name')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-bold">{{ __('app.invite_choose_password') }}</label>
                        <input id="password" name="password" type="password" required minlength="8"
                               class="mt-2 min-h-12 w-full rounded-xl border border-ink/15 bg-white px-4">
                        @error('password')<p class="mt-1 text-sm text-danger">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-bold">{{ __('app.invite_confirm_password') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8"
                               class="mt-2 min-h-12 w-full rounded-xl border border-ink/15 bg-white px-4">
                    </div>
                @else
                    <p class="rounded-xl bg-cream p-4 text-sm text-ink-soft">{{ __('app.invite_existing_account') }}</p>
                @endunless

                <button type="submit" class="min-h-12 w-full rounded-xl bg-brand px-6 font-bold text-ink">
                    {{ $hasAccount ? __('app.invite_join') : __('app.invite_create_and_join') }}
                </button>
            </form>
        </div>
    </main>
</x-public-layout>
