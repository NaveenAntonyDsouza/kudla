<x-filament-panels::page>
    <form wire:submit="send">
        {{ $this->form }}

        <div style="margin-top: 24px; display: flex; align-items: center; gap: 12px;">
            <x-filament::button type="submit" color="success">
                Send Notification
            </x-filament::button>

            <x-filament::button color="gray" wire:click="preview" type="button">
                Preview Count
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
