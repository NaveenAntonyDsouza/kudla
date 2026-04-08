<x-filament-panels::page>
    {{-- Current Logo Preview --}}
    @php
        $theme = \App\Models\ThemeSetting::getTheme();
    @endphp
    @if($theme->logo_url)
        <div class="mb-4 p-4 bg-gray-50 rounded-lg border">
            <p class="text-sm font-medium text-gray-600 mb-2">Current Logo:</p>
            <img src="{{ $theme->logo_url }}" alt="Current Logo" class="h-12 max-w-xs">
        </div>
    @endif
    @if($theme->favicon_url)
        <div class="mb-4 p-4 bg-gray-50 rounded-lg border">
            <p class="text-sm font-medium text-gray-600 mb-2">Current Favicon:</p>
            <img src="{{ $theme->favicon_url }}" alt="Current Favicon" class="h-8 w-8">
        </div>
    @endif

    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Save Settings
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
