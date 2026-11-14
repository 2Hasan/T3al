<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap gap-3">
            <x-filament::button type="submit">{{ __('app.save') }}</x-filament::button>
            <x-filament::button color="gray" type="button" wire:click="sendTest">{{ __('app.mail_send_test') }}</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
