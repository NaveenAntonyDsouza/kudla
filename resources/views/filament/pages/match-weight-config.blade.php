<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div style="margin-top: 24px; display: flex; align-items: center; gap: 12px;">
            <x-filament::button type="submit">
                Save Weights
            </x-filament::button>

            <x-filament::button color="gray" wire:click="resetToDefaults" type="button">
                Reset to Defaults
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
