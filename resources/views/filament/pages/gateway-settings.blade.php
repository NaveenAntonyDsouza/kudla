<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
            <x-filament::button type="submit">
                Save Settings
            </x-filament::button>

            <x-filament::button type="button" wire:click="sendTestEmail" color="gray">
                Send Test Email
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
