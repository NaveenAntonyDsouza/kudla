<x-filament-panels::page>
    {{-- Current Logo & Favicon Preview --}}
    @php
        $theme = \App\Models\ThemeSetting::getTheme();
    @endphp

    {{-- Preset Themes Picker (Phase 2.6B) --}}
    @if(!empty($presets))
        <div style="margin-bottom: 24px; padding: 20px; border-radius: 10px; border: 1px solid rgba(128,128,128,0.25); background: rgba(128,128,128,0.04);">
            <div style="margin-bottom: 12px;">
                <h3 style="font-size: 16px; font-weight: 600; margin: 0 0 4px;">Preset Themes</h3>
                <p style="font-size: 13px; opacity: 0.7; margin: 0;">Click a preset to apply its color palette instantly. Use the Custom Colors section below to fine-tune.</p>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
                @foreach($presets as $key => $preset)
                    @php $isActive = $activePresetKey === $key; @endphp
                    <div style="padding: 12px; border-radius: 8px; border: 2px solid {{ $isActive ? $preset['primary_color'] : 'rgba(128,128,128,0.2)' }}; background: rgba(255,255,255,0.6); cursor: pointer;"
                         wire:click="applyPreset('{{ $key }}')"
                         wire:confirm="Apply the '{{ $preset['name'] }}' preset? This replaces the current color palette."
                         onmouseover="this.style.borderColor='{{ $preset['primary_color'] }}'"
                         onmouseout="this.style.borderColor='{{ $isActive ? $preset['primary_color'] : 'rgba(128,128,128,0.2)' }}'">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <div style="width: 20px; height: 20px; border-radius: 50%; background: {{ $preset['primary_color'] }};"></div>
                            <div style="width: 20px; height: 20px; border-radius: 50%; background: {{ $preset['secondary_color'] }};"></div>
                            <div style="width: 20px; height: 20px; border-radius: 4px; background: {{ $preset['primary_light'] }}; border: 1px solid rgba(0,0,0,0.08);"></div>
                            @if($isActive)
                                <span style="margin-left: auto; font-size: 10px; font-weight: 600; padding: 2px 6px; border-radius: 999px; background: {{ $preset['primary_color'] }}; color: #fff;">ACTIVE</span>
                            @endif
                        </div>
                        <div style="font-size: 13px; font-weight: 600; margin-bottom: 2px;">{{ $preset['name'] }}</div>
                        <div style="font-size: 11px; opacity: 0.7; line-height: 1.3;">{{ $preset['description'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
        @if($theme->logo_url)
            <div style="padding: 16px; border-radius: 8px; border: 1px solid rgba(128,128,128,0.3); background: rgba(128,128,128,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-size: 13px; font-weight: 500; opacity: 0.7;">Current Logo:</span>
                    <button
                        type="button"
                        wire:click="removeLogo"
                        wire:confirm="Are you sure you want to remove the logo?"
                        style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; font-size: 12px; font-weight: 500; color: #fff; background: #dc2626; border: none; border-radius: 4px; cursor: pointer;"
                        onmouseover="this.style.background='#b91c1c'"
                        onmouseout="this.style.background='#dc2626'"
                    >
                        &times; Remove Logo
                    </button>
                </div>
                <img src="{{ $theme->logo_url }}" alt="Current Logo" style="height: 48px; max-width: 300px;">
            </div>
        @endif
        @if($theme->favicon_url)
            <div style="padding: 16px; border-radius: 8px; border: 1px solid rgba(128,128,128,0.3); background: rgba(128,128,128,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-size: 13px; font-weight: 500; opacity: 0.7;">Current Favicon:</span>
                    <button
                        type="button"
                        wire:click="removeFavicon"
                        wire:confirm="Are you sure you want to remove the favicon?"
                        style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; font-size: 12px; font-weight: 500; color: #fff; background: #dc2626; border: none; border-radius: 4px; cursor: pointer;"
                        onmouseover="this.style.background='#b91c1c'"
                        onmouseout="this.style.background='#dc2626'"
                    >
                        &times; Remove Favicon
                    </button>
                </div>
                <img src="{{ $theme->favicon_url }}" alt="Current Favicon" style="height: 32px; width: 32px;">
            </div>
        @endif
    </div>

    {{-- Live Color Preview --}}
    <div style="margin-bottom: 24px; padding: 16px; border-radius: 8px; border: 1px solid rgba(128,128,128,0.3); background: rgba(128,128,128,0.05);">
        <p style="font-size: 13px; font-weight: 500; opacity: 0.7; margin-bottom: 12px;">Current Theme Colors:</p>
        <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <div style="width: 32px; height: 32px; border-radius: 4px; border: 1px solid rgba(128,128,128,0.3); background-color: {{ $theme->primary_color ?? '#8B1D91' }};"></div>
                <span style="font-size: 11px; opacity: 0.6;">Primary</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <div style="width: 32px; height: 32px; border-radius: 4px; border: 1px solid rgba(128,128,128,0.3); background-color: {{ $theme->primary_hover ?? '#6B1571' }};"></div>
                <span style="font-size: 11px; opacity: 0.6;">Hover</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <div style="width: 32px; height: 32px; border-radius: 4px; border: 1px solid rgba(128,128,128,0.3); background-color: {{ $theme->primary_light ?? '#F3E8F7' }};"></div>
                <span style="font-size: 11px; opacity: 0.6;">Light</span>
            </div>
            <div style="width: 1px; height: 32px; background: rgba(128,128,128,0.3);"></div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <div style="width: 32px; height: 32px; border-radius: 4px; border: 1px solid rgba(128,128,128,0.3); background-color: {{ $theme->secondary_color ?? '#00BCD4' }};"></div>
                <span style="font-size: 11px; opacity: 0.6;">Secondary</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <div style="width: 32px; height: 32px; border-radius: 4px; border: 1px solid rgba(128,128,128,0.3); background-color: {{ $theme->secondary_hover ?? '#00ACC1' }};"></div>
                <span style="font-size: 11px; opacity: 0.6;">Hover</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <div style="width: 32px; height: 32px; border-radius: 4px; border: 1px solid rgba(128,128,128,0.3); background-color: {{ $theme->secondary_light ?? '#E0F7FA' }};"></div>
                <span style="font-size: 11px; opacity: 0.6;">Light</span>
            </div>
        </div>
    </div>

    <form wire:submit="save">
        {{ $this->form }}

        <div style="margin-top: 24px; display: flex; align-items: center; gap: 12px;">
            <x-filament::button type="submit">
                Save Theme
            </x-filament::button>

            <x-filament::button color="gray" wire:click="resetToDefaults" type="button">
                Reset to Defaults
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
