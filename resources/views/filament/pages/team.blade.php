<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-[1.25rem] bg-white p-6 shadow-sm ring-1 ring-ink/10 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-lg font-black">{{ __('app.team_members') }}</h2>
            <ul class="mt-4 divide-y divide-ink/10 dark:divide-white/10">
                @foreach ($this->members() as $member)
                    <li class="flex items-center justify-between gap-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate font-bold">{{ $member['name'] }} @if ($member['is_me'])<span class="text-xs text-ink-soft">({{ __('app.you') }})</span>@endif</p>
                            <p class="truncate text-sm text-ink-soft">{{ $member['email'] }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            <span class="rounded-full bg-cream px-3 py-1 text-xs font-bold text-ink">{{ $member['role'] }}</span>
                            @if ($member['can_remove'])
                                <button type="button" wire:click="removeMember({{ $member['id'] }})" wire:confirm="{{ __('app.team_confirm_remove') }}"
                                        class="min-h-11 rounded-xl px-3 text-sm font-bold text-danger hover:underline">{{ __('app.team_remove') }}</button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="rounded-[1.25rem] bg-white p-6 shadow-sm ring-1 ring-ink/10 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="text-lg font-black">{{ __('app.team_pending') }}</h2>
            @forelse ($this->pendingInvitations() as $invitation)
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-ink/10 py-3 last:border-0">
                    <div class="min-w-0">
                        <p class="truncate font-bold">{{ $invitation->email }}</p>
                        <p class="text-sm text-ink-soft">{{ __('app.team_expires', ['date' => $invitation->expires_at->format('Y-m-d')]) }}</p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <button type="button" wire:click="resend({{ $invitation->id }})" class="min-h-11 rounded-xl border border-ink/15 px-3 text-sm font-bold">{{ __('app.team_resend') }}</button>
                        <button type="button" wire:click="revoke({{ $invitation->id }})" class="min-h-11 rounded-xl px-3 text-sm font-bold text-danger">{{ __('app.team_revoke') }}</button>
                    </div>
                </div>
            @empty
                <p class="mt-4 text-sm text-ink-soft">{{ __('app.team_none_pending') }}</p>
            @endforelse
        </section>
    </div>
</x-filament-panels::page>
