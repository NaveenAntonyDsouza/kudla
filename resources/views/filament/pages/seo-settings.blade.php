<x-filament-panels::page>
    {{-- Global SEO Form --}}
    <form wire:submit="saveGlobal">
        {{ $this->globalForm }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Save Global SEO
            </x-filament::button>
        </div>
    </form>

    <hr class="my-8 border-gray-200 dark:border-gray-700">

    {{-- Per-Page SEO Form --}}
    <form wire:submit="savePages">
        {{ $this->pageForm }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Save Page SEO
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
