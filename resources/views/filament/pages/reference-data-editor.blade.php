<x-filament-panels::page>
    <div style="margin-bottom: 1.5rem;">
        {{-- Category Selector --}}
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
            <label style="font-size: 0.875rem; font-weight: 600; color: #374151; white-space: nowrap;">Category:</label>
            <select wire:model.live="selectedCategory"
                style="flex: 1; max-width: 400px; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; background: white;">
                @foreach(\App\Filament\Pages\ReferenceDataEditor::categories() as $key => $cat)
                    <option value="{{ $key }}">{{ $cat['label'] }} {{ $cat['grouped'] ? '(Grouped)' : '' }}</option>
                @endforeach
            </select>
        </div>

        {{-- Instructions --}}
        <div style="padding: 0.75rem 1rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 0.5rem; font-size: 0.8rem; color: #1e40af; margin-bottom: 1rem;">
            @if($isGrouped)
                <strong>Grouped list:</strong> Use <code style="background: #dbeafe; padding: 0.125rem 0.25rem; border-radius: 0.25rem;"># Group Name</code> to start a group, then list items below it (one per line).
                <br>Example: <code style="background: #dbeafe; padding: 0.125rem 0.25rem; border-radius: 0.25rem;"># Engineering</code> followed by <code>B.Tech</code>, <code>M.Tech</code>, etc.
            @else
                <strong>Simple list:</strong> Enter one item per line. Items appear in dropdown menus exactly as typed.
            @endif
        </div>

        {{-- Editor --}}
        <textarea wire:model.defer="editorContent"
            style="width: 100%; min-height: 400px; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-family: ui-monospace, SFMono-Regular, monospace; font-size: 0.8rem; line-height: 1.5; resize: vertical; background: #fafafa;"
            placeholder="Enter items, one per line..."></textarea>

        {{-- Action Buttons --}}
        <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
            <button wire:click="save" type="button"
                style="padding: 0.625rem 1.5rem; background: #8B1D91; color: white; border: none; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; cursor: pointer;"
                onmouseover="this.style.background='#6B1571'" onmouseout="this.style.background='#8B1D91'">
                Save Changes
            </button>
            <button wire:click="resetToDefault" type="button"
                wire:confirm="This will reset this category to the default values from the config file. Any custom changes will be lost. Continue?"
                style="padding: 0.625rem 1.5rem; background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; cursor: pointer;"
                onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                Reset to Default
            </button>
        </div>
    </div>
</x-filament-panels::page>
